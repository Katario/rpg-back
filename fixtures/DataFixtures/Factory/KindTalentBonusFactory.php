<?php

namespace App\Fixtures\DataFixtures\Factory;

use App\Entity\KindTalentBonus;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<KindTalentBonus>
 */
final class KindTalentBonusFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return KindTalentBonus::class;
    }

    /**
     * @return array<int|string>
     */
    protected function defaults(): array
    {
        return [
            'value' => self::faker()->numberBetween(1, 5),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
}
