<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\ApiToken;
use App\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApiToken::class)]
class ApiTokenTest extends TestCase
{
    public function testHashIsDeterministicAndNotThePlainToken(): void
    {
        $hash = ApiToken::hash('rpg_secret');

        self::assertSame($hash, ApiToken::hash('rpg_secret'));
        self::assertNotSame('rpg_secret', $hash);
        self::assertSame(64, strlen($hash));
    }

    public function testExpiry(): void
    {
        $now = new \DateTimeImmutable('2026-11-13 12:00:00');
        $token = new ApiToken(new User(), 'hash', $now->modify('+1 hour'), $now);

        self::assertFalse($token->isExpired($now));
        self::assertTrue($token->isExpired($now->modify('+1 hour')));
        self::assertTrue($token->isExpired($now->modify('+2 hours')));
    }

    public function testMarkUsed(): void
    {
        $now = new \DateTimeImmutable('2026-11-13 12:00:00');
        $token = new ApiToken(new User(), 'hash', $now->modify('+1 day'), $now);

        self::assertNull($token->getLastUsedAt());

        $token->markUsed($now->modify('+5 minutes'));

        self::assertEquals($now->modify('+5 minutes'), $token->getLastUsedAt());
    }
}
