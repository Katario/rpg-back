<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller\Web;

use App\Controller\GameController;
use App\Controller\GamePlayerController;
use App\Controller\HomeController;
use App\Entity\Game;
use App\Entity\User;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use App\Fixtures\DataFixtures\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(GameController::class)]
#[CoversClass(HomeController::class)]
#[CoversClass(GamePlayerController::class)]
class GameArchiveTest extends WebTestCase
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

    public function testTheGameMasterArchivesAndUnarchivesTheGame(): void
    {
        $this->game->setInviteCode('K7QM3XPA');
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', $this->gamePage());
        $this->client->submit($crawler->selectButton('Archiver la partie')->form());

        self::assertResponseRedirects($this->gamePage());
        $game = $this->reloadedGame();
        self::assertTrue($game->isArchived());
        self::assertNull($game->getInviteCode(), 'Archiving closes the game.');

        $crawler = $this->client->followRedirect();
        self::assertSelectorTextContains('[role=status]', 'Partie archivée');
        self::assertSelectorNotExists('#game-invitation-title');

        $this->client->submit($crawler->selectButton('Désarchiver la partie')->form());
        self::assertFalse($this->reloadedGame()->isArchived());
    }

    public function testHomeListsArchivedGamesSeparately(): void
    {
        GameFactory::createOne(['name' => 'Vieille campagne', 'gameMaster' => $this->gameMaster])->_real()->archive();
        $this->entityManager()->flush();

        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main > ul', 'Les Terres Brûlées');
        self::assertSelectorTextNotContains('main > ul', 'Vieille campagne');
        self::assertSelectorTextContains('#archived-games-title + ul', 'Vieille campagne');
    }

    public function testAnArchivedGameCannotGetAnInviteCode(): void
    {
        $crawler = $this->client->request('GET', $this->gamePage());
        $token = $crawler->filter('input[name=_token]')->first()->attr('value'); // invitation form, rendered first
        $this->game->archive();
        $this->entityManager()->flush();

        $this->client->request('POST', $this->gamePage().'/invite-code', ['_token' => $token]);

        self::assertResponseStatusCodeSame(409);
        self::assertNull($this->reloadedGame()->getInviteCode());
    }

    public function testOnlyTheGameMasterArchives(): void
    {
        $this->client->loginUser(UserFactory::createOne()->_real());

        $this->client->request('POST', $this->gamePage().'/archive');

        self::assertFalse($this->client->getResponse()->isSuccessful());
        self::assertFalse($this->reloadedGame()->isArchived());
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

    private function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }
}
