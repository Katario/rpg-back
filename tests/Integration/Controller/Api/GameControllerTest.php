<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Api;

use App\Controller\Api\GameController;
use App\Entity\Character;
use App\Entity\Game;
use App\Entity\User;
use App\Fixtures\DataFixtures\Factory\CharacterFactory;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use App\Fixtures\DataFixtures\Factory\UserFactory;
use App\Repository\GameMembershipRepository;
use App\Serializer\GameSerializer;
use App\Tests\Support\IssuesApiTokens;
use App\Tests\Support\ResetsRateLimiters;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(GameController::class)]
#[CoversClass(GameSerializer::class)]
#[CoversClass(GameMembershipRepository::class)]
class GameControllerTest extends WebTestCase
{
    use Factories;
    use IssuesApiTokens;
    use ResetDatabase;
    use ResetsRateLimiters;

    private KernelBrowser $client;
    private User $player;
    private string $token;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->player = UserFactory::createOne(['email' => 'player@example.com'])->_real();
        $this->token = $this->issueApiToken($this->player);
    }

    public function testListsOnlyTheGamesThePlayerPlaysIn(): void
    {
        $gameMaster = UserFactory::createOne(['username' => 'Le MJ'])->_real();
        $played = GameFactory::createOne(['name' => 'Les Terres Brûlées', 'gameMaster' => $gameMaster])->_real();
        $archived = GameFactory::createOne(['name' => 'Ancienne campagne'])->_real();
        $played->addMember($this->player, new \DateTimeImmutable('2026-11-15 10:00:00'));
        $archived->addMember($this->player);
        $archived->archive();
        GameFactory::createOne(['name' => 'Partie que je mène', 'gameMaster' => $this->player]);
        GameFactory::createOne(['name' => 'Partie des autres']);
        $this->entityManager()->flush();

        $this->client->request('GET', '/api/games', server: $this->bearer($this->token));

        self::assertResponseIsSuccessful();
        $games = $this->json();
        self::assertSame(['Ancienne campagne', 'Les Terres Brûlées'], array_column($games, 'name'));
        self::assertTrue($games[0]['archived']);
        self::assertArrayNotHasKey('email', $games[0]['gameMaster']);
        self::assertSame([
            'id' => $played->getId(),
            'name' => 'Les Terres Brûlées',
            'gameMaster' => ['username' => 'Le MJ'],
            'joinedAt' => '2026-11-15T10:00:00+00:00',
            'archived' => false,
        ], $games[1]);
    }

    public function testJoinWithTheCodeAsTyped(): void
    {
        $game = $this->openGame('K7QM3XPA');

        $this->join('k7qm-3xpa');
        self::assertResponseIsSuccessful();
        self::assertSame($game->getId(), $this->json()['id']);

        // Joining again is harmless.
        $this->join('K7QM3XPA');
        self::assertResponseIsSuccessful();
        self::assertCount(1, self::getContainer()->get(GameMembershipRepository::class)->findByPlayer($this->player));
    }

    public function testJoinErrors(): void
    {
        $this->openGame('K7QM3XPA', gameMaster: $this->player);

        $this->join('ZZZZ-ZZZZ');
        self::assertResponseStatusCodeSame(404);
        self::assertSame(['error' => 'No open game matches this invite code.'], $this->json());

        $this->join('K7QM3XPA');
        self::assertResponseStatusCodeSame(409);

        $this->join('  ');
        self::assertResponseStatusCodeSame(400);
    }

    public function testJoinIsRateLimited(): void
    {
        for ($i = 0; $i < 10; ++$i) {
            $this->join('ZZZZ-ZZZZ');
            self::assertResponseStatusCodeSame(404);
        }

        $this->join('ZZZZ-ZZZZ');

        self::assertResponseStatusCodeSame(429);
        self::assertResponseHasHeader('Retry-After');
    }

    public function testLeaveKeepsTheCharactersButDisablesThem(): void
    {
        $game = $this->openGame('K7QM3XPA');
        $game->addMember($this->player);
        $this->entityManager()->flush();
        $character = CharacterFactory::createOne(['game' => $game, 'user' => $this->player])->_real();

        $this->client->request('POST', "/api/games/{$game->getId()}/leave", server: $this->bearer($this->token));

        self::assertResponseStatusCodeSame(204);
        $this->entityManager()->clear();
        $character = $this->entityManager()->find(Character::class, $character->getId());
        self::assertTrue($character?->isDisabled());
        self::assertSame($this->player->getId(), $character->getUser()?->getId());
    }

    public function testLeaveAGameThePlayerIsNotInIsNotFound(): void
    {
        $game = $this->openGame('K7QM3XPA');

        $this->client->request('POST', "/api/games/{$game->getId()}/leave", server: $this->bearer($this->token));
        self::assertResponseStatusCodeSame(404);

        $this->client->request('POST', '/api/games/999999/leave', server: $this->bearer($this->token));
        self::assertResponseStatusCodeSame(404);
    }

    public function testRoutesRequireAnAccount(): void
    {
        $this->client->request('GET', '/api/games');
        self::assertResponseStatusCodeSame(401);

        $this->client->request('POST', '/api/games/join', server: ['CONTENT_TYPE' => 'application/json'], content: '{"code":"K7QM3XPA"}');
        self::assertResponseStatusCodeSame(401);
    }

    private function openGame(string $code, ?User $gameMaster = null): Game
    {
        return GameFactory::createOne(['inviteCode' => $code] + ($gameMaster ? ['gameMaster' => $gameMaster] : []))->_real();
    }

    private function join(string $code): void
    {
        $this->client->request(
            'POST',
            '/api/games/join',
            server: ['CONTENT_TYPE' => 'application/json'] + $this->bearer($this->token),
            content: (string) json_encode(['code' => $code]),
        );
    }

    /**
     * @return array<mixed>
     */
    private function json(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true);
    }

    private function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }
}
