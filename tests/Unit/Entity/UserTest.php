<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(User::class)]
class UserTest extends TestCase
{
    public function testEmailIsNormalized(): void
    {
        $user = new User()->setEmail('  Aldric@Example.COM ');

        self::assertSame('aldric@example.com', $user->getEmail());
        self::assertSame('aldric@example.com', $user->getUserIdentifier());
    }

    public function testDisplayNameIsTheUsernameWhenSet(): void
    {
        $user = new User()->setEmail('aldric@example.com')->setUsername('Aldric');

        self::assertSame('Aldric', $user->getDisplayName());
    }

    public function testDisplayNameFallsBackToTheEmail(): void
    {
        $user = new User()->setEmail('aldric@example.com');

        self::assertNull($user->getUsername());
        self::assertSame('aldric@example.com', $user->getDisplayName());
    }
}
