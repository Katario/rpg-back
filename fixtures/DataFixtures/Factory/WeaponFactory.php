<?php

namespace App\Fixtures\DataFixtures\Factory;

use App\Entity\Weapon;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Weapon>
 */
final class WeaponFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Weapon::class;
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
            'damageDice'              => [],
            'damageBonus'             => 0,
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
}
