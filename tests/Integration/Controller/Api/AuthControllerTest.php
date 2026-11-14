<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api;

use App\Controller\Api\AuthController;
use App\Entity\User;
use App\Factory\UserFactory as AppUserFactory;
use App\Fixtures\DataFixtures\Factory\UserFactory;
use App\Repository\UserRepository;
use App\Serializer\UserSerializer;
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
class AuthControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

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
