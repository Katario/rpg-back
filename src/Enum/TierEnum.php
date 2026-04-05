<?php

declare(strict_types=1);

namespace App\Enum;

enum TierEnum: string
{
    case NOVICE = 'novice';
    case COMPANION = 'companion';
    case ADEPT = 'adept';
    case EXPERT = 'expert';
    case LEGENDARY = 'legendary';
}
