<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Character;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * - VIEW: the owner of the character, or whoever manages its game (game master, admin).
 * - EDIT / DELETE: whoever manages its game only — the back office character form can reassign the player.
 *
 * Not applied to the public token routes yet (see docs/auth.md).
 *
 * @extends Voter<string, Character>
 */
class CharacterVoter extends Voter
{
    public const string VIEW = 'CHARACTER_VIEW';
    public const string EDIT = 'CHARACTER_EDIT';
    public const string DELETE = 'CHARACTER_DELETE';

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true) && $subject instanceof Character;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($this->accessDecisionManager->decide($token, [GameVoter::MANAGE], $subject)) {
            return true;
        }

        return self::VIEW === $attribute && $subject->getUser()?->getId() === $user->getId();
    }
}
