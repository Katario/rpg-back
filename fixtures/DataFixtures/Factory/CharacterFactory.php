<?php

namespace App\Fixtures\DataFixtures\Factory;

use App\Entity\Character;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Character>
 */
final class CharacterFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Character::class;
    }

    /**
     * @return array<bool|int|string>
     */
    protected function defaults(): array
    {
        return [
            'name' => self::faker()->text(50),
            'token' => self::faker()->md5(),
            'avatarUrl' => '/uploads/avatars/default-avatar.jpg',
            'level' => 1,
            'currentHealthPoints' => self::faker()->numberBetween(50, 250),
            'maxHealthPoints' => self::faker()->numberBetween(255, 500),
            'currentManaPoints' => self::faker()->numberBetween(50, 250),
            'maxManaPoints' => self::faker()->numberBetween(255, 500),
            'currentActionPoints' => self::faker()->numberBetween(50, 250),
            'maxActionPoints' => self::faker()->numberBetween(255, 500),
            'currentExhaustPoints' => self::faker()->numberBetween(50, 250),
            'maxExhaustPoints' => self::faker()->numberBetween(255, 500),
            'maxLoadPoints' => self::faker()->numberBetween(10, 20),
            'currentMentalPoints' => self::faker()->numberBetween(50, 250),
            'maxMentalPoints' => self::faker()->numberBetween(255, 500),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }
}
