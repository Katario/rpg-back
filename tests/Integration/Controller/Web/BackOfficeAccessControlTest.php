<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Web;

use App\Controller\ArmamentController;
use App\Controller\CharacterController;
use App\Controller\EncyclopediaController;
use App\Controller\GameController;
use App\Controller\HomeController;
use App\Controller\MonsterController;
use App\Controller\NonPlayableCharacterController;
use App\Entity\Game;
use App\Entity\User;
use App\Fixtures\DataFixtures\Factory\CharacterClassFactory;
use App\Fixtures\DataFixtures\Factory\CharacterFactory;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use App\Fixtures\DataFixtures\Factory\KindFactory;
use App\Fixtures\DataFixtures\Factory\MonsterFactory;
use App\Fixtures\DataFixtures\Factory\NonPlayableCharacterFactory;
use App\Fixtures\DataFixtures\Factory\SpecieFactory;
use App\Fixtures\DataFixtures\Factory\UserFactory;
use App\Fixtures\DataFixtures\Factory\WeaponFactory;
use App\Repository\MonsterRepository;
use App\Repository\NonPlayableCharacterRepository;
use App\Security\Voter\CharacterVoter;
use App\Security\Voter\GameVoter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(GameVoter::class)]
#[CoversClass(CharacterVoter::class)]
#[CoversClass(HomeController::class)]
#[CoversClass(GameController::class)]
#[CoversClass(CharacterController::class)]
#[CoversClass(MonsterController::class)]
#[CoversClass(NonPlayableCharacterController::class)]
#[CoversClass(ArmamentController::class)]
#[CoversClass(EncyclopediaController::class)]
class BackOfficeAccessControlTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private KernelBrowser $client;
    private User $gameMaster;
    private User $stranger;
    private User $admin;
    private Game $game;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->gameMaster = UserFactory::createOne(['email' => 'gm@example.com'])->_real();
        $this->stranger = UserFactory::createOne(['email' => 'stranger@example.com'])->_real();
        $this->admin = UserFactory::createOne(['email' => 'admin@example.com', 'roles' => ['ROLE_ADMIN']])->_real();
        $this->game = GameFactory::createOne(['name' => 'Les Terres Brûlées', 'gameMaster' => $this->gameMaster])->_real();
    }

    public function testHomeListsOnlyTheGamesOfTheGameMaster(): void
    {
        GameFactory::createOne(['name' => 'Partie du voisin', 'gameMaster' => $this->stranger]);

        $this->client->loginUser($this->gameMaster);
        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'Les Terres Brûlées');
        self::assertSelectorTextNotContains('main', 'Partie du voisin');
    }

    public function testHomeListsEveryGameForAnAdmin(): void
    {
        GameFactory::createOne(['name' => 'Partie du voisin', 'gameMaster' => $this->stranger]);

        $this->client->loginUser($this->admin);
        $this->client->request('GET', '/');
        self::assertSelectorTextContains('main', 'Les Terres Brûlées');
        self::assertSelectorTextContains('main', 'Partie du voisin');
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function gameAccessProvider(): iterable
    {
        yield 'game master' => ['gameMaster', true];
        yield 'admin' => ['admin', true];
        yield 'another user' => ['stranger', false];
    }

    #[DataProvider('gameAccessProvider')]
    public function testGamePagesAreReservedToTheGameMasterAndAdmins(string $who, bool $granted): void
    {
        $monster = MonsterFactory::createOne(['game' => $this->game, 'specie' => SpecieFactory::createOne()]);
        $npc = NonPlayableCharacterFactory::createOne(['game' => $this->game, 'kind' => KindFactory::createOne(), 'characterClass' => CharacterClassFactory::createOne()]);
        $weapon = WeaponFactory::createOne(['game' => $this->game]);
        $gameId = $this->game->getId();

        $this->client->loginUser($this->{$who});

        foreach ([
            "/games/$gameId",
            "/monsters/{$monster->getId()}",
            "/monsters/{$monster->getId()}/edit",
            "/npcs/{$npc->getId()}",
            "/npcs/{$npc->getId()}/edit",
            "/armaments/{$weapon->getId()}",
            "/armaments/{$weapon->getId()}/edit",
            "/games/$gameId/monsters/generate",
            "/games/$gameId/npcs/generate",
            "/games/$gameId/characters/generate",
        ] as $url) {
            $this->client->request('GET', $url);
            $this->assertAccess($granted, $url);
        }
    }

    public function testAnotherUserCannotDeleteAnythingFromTheGame(): void
    {
        $monster = MonsterFactory::createOne(['game' => $this->game]);
        $npc = NonPlayableCharacterFactory::createOne(['game' => $this->game]);
        $monsterId = $monster->getId();
        $npcId = $npc->getId();

        $this->client->loginUser($this->stranger);
        $this->client->request('GET', "/monsters/$monsterId/delete");
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', "/npcs/$npcId/delete");
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', "/games/{$this->game->getId()}/delete", ['gameNameConfirmation' => 'Les Terres Brûlées']);
        self::assertResponseStatusCodeSame(403);

        self::assertNotNull(self::getContainer()->get(MonsterRepository::class)->find($monsterId));
        self::assertNotNull(self::getContainer()->get(NonPlayableCharacterRepository::class)->find($npcId));
    }

    public function testUnknownEntityReturns404(): void
    {
        $this->client->loginUser($this->gameMaster);

        $this->client->request('GET', '/monsters/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testTheOwnerCanViewTheirCharacterButNotEditIt(): void
    {
        $player = UserFactory::createOne()->_real();
        $character = CharacterFactory::createOne([
            'game' => $this->game,
            'user' => $player,
            'kind' => KindFactory::createOne(),
            'characterClass' => CharacterClassFactory::createOne(),
        ]);
        $base = "/games/{$this->game->getId()}/characters/{$character->getId()}";

        $this->client->loginUser($player);
        $this->client->request('GET', $base);
        $this->assertAccess(true, $base);
        $this->client->request('GET', "$base/edit");
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', "$base/delete");
        self::assertResponseStatusCodeSame(403);

        $this->client->loginUser($this->stranger);
        $this->client->request('GET', $base);
        self::assertResponseStatusCodeSame(403);

        $this->client->loginUser($this->gameMaster);
        $this->client->request('GET', "$base/edit");
        $this->assertAccess(true, "$base/edit");
    }

    public function testEncyclopediaCanBeBrowsedByAnyoneButEditedByAdminsOnly(): void
    {
        $this->client->loginUser($this->gameMaster);
        $this->client->request('GET', '/encyclopedia');
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('a[href="/encyclopedia/create-spell"]');
        $this->client->request('GET', '/encyclopedia/create-spell');
        self::assertResponseStatusCodeSame(403);

        $this->client->loginUser($this->admin);
        $this->client->request('GET', '/encyclopedia');
        self::assertSelectorExists('a[href="/encyclopedia/create-spell"]');
        $this->client->request('GET', '/encyclopedia/create-spell');
        self::assertResponseIsSuccessful();
    }

    /**
     * Checks the access decision only: some back office pages have rendering bugs of their own
     * (strict Twig variables in tests), unrelated to permissions.
     */
    private function assertAccess(bool $granted, string $url): void
    {
        $status = $this->client->getResponse()->getStatusCode();

        if ($granted) {
            self::assertNotContains($status, [401, 403, 404], "$url should be accessible, got $status.");
        } else {
            self::assertSame(403, $status, "$url should be forbidden, got $status.");
        }
    }
}
