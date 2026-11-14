<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api;

use App\Controller\Api\MyCharacterController;
use App\Entity\Character;
use App\Fixtures\DataFixtures\Factory\CharacterFactory;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use App\Fixtures\DataFixtures\Factory\KindFactory;
use App\Fixtures\DataFixtures\Factory\UserFactory;
use App\Repository\CharacterRepository;
use App\Serializer\CharacterSerializer;
use App\Tests\Support\IssuesApiTokens;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(MyCharacterController::class)]
#[CoversClass(CharacterSerializer::class)]
class MyCharacterControllerTest extends WebTestCase
{
    use Factories;
    use IssuesApiTokens;
    use ResetDatabase;

    public function testListsOnlyTheCharactersOfTheSignedInUser(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne()->_real();
        $game = GameFactory::createOne(['name' => 'Les Terres Brûlées']);
        $kind = KindFactory::createOne(['name' => 'Human']);
        CharacterFactory::createOne(['name' => 'Zora', 'user' => $user, 'game' => $game, 'kind' => $kind, 'token' => 'zora-token']);
        CharacterFactory::createOne(['name' => 'Aldric', 'user' => $user, 'game' => $game, 'kind' => $kind]);
        CharacterFactory::createOne(['name' => 'Someone else', 'user' => UserFactory::createOne(), 'game' => $game, 'kind' => $kind]);
        CharacterFactory::createOne(['name' => 'Nobody', 'game' => $game, 'kind' => $kind]);

        $client->request('GET', '/api/me/characters', server: $this->bearer($this->issueApiToken($user)));

        self::assertResponseIsSuccessful();
        $payload = $this->json($client);
        self::assertSame(['Aldric', 'Zora'], array_column($payload, 'name'));
        self::assertSame('zora-token', $payload[1]['token']);
        self::assertSame('Human', $payload[1]['kind']);
        self::assertSame(['id' => $game->getId(), 'name' => 'Les Terres Brûlées'], $payload[1]['game']);
    }

    public function testListRequiresAnAccessToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/me/characters');

        self::assertResponseStatusCodeSame(401);
    }

    public function testClaimLinksAnUnownedCharacterToTheUser(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne()->_real();
        CharacterFactory::createOne([
            'name' => 'Zora',
            'game' => GameFactory::createOne(),
            'kind' => KindFactory::createOne(),
            'token' => 'zora-token',
        ]);

        $this->claim($client, $this->issueApiToken($user), 'zora-token');

        self::assertResponseIsSuccessful();
        self::assertSame('Zora', $this->json($client)['name']);
        self::assertSame($user->getId(), $this->character('zora-token')->getUser()?->getId());

        // The token routes keep working after the claim.
        $client->request('GET', '/api/characters/zora-token/export');
        self::assertResponseIsSuccessful();
    }

    public function testClaimIsIdempotent(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne()->_real();
        CharacterFactory::createOne(['user' => $user, 'game' => GameFactory::createOne(), 'token' => 'zora-token']);

        $this->claim($client, $this->issueApiToken($user), 'zora-token');

        self::assertResponseIsSuccessful();
        self::assertSame($user->getId(), $this->character('zora-token')->getUser()?->getId());
    }

    public function testClaimRefusesACharacterOwnedByAnotherAccount(): void
    {
        $client = static::createClient();
        $owner = UserFactory::createOne()->_real();
        CharacterFactory::createOne(['user' => $owner, 'game' => GameFactory::createOne(), 'token' => 'zora-token']);

        $this->claim($client, $this->issueApiToken(UserFactory::createOne()->_real()), 'zora-token');

        self::assertResponseStatusCodeSame(409);
        self::assertSame($owner->getId(), $this->character('zora-token')->getUser()?->getId());
    }

    public function testClaimUnknownTokenReturns404(): void
    {
        $client = static::createClient();

        $this->claim($client, $this->issueApiToken(UserFactory::createOne()->_real()), 'unknown-token');

        self::assertResponseStatusCodeSame(404);
    }

    public function testClaimRequiresAToken(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/me/characters/claim',
            server: ['CONTENT_TYPE' => 'application/json'] + $this->bearer($this->issueApiToken(UserFactory::createOne()->_real())),
            content: '{}',
        );
        self::assertResponseStatusCodeSame(400);

        $client->request('POST', '/api/me/characters/claim', server: ['CONTENT_TYPE' => 'application/json'], content: '{"token":"x"}');
        self::assertResponseStatusCodeSame(401);
    }

    private function claim(KernelBrowser $client, string $accessToken, string $characterToken): void
    {
        $client->request(
            'POST',
            '/api/me/characters/claim',
            server: ['CONTENT_TYPE' => 'application/json'] + $this->bearer($accessToken),
            content: (string) json_encode(['token' => $characterToken]),
        );
    }

    private function character(string $token): Character
    {
        $character = self::getContainer()->get(CharacterRepository::class)->findOneByToken($token);
        self::assertInstanceOf(Character::class, $character);

        return $character;
    }

    /**
     * @return array<mixed>
     */
    private function json(KernelBrowser $client): array
    {
        return json_decode((string) $client->getResponse()->getContent(), true);
    }
}
