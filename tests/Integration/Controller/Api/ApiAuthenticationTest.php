<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api;

use App\Controller\Api\MeController;
use App\Fixtures\DataFixtures\Factory\CharacterFactory;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use App\Fixtures\DataFixtures\Factory\KindFactory;
use App\Fixtures\DataFixtures\Factory\UserFactory;
use App\Repository\ApiTokenRepository;
use App\Security\ApiTokenHandler;
use App\Tests\Support\IssuesApiTokens;
use App\Tests\Support\ResetsRateLimiters;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(ApiTokenHandler::class)]
#[CoversClass(MeController::class)]
class ApiAuthenticationTest extends WebTestCase
{
    use Factories;
    use IssuesApiTokens;
    use ResetDatabase;
    use ResetsRateLimiters;

    public function testMeReturnsTheAuthenticatedUser(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => 'aldric@example.com', 'username' => null])->_real();
        $token = $this->issueApiToken($user);

        $client->request('GET', '/api/me', server: $this->bearer($token));

        self::assertResponseIsSuccessful();
        $me = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame($user->getId(), $me['id']);
        self::assertSame('aldric@example.com', $me['email']);
        self::assertNull($me['username']);
        self::assertSame('aldric@example.com', $me['displayName']);
        self::assertSame(['ROLE_USER'], $me['roles']);

        $apiToken = self::getContainer()->get(ApiTokenRepository::class)->findOneByPlainToken($token);
        self::assertNotNull($apiToken?->getLastUsedAt());
    }

    public function testMeRequiresAToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/me');

        self::assertResponseStatusCodeSame(401);
    }

    public function testUnknownTokenIsRejected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/me', server: $this->bearer('rpg_unknown'));

        self::assertResponseStatusCodeSame(401);
        self::assertResponseHasHeader('WWW-Authenticate');
    }

    public function testExpiredTokenIsRejected(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne()->_real();
        $token = $this->issueApiToken($user, new \DateTimeImmutable('-31 days'));

        $client->request('GET', '/api/me', server: $this->bearer($token));

        self::assertResponseStatusCodeSame(401);
    }

    public function testTokenBasedCharacterRoutesStayPublic(): void
    {
        $client = static::createClient();
        CharacterFactory::createOne([
            'game' => GameFactory::createOne(),
            'token' => 'public-sheet-token',
            'kind' => KindFactory::createOne(),
        ]);

        // Anonymous access, as today.
        $client->request('GET', '/api/characters/public-sheet-token');
        self::assertResponseIsSuccessful();

        // Same route with a valid access token: still served.
        $token = $this->issueApiToken(UserFactory::createOne()->_real());
        $client->request('GET', '/api/characters/public-sheet-token', server: $this->bearer($token));
        self::assertResponseIsSuccessful();
    }

    public function testInvalidTokenIsRejectedEvenOnPublicRoutes(): void
    {
        $client = static::createClient();

        // A sent token is always checked: the request does not silently fall back to anonymous.
        // The front must drop its token on 401 (it can then retry the public route without it).
        $client->request('GET', '/api/health', server: $this->bearer('rpg_unknown'));
        self::assertResponseStatusCodeSame(401);

        $client->request('GET', '/api/health');
        self::assertResponseIsSuccessful();
    }

    public function testCorsPreflightAllowsTheAuthorizationHeader(): void
    {
        $client = static::createClient();

        $client->request('OPTIONS', '/api/me', server: [
            'HTTP_ORIGIN' => 'http://localhost:51173',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'authorization',
        ]);

        self::assertResponseIsSuccessful();
        self::assertStringContainsStringIgnoringCase(
            'authorization',
            (string) $client->getResponse()->headers->get('Access-Control-Allow-Headers'),
        );
    }

    public function testRejectedBearerTokensDoNotLockOutSignInsFromTheSameAddress(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => 'aldric@example.com', 'password' => 'secret123'])->_real();
        $token = $this->issueApiToken($user);

        // e.g. several devices on the same network still sending an expired token
        for ($i = 0; $i < 30; ++$i) {
            $client->request('GET', '/api/me', server: $this->bearer('rpg_expired'));
            self::assertResponseStatusCodeSame(401);
        }

        $client->request('GET', '/api/me', server: $this->bearer($token));
        self::assertResponseIsSuccessful();

        $client->jsonRequest('POST', '/api/auth/login', ['email' => 'aldric@example.com', 'password' => 'secret123']);
        self::assertResponseIsSuccessful();
    }
}
