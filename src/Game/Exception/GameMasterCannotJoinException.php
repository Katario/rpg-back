<?php

declare(strict_types=1);

namespace App\Game\Exception;

final class GameMasterCannotJoinException extends \DomainException
{
    public function __construct(string $message = 'The game master cannot join their own game as a player.')
    {
        parent::__construct($message);
    }
}
