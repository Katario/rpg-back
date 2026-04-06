<?php

declare(strict_types=1);

namespace App\Enum;

enum DamageTypeEnum: string
{
    case PHYSICAL = 'physical';
    case MAGICAL = 'magical';
}
