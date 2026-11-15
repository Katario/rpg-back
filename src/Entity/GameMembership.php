<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GameMembershipRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A player who joined a game (see docs/game.md). The game master is not a member: they stay
 * `Game.gameMaster`. Removing or leaving deletes the membership, never the player's characters.
 */
#[ORM\Entity(repositoryClass: GameMembershipRepository::class)]
#[ORM\UniqueConstraint(name: 'game_membership_game_user', columns: ['game_id', 'user_id'])]
class GameMembership
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    // Assigned by Doctrine (generated value), which PHPStan cannot see without the Doctrine extension.
    private ?int $id = null; // @phpstan-ignore property.unusedType

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $joinedAt;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'memberships')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Game $game,
        #[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private User $user,
        \DateTimeImmutable $now = new \DateTimeImmutable(),
    ) {
        $this->joinedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getJoinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }
}
