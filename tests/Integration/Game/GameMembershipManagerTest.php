<?php

declare(strict_types=1);

namespace App\Tests\Integration\Game;

use App\Entity\Character;
use App\Entity\Game;
use App\Entity\User;
use App\Fixtures\DataFixtures\Factory\CharacterFactory;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use App\Fixtures\DataFixtures\Factory\UserFactory;
use App\Game\Exception\GameMasterCannotJoinException;
use App\Game\Exception\InviteCodeNotFoundException;
use App\Game\Exception\NotAMemberException;
use App\Game\GameMembershipManager;
use App\Game\InviteCode;
use App\Repository\GameMembershipRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(GameMembershipManager::class)]
#[CoversClass(InviteCodeNotFoundException::class)]
#[CoversClass(GameMasterCannotJoinException::class)]
#[CoversClass(NotAMemberException::class)]
class GameMembershipManagerTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private GameMembershipManager $manager;
    private User $gameMaster;
    private Game $game;

    protected function setUp(): void
    {
        $this->manager = self::getContainer()->get(GameMembershipManager::class);
        $this->gameMaster = UserFactory::createOne()->_real();
        $this->game = GameFactory::createOne(['gameMaster' => $this->gameMaster])->_real();
    }

    public function testGenerateReplacesTheCodeAndDeleteClosesTheGame(): void
    {
        $first = $this->manager->generateInviteCode($this->game);
        $second = $this->manager->generateInviteCode($this->game);

        self::assertSame($first, InviteCode::normalize($first));
        self::assertNotSame($first, $second);
        self::assertSame($second, $this->reloadedGame()->getInviteCode());

        $this->manager->deleteInviteCode($this->game);

        self::assertNull($this->reloadedGame()->getInviteCode());
    }

    public function testAnArchivedGameCannotGetACode(): void
    {
        $this->game->archive();

        $this->expectException(\LogicException::class);
        $this->manager->generateInviteCode($this->game);
    }

    public function testJoinWithTheCodeAsTypedIsIdempotent(): void
    {
        $code = $this->manager->generateInviteCode($this->game);
        $player = UserFactory::createOne()->_real();

        $joined = $this->manager->join($player, strtolower(InviteCode::format($code)));
        $this->manager->join($player, $code);

        self::assertSame($this->game, $joined);
        self::assertCount(1, $this->memberships());
        self::assertTrue($this->reloadedGame()->hasMember($player));
    }

    public function testJoinRefusesAnUnknownOrMalformedCode(): void
    {
        $this->manager->generateInviteCode($this->game);
        $player = UserFactory::createOne()->_real();

        foreach (['ZZZZ-ZZZZ', 'not a code'] as $code) {
            try {
                $this->manager->join($player, $code);
                self::fail("\"$code\" should not match any game.");
            } catch (InviteCodeNotFoundException) {
            }
        }

        self::assertCount(0, $this->memberships());
    }

    public function testADeletedCodeNoLongerWorks(): void
    {
        $code = $this->manager->generateInviteCode($this->game);
        $this->manager->deleteInviteCode($this->game);

        $this->expectException(InviteCodeNotFoundException::class);
        $this->manager->join(UserFactory::createOne()->_real(), $code);
    }

    public function testTheGameMasterCannotJoin(): void
    {
        $code = $this->manager->generateInviteCode($this->game);

        $this->expectException(GameMasterCannotJoinException::class);
        $this->manager->join($this->gameMaster, $code);
    }

    public function testLeavingDisablesOnlyThePlayersCharactersOfThatGame(): void
    {
        $player = UserFactory::createOne()->_real();
        $other = UserFactory::createOne()->_real();
        $this->game->addMember($player);
        $this->game->addMember($other);
        $this->flush();

        $mine = $this->character($this->game, $player);
        $othersCharacter = $this->character($this->game, $other);
        $mineElsewhere = $this->character(GameFactory::createOne()->_real(), $player);

        $this->manager->leave($this->game, $player);

        self::assertFalse($this->reloadedGame()->hasMember($player));
        self::assertTrue($this->reloadedGame()->hasMember($other));
        $mine = $this->reloaded($mine);
        self::assertTrue($mine->isDisabled());
        self::assertSame($player->getId(), $mine->getUser()?->getId(), 'The player keeps their character.');
        self::assertFalse($this->reloaded($othersCharacter)->isDisabled());
        self::assertFalse($this->reloaded($mineElsewhere)->isDisabled());
    }

    public function testRemovalByTheGameMasterDisablesTheCharactersToo(): void
    {
        $player = UserFactory::createOne()->_real();
        $this->game->addMember($player);
        $this->flush();
        $character = $this->character($this->game, $player);

        $this->manager->remove($this->game, $player);

        self::assertCount(0, $this->memberships());
        self::assertTrue($this->reloaded($character)->isDisabled());
    }

    public function testOnlyAPlayerCanLeave(): void
    {
        $this->expectException(NotAMemberException::class);
        $this->manager->leave($this->game, UserFactory::createOne()->_real());
    }

    private function character(Game $game, User $owner): Character
    {
        return CharacterFactory::createOne(['game' => $game, 'user' => $owner])->_real();
    }

    private function reloaded(Character $character): Character
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        return $entityManager->find(Character::class, $character->getId()) ?? throw new \LogicException('Character not found.');
    }

    private function reloadedGame(): Game
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $this->game = $entityManager->find(Game::class, $this->game->getId()) ?? throw new \LogicException('Game not found.');

        return $this->game;
    }

    /**
     * @return list<object>
     */
    private function memberships(): array
    {
        return self::getContainer()->get(GameMembershipRepository::class)->findBy(['game' => $this->game->getId()]);
    }

    private function flush(): void
    {
        self::getContainer()->get(EntityManagerInterface::class)->flush();
    }
}
