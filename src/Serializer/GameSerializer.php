<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\GameMembership;

/**
 * A game as seen by one of its players (GET /api/games, POST /api/games/join).
 */
class GameSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serializeForPlayer(GameMembership $membership): array
    {
        $game = $membership->getGame();

        return [
            'id' => $game->getId(),
            'name' => $game->getName(),
            // Username only (null when not set): the display name falls back to the email, which players must not see.
            'gameMaster' => ['username' => $game->getGameMaster()->getUsername()],
            'joinedAt' => $membership->getJoinedAt()->format(\DateTimeInterface::ATOM),
            // Archived: closed and hidden in the back office, its characters still work.
            'archived' => $game->isArchived(),
        ];
    }
}
