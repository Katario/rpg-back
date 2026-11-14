<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * The email must not be used by another account (case-insensitive, see UserRepository::findOneByEmail()).
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class UniqueUserEmail extends Constraint
{
    public const string MESSAGE = 'This email is already used.';

    public string $message = self::MESSAGE;
}
