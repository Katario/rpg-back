<?php

declare(strict_types=1);

namespace App\Game;

use App\Entity\Game;
use App\Entity\User;
use App\Game\Exception\GameMasterCannotJoinException;
use App\Game\Exception\InviteCodeNotFoundException;
use App\Game\Exception\NotAMemberException;
use App\Repository\CharacterRepository;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Players of a game and its invite code (see docs/game.md). Every method flushes.
 */
class GameMembershipManager
{
    private const int MAX_CODE_ATTEMPTS = 5;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameRepository $gameRepository,
        private readonly CharacterRepository $characterRepository,
    ) {
    }

    /**
     * Creates the invite code, or replaces it (the previous one stops working).
     *
     * @return string the new code, normalized
     */
    public function generateInviteCode(Game $game): string
    {
        if ($game->isArchived()) {
            throw new \LogicException('An archived game cannot be opened to new players.');
        }

        // ~10^12 possible codes: a collision is unlikely, the unique index is the last safety net.
        $attempts = 0;
        do {
            $code = InviteCode::generate();
        } while (null !== $this->gameRepository->findOneBy(['inviteCode' => $code]) && ++$attempts < self::MAX_CODE_ATTEMPTS);

        $game->setInviteCode($code);
        $this->entityManager->flush();

        return $code;
    }

    /**
     * Closes the game: nobody can join it until a new code is generated.
     */
    public function deleteInviteCode(Game $game): void
    {
        $game->setInviteCode(null);
        $this->entityManager->flush();
    }

    /**
     * Idempotent: a player who already joined gets the game back.
     *
     * @param string $code as typed by the player (case and dashes do not matter)
     *
     * @throws InviteCodeNotFoundException   no open game has this code (also for a malformed code)
     * @throws GameMasterCannotJoinException
     */
    public function join(User $user, string $code): Game
    {
        $normalized = InviteCode::normalize($code);
        $game = null === $normalized ? null : $this->gameRepository->findOneBy(['inviteCode' => $normalized]);

        // Archived games have no code, the check only guards against inconsistent data.
        if (null === $game || $game->isArchived()) {
            throw new InviteCodeNotFoundException();
        }

        if ($game->isGameMaster($user)) {
            throw new GameMasterCannotJoinException();
        }

        $game->addMember($user);
        $this->entityManager->flush();

        return $game;
    }

    /**
     * @throws NotAMemberException
     */
    public function leave(Game $game, User $user): void
    {
        $this->removeMembership($game, $user);
    }

    /**
     * Removal by the game master.
     *
     * @throws NotAMemberException
     */
    public function remove(Game $game, User $player): void
    {
        $this->removeMembership($game, $player);
    }

    /**
     * The player keeps their characters of the game, but they are disabled (read-only); the game master can enable
     * them again. Rejoining the game does not enable them.
     */
    private function removeMembership(Game $game, User $user): void
    {
        if (!$game->hasMember($user)) {
            throw new NotAMemberException();
        }

        $game->removeMember($user);
        foreach ($this->characterRepository->findBy(['game' => $game, 'user' => $user]) as $character) {
            $character->disable();
        }

        $this->entityManager->flush();
    }
}
