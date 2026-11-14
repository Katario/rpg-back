<?php

declare(strict_types=1);

namespace App\Factory;

use App\Dto\RegisterUserRequest;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFactory
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * Builds an account from a validated registration (web sign-up and API); the caller persists it.
     */
    public function createOneFromRegistration(RegisterUserRequest $registration): User
    {
        $user = new User()->setEmail($registration->email);

        return $user->setPassword($this->passwordHasher->hashPassword($user, $registration->password));
    }
}
