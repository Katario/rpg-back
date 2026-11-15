<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Game;
use App\Entity\GameMembership;
use App\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Game::class)]
#[CoversClass(GameMembership::class)]
class GameTest extends TestCase
{
    public function testAddMemberIsIdempotent(): void
    {
        $game = $this->game();
        $player = new User();
        $now = new \DateTimeImmutable('2026-11-15 12:00:00');

        $membership = $game->addMember($player, $now);

        self::assertSame($membership, $game->addMember($player, $now->modify('+1 day')));
        self::assertCount(1, $game->getMemberships());
        self::assertTrue($game->hasMember($player));
        self::assertSame($game, $membership->getGame());
        self::assertSame($player, $membership->getUser());
        self::assertEquals($now, $membership->getJoinedAt());
    }

    public function testTheGameMasterCannotJoinTheirOwnGame(): void
    {
        $gameMaster = new User();
        $game = $this->game($gameMaster);

        self::assertTrue($game->isGameMaster($gameMaster));

        $this->expectException(\LogicException::class);
        $game->addMember($gameMaster);
    }

    public function testRemoveMember(): void
    {
        $game = $this->game();
        $player = new User();
        $other = new User();
        $game->addMember($player);
        $game->addMember($other);

        $game->removeMember($player);
        $game->removeMember(new User()); // not a member: no-op

        self::assertFalse($game->hasMember($player));
        self::assertTrue($game->hasMember($other));
        self::assertCount(1, $game->getMemberships());
    }

    public function testUsersAreComparedByIdentifierToo(): void
    {
        $game = $this->game();
        $game->addMember($this->userWithId(7));

        // Another instance of the same row, e.g. loaded by a separate entity manager.
        self::assertTrue($game->hasMember($this->userWithId(7)));
        self::assertFalse($game->hasMember($this->userWithId(8)));
        self::assertFalse($game->hasMember(new User()));
    }

    public function testArchivingClosesTheGame(): void
    {
        $now = new \DateTimeImmutable('2026-11-15 12:00:00');
        $game = $this->game()->setInviteCode('K7QM3XPA');

        self::assertFalse($game->isArchived());

        $game->archive($now);
        $game->archive($now->modify('+1 day')); // keeps the first date

        self::assertTrue($game->isArchived());
        self::assertEquals($now, $game->getArchivedAt());
        self::assertNull($game->getInviteCode());

        $game->unarchive();

        self::assertFalse($game->isArchived());
        self::assertNull($game->getInviteCode(), 'Unarchiving does not reopen the game.');
    }

    private function game(User $gameMaster = new User()): Game
    {
        return new Game()->setName('First Game')->setGameMaster($gameMaster);
    }

    private function userWithId(int $id): User
    {
        $user = new User();
        new \ReflectionProperty(User::class, 'id')->setValue($user, $id);

        return $user;
    }
}
