<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api;

use App\Controller\Api\AuthController;
use App\Entity\User;
use App\Factory\UserFactory as AppUserFactory;
use App\Fixtures\DataFixtures\Factory\UserFactory;
use App\Repository\ApiTokenRepository;
use App\Repository\UserRepository;
use App\Security\ApiLoginFailureHandler;
use App\Security\ApiLoginSuccessHandler;
use App\Serializer\UserSerializer;
use App\Tests\Support\IssuesApiTokens;
use App\Tests\Support\ResetsRateLimiters;
use App\Validator\UniqueUserEmailValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(AuthController::class)]
#[CoversClass(UserSerializer::class)]
#[CoversClass(AppUserFactory::class)]
#[CoversClass(UniqueUserEmailValidator::class)]
#[CoversClass(ApiLoginSuccessHandler::class)]
#[CoversClass(ApiLoginFailureHandler::class)]
class AuthControllerTest extends WebTestCase
{
    use Factories;
    use IssuesApiTokens;
    use ResetDatabase;
    use ResetsRateLimiters;

    public function testRegisterCreatesAnAccount(): void
    {
        $client = static::createClient();

        $this->register($client, ['email' => ' New.Player@Example.com ', 'password' => 'secret-password']);

        self::assertResponseStatusCodeSame(201);
        $payload = $this->json($client);
        self::assertSame('new.player@example.com', $payload['email']);
        self::assertNull($payload['username']);
        self::assertSame('new.player@example.com', $payload['displayName']);
        self::assertSame(['ROLE_USER'], $payload['roles']);

        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'new.player@example.com']);
        self::assertInstanceOf(User::class, $user);
        self::assertNotSame('secret-password', $user->getPassword(), 'The password must be hashed.');
    }

    public function testRegisteredAccountCanSignInOnTheWebBackOffice(): void
    {
        $client = static::createClient();
        $this->register($client, ['email' => 'gm@example.com', 'password' => 'secret-password']);
        self::assertResponseStatusCodeSame(201);

        $client->request('POST', '/sign-in', ['_username' => 'gm@example.com', '_password' => 'secret-password']);
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }

    public function testRegisterRejectsAnAlreadyUsedEmailCaseInsensitively(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'aldric@example.com']);

        $this->register($client, ['email' => 'Aldric@Example.com', 'password' => 'secret-password']);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['email' => ['This email is already used.']], $this->json($client)['violations']);
    }

    public function testRegisterValidatesEveryField(): void
    {
        $client = static::createClient();

        $this->register($client, ['email' => 'not-an-email', 'password' => 'short']);

        self::assertResponseStatusCodeSame(422);
        $payload = $this->json($client);
        self::assertSame('Validation failed', $payload['error']);
        self::assertArrayHasKey('email', $payload['violations']);
        self::assertArrayHasKey('password', $payload['violations']);
    }

    public function testRegisterRequiresEmailAndPassword(): void
    {
        $client = static::createClient();

        $this->register($client, []);

        self::assertResponseStatusCodeSame(422);
        self::assertEqualsCanonicalizing(['email', 'password'], array_keys($this->json($client)['violations']));
    }

    public function testRegisterRejectsAnInvalidBody(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/register', server: ['CONTENT_TYPE' => 'application/json'], content: 'not json');

        self::assertResponseStatusCodeSame(400);
    }

    public function testRegisterIsRateLimitedPerIp(): void
    {
        $client = static::createClient();

        for ($i = 0; $i < 10; ++$i) {
            $this->register($client, ['email' => "player$i@example.com", 'password' => 'secret-password']);
            self::assertResponseStatusCodeSame(201);
        }

        $this->register($client, ['email' => 'one-too-many@example.com', 'password' => 'secret-password']);

        self::assertResponseStatusCodeSame(429);
        self::assertResponseHasHeader('Retry-After');
        self::assertNull(self::getContainer()->get(UserRepository::class)->findOneByEmail('one-too-many@example.com'));
    }

    public function testLoginIssuesAnAccessToken(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'aldric@example.com', 'password' => 'secret-password']);

        $this->login($client, 'Aldric@Example.com', 'secret-password');

        self::assertResponseIsSuccessful();
        $payload = $this->json($client);
        self::assertStringStartsWith('rpg_', $payload['token']);
        self::assertSame('aldric@example.com', $payload['user']['email']);
        $expiresAt = new \DateTimeImmutable($payload['expiresAt']);
        self::assertEqualsWithDelta(new \DateTimeImmutable('+30 days')->getTimestamp(), $expiresAt->getTimestamp(), 60);

        $client->request('GET', '/api/me', server: $this->bearer($payload['token']));
        self::assertResponseIsSuccessful();
        self::assertSame('aldric@example.com', $this->json($client)['email']);
    }

    public function testLoginRejectsWrongCredentialsWithTheSameMessage(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'aldric@example.com', 'password' => 'secret-password']);

        $this->login($client, 'aldric@example.com', 'wrong-password');
        self::assertResponseStatusCodeSame(401);
        self::assertSame(['error' => 'Invalid credentials.'], $this->json($client));

        $this->login($client, 'nobody@example.com', 'secret-password');
        self::assertResponseStatusCodeSame(401);
        self::assertSame(['error' => 'Invalid credentials.'], $this->json($client));

        self::assertCount(0, self::getContainer()->get(ApiTokenRepository::class)->findAll());
    }

    public function testLoginIsThrottledAfterFiveFailedAttempts(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'aldric@example.com', 'password' => 'secret-password']);

        for ($i = 0; $i < 5; ++$i) {
            $this->login($client, 'aldric@example.com', 'wrong-password');
            self::assertResponseStatusCodeSame(401);
        }

        // Even the right password is refused until the window expires.
        $this->login($client, 'aldric@example.com', 'secret-password');
        self::assertResponseStatusCodeSame(429);
    }

    public function testLoginRequiresAJsonBody(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/login', ['email' => 'aldric@example.com', 'password' => 'secret-password']);

        self::assertResponseStatusCodeSame(400);
    }

    public function testLogoutRevokesOnlyTheCurrentToken(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne()->_real();
        $phone = $this->issueApiToken($user);
        $laptop = $this->issueApiToken($user);

        $client->request('POST', '/api/auth/logout', server: $this->bearer($phone));
        self::assertResponseStatusCodeSame(204);

        $client->request('GET', '/api/me', server: $this->bearer($phone));
        self::assertResponseStatusCodeSame(401);

        $client->request('GET', '/api/me', server: $this->bearer($laptop));
        self::assertResponseIsSuccessful();
    }

    public function testLogoutRequiresAToken(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/logout');

        self::assertResponseStatusCodeSame(401);
    }

    private function login(KernelBrowser $client, string $email, string $password): void
    {
        $client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(['email' => $email, 'password' => $password]),
        );
    }

    /**
     * @param array<string, mixed> $body
     */
    private function register(KernelBrowser $client, array $body): void
    {
        $client->request(
            'POST',
            '/api/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode($body),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function json(KernelBrowser $client): array
    {
        return json_decode((string) $client->getResponse()->getContent(), true);
    }
}
