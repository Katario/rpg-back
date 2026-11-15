<?php

declare(strict_types=1);

namespace App\Twig;

use App\Game\InviteCode;
use Twig\Attribute\AsTwigFilter;

final class GameExtension
{
    /**
     * Displayed form of a stored invite code: "K7QM3XPA" → "K7QM-3XPA".
     */
    #[AsTwigFilter('invite_code')]
    public static function formatInviteCode(string $code): string
    {
        return InviteCode::format($code);
    }
}
