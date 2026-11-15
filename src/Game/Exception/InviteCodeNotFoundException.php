<?php

declare(strict_types=1);

namespace App\Game\Exception;

final class InviteCodeNotFoundException extends \DomainException
{
    public function __construct(string $message = 'No open game matches this invite code.')
    {
        parent::__construct($message);
    }
}
