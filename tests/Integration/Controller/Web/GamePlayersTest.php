<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Web;

use App\Controller\CharacterController;
use App\Controller\GamePlayerController;
use App\Entity\Character;
use App\Entity\Game;
use App\Entity\User;
use App\Fixtures\DataFixtures\Factory\CharacterClassFactory;
use App\Fixtures\DataFixtures\Factory\CharacterFactory;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use App\Fixtures\DataFixtures\Factory\KindFactory;
use App\Fixtures\DataFixtures\Factory\UserFactory;
use App\Game\InviteCode;
use App\Twig\GameExtension;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(GamePlayerController::class)]
#[CoversClass(CharacterController::class)]
#[CoversClass(GameExtension::class)]
class GamePlayersTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private KernelBrowser $client;
    private User $gameMaster;
    private Game $game;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->gameMaster = UserFactory::createOne(['email' => 'gm@example.com'])->_real();
        $this->game = GameFactory::createOne(['name' => 'Les Terres Brûlées', 'gameMaster' => $this->gameMaster])->_real();
        $this->client->loginUser($this->gameMaster);
    }

    public function testTheGameMasterCreatesReplacesAndDeletesTheInviteCode(): void
    {
        $crawler = $this->client->request('GET', $this->gamePage());
        self::assertSelectorTextContains('#game-invitation-title + p', 'La partie est fermée');

        $this->client->submit($crawler->selectButton('Créer un code')->form());
        $first = $this->reloadedGame()->getInviteCode();
        self::assertNotNull($first);
        self::assertResponseRedirects($this->gamePage());
        $crawler = $this->client->followRedirect();
        self::assertSelectorTextContains('main', InviteCode::format($first));

        $this->client->submit($crawler->selectButton('Remplacer le code')->form());
        $crawler = $this->client->followRedirect();
        self::assertNotSame($first, $this->reloadedGame()->getInviteCode());

        $this->client->submit($crawler->selectButton('Supprimer le code')->form());
        self::assertNull($this->reloadedGame()->getInviteCode());
    }

    public function testRemovingAPlayerDisablesTheirCharacters(): void
    {
        $player = UserFactory::createOne(['email' => 'player@example.com'])->_real();
        $this->game->addMember($player);
        $this->flush();
        $character = $this->character($player);

        $crawler = $this->client->request('GET', $this->gamePage());
        self::assertSelectorTextContains('#game-players-title + ul', $player->getDisplayName());

        $this->client->submit($crawler->selectButton('Retirer')->form());

        self::assertResponseRedirects($this->gamePage());
        self::assertFalse($this->reloadedGame()->hasMember($player));
        self::assertTrue($this->reloaded($character)->isDisabled());
    }

    public function testTheGameMasterDisablesAndEnablesACharacter(): void
    {
        $character = $this->character(null);

        $crawler = $this->client->request('GET', $this->gamePage());
        $this->client->submit($crawler->selectButton('Désactiver')->form());
        self::assertTrue($this->reloaded($character)->isDisabled());

        $crawler = $this->client->followRedirect();
        self::assertSelectorTextContains('main', 'Désactivée');
        $this->client->submit($crawler->selectButton('Réactiver')->form());
        self::assertFalse($this->reloaded($character)->isDisabled());
    }

    public function testActionsNeedAValidCsrfToken(): void
    {
        $this->client->request('POST', $this->gamePage().'/invite-code', ['_token' => 'forged']);

        self::assertNull($this->reloadedGame()->getInviteCode());
    }

    public function testOnlyTheGameMasterManagesPlayers(): void
    {
        $player = UserFactory::createOne()->_real();
        $this->game->addMember($player);
        $this->flush();

        // The CSRF token is checked first: without one, the request is refused before the permission check.
        $this->client->loginUser($player);
        $this->client->request('GET', $this->gamePage());
        self::assertResponseStatusCodeSame(403);

        $this->client->request('POST', $this->gamePage().'/invite-code');
        self::assertFalse($this->client->getResponse()->isSuccessful());
        $this->client->request('POST', $this->gamePage()."/players/{$player->getId()}/remove");
        self::assertFalse($this->client->getResponse()->isSuccessful());

        self::assertNull($this->reloadedGame()->getInviteCode());
        self::assertTrue($this->reloadedGame()->hasMember($player));
    }

    private function character(?User $owner): Character
    {
        return CharacterFactory::createOne([
            'game' => $this->game,
            'user' => $owner,
            'kind' => KindFactory::createOne(),
            'characterClass' => CharacterClassFactory::createOne(),
        ])->_real();
    }

    private function gamePage(): string
    {
        return '/games/'.$this->game->getId();
    }

    private function reloadedGame(): Game
    {
        $this->entityManager()->clear();

        return $this->entityManager()->find(Game::class, $this->game->getId()) ?? throw new \LogicException('Game not found.');
    }

    private function reloaded(Character $character): Character
    {
        $this->entityManager()->clear();

        return $this->entityManager()->find(Character::class, $character->getId()) ?? throw new \LogicException('Character not found.');
    }

    private function flush(): void
    {
        $this->entityManager()->flush();
    }

    private function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }
}
