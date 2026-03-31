<?php

namespace App\Fixtures\DataFixtures\Factory;

use App\Entity\Armor;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Armor>
 */
final class ArmorFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Armor::class;
    }

    /**
     * @return array<bool|\DateTimeImmutable|int|string>
     */
    protected function defaults(): array
    {
        return [
            'currentDurabilityPoints' => self::faker()->numberBetween(1, 10),
            'description'             => self::faker()->text(),
            'maxDurabilityPoints'     => self::faker()->numberBetween(11, 20),
            'name'                    => self::faker()->text(50),
            'value'                   => self::faker()->numberBetween(1, 10000),
            'weight'                  => self::faker()->numberBetween(1, 20),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
}
