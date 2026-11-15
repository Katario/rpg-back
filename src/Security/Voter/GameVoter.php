<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Being;
use App\Entity\Equipment;
use App\Entity\Game;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * - MANAGE: managing a game and everything in it (characters, monsters, NPCs, armaments): its game master, or an admin.
 *   The subject is the game itself or any entity that belongs to one.
 * - PLAY: playing in a game (e.g. importing a character into it): its players and its game master, while the game is
 *   not archived. The subject is the game.
 *
 * @extends Voter<string, Game|Being|Equipment>
 */
class GameVoter extends Voter
{
    public const string MANAGE = 'GAME_MANAGE';
    public const string PLAY = 'GAME_PLAY';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return match ($attribute) {
            self::MANAGE => $subject instanceof Game || $subject instanceof Being || $subject instanceof Equipment,
            self::PLAY => $subject instanceof Game,
            default => false,
        };
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $game = $subject instanceof Game ? $subject : $subject->getGame();

        if (self::PLAY === $attribute) {
            return !$game->isArchived() && ($game->isGameMaster($user) || $game->hasMember($user));
        }

        return $game->isGameMaster($user) || $this->accessDecisionManager->decide($token, ['ROLE_ADMIN']);
    }
}
