<?php

declare(strict_types=1);

namespace App\Dto;

use App\Validator\UniqueUserEmail;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Registration data: body of POST /api/auth/register, and data of the web sign-up form.
 */
final readonly class RegisterUserRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        #[UniqueUserEmail]
        public string $email,
        #[Assert\NotBlank]
        // 4096: upper bound accepted by Symfony password hashers.
        #[Assert\Length(min: 8, max: 4096)]
        public string $password,
    ) {
    }
}
