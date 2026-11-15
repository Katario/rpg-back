<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Character;
use App\Entity\Game;
use App\Entity\User;
use App\Security\Voter\GameVoter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[CoversClass(GameVoter::class)]
class GameVoterTest extends TestCase
{
    private User $gameMaster;
    private User $player;
    private User $stranger;
    private Game $game;

    protected function setUp(): void
    {
        $this->gameMaster = new User()->setEmail('gm@example.com');
        $this->player = new User()->setEmail('player@example.com');
        $this->stranger = new User()->setEmail('stranger@example.com');
        $this->game = new Game()->setName('First Game')->setGameMaster($this->gameMaster);
        $this->game->addMember($this->player);
    }

    public function testPlayersAndTheGameMasterCanPlay(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($this->gameMaster, GameVoter::PLAY));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($this->player, GameVoter::PLAY));
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($this->stranger, GameVoter::PLAY));
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($this->stranger, GameVoter::PLAY, admin: true));
    }

    public function testNobodyPlaysInAnArchivedGame(): void
    {
        $this->game->archive();

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($this->gameMaster, GameVoter::PLAY));
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($this->player, GameVoter::PLAY));
    }

    public function testOnlyTheGameMasterOrAnAdminManages(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($this->gameMaster, GameVoter::MANAGE));
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($this->player, GameVoter::MANAGE));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($this->stranger, GameVoter::MANAGE, admin: true));

        $character = new Character()->setGame($this->game);
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($this->gameMaster, GameVoter::MANAGE, $character));
    }

    public function testAnonymousAndUnsupportedSubjects(): void
    {
        $voter = new GameVoter($this->createStub(AccessDecisionManagerInterface::class));

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote(new NullToken(), $this->game, [GameVoter::PLAY]));
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->vote($this->player, GameVoter::PLAY, new Character()));
    }

    private function vote(User $user, string $attribute, ?object $subject = null, bool $admin = false): int
    {
        $accessDecisionManager = $this->createStub(AccessDecisionManagerInterface::class);
        $accessDecisionManager->method('decide')->willReturn($admin);

        return new GameVoter($accessDecisionManager)
            ->vote(new UsernamePasswordToken($user, 'main'), $subject ?? $this->game, [$attribute]);
    }
}
