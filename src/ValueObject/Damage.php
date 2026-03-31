<?php

declare(strict_types=1);

namespace App\ValueObject;

class Damage
{
    /**
     * @param array<array{count: int, faces: int}> $dice
     */
    public function __construct(
        private array $dice = [],
        private int $bonus = 0,
    ) {}

    /** @return array<array{count: int, faces: int}> */
    public function getDice(): array
    {
        return $this->dice;
    }

    public function getBonus(): int
    {
        return $this->bonus;
    }
}
