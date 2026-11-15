<?php

declare(strict_types=1);

namespace App\Tests\Unit\Game;

use App\Game\InviteCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(InviteCode::class)]
class InviteCodeTest extends TestCase
{
    public function testGeneratedCodesAreNormalizedAndAvoidLookAlikes(): void
    {
        for ($i = 0; $i < 50; ++$i) {
            $code = InviteCode::generate();

            self::assertSame($code, InviteCode::normalize($code));
            self::assertDoesNotMatchRegularExpression('/[01IO]/', $code);
        }
    }

    #[DataProvider('typedCodes')]
    public function testNormalize(string $typed, ?string $expected): void
    {
        self::assertSame($expected, InviteCode::normalize($typed));
    }

    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function typedCodes(): iterable
    {
        yield 'stored form' => ['K7QM3XPA', 'K7QM3XPA'];
        yield 'displayed form' => ['K7QM-3XPA', 'K7QM3XPA'];
        yield 'lowercase, spaces around' => ['  k7qm 3xpa ', 'K7QM3XPA'];
        yield 'too short' => ['K7QM3XP', null];
        yield 'too long' => ['K7QM3XPAB', null];
        yield 'look-alike characters are not in codes' => ['K7QM3XP0', null];
        yield 'other characters' => ['K7QM_3XPA', null];
        yield 'empty' => ['', null];
    }

    public function testFormat(): void
    {
        self::assertSame('K7QM-3XPA', InviteCode::format('K7QM3XPA'));
    }
}
