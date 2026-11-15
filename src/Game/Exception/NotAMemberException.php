<?php

declare(strict_types=1);

namespace App\Game\Exception;

final class NotAMemberException extends \DomainException
{
    public function __construct(string $message = 'This user is not a player of the game.')
    {
        parent::__construct($message);
    }
}
