<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\User;

/**
 * Public representation of a user account, shared by every API route returning a user.
 */
class UserSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'displayName' => $user->getDisplayName(),
            'roles' => $user->getRoles(),
        ];
    }
}
