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
 * Managing a game and everything in it (characters, monsters, NPCs, armaments): its game master, or an admin.
 *
 * The subject is the game itself or any entity that belongs to one.
 *
 * @extends Voter<string, Game|Being|Equipment>
 */
class GameVoter extends Voter
{
    public const string MANAGE = 'GAME_MANAGE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::MANAGE === $attribute
            && ($subject instanceof Game || $subject instanceof Being || $subject instanceof Equipment);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        $game = $subject instanceof Game ? $subject : $subject->getGame();

        return $game->getGameMaster()->getId() === $user->getId();
    }
}
