<?php

declare(strict_types=1);

namespace App\ValueObject;

use App\Enum\DamageTypeEnum;
use App\Enum\ElementEnum;

class DamageLine
{
    public function __construct(
        private int $diceCount = 0,
        private int $diceFaces = 0,
        private int $fixedAmount = 0,
        private DamageTypeEnum $type = DamageTypeEnum::PHYSICAL,
        private ?ElementEnum $element = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            diceCount: (int) ($data['diceCount'] ?? 0),
            diceFaces: (int) ($data['diceFaces'] ?? 0),
            fixedAmount: (int) ($data['fixedAmount'] ?? 0),
            type: DamageTypeEnum::tryFrom((string) ($data['type'] ?? '')) ?? DamageTypeEnum::PHYSICAL,
            element: ElementEnum::tryFrom((string) ($data['element'] ?? '')),
        );
    }

    public function toArray(): array
    {
        return [
            'diceCount'   => $this->diceCount,
            'diceFaces'   => $this->diceFaces,
            'fixedAmount' => $this->fixedAmount,
            'type'        => $this->type->value,
            'element'     => $this->element?->value,
        ];
    }

    public function getDiceCount(): int
    {
        return $this->diceCount;
    }

    public function getDiceFaces(): int
    {
        return $this->diceFaces;
    }

    public function getFixedAmount(): int
    {
        return $this->fixedAmount;
    }

    public function getType(): DamageTypeEnum
    {
        return $this->type;
    }

    public function getElement(): ?ElementEnum
    {
        return $this->element;
    }
}
