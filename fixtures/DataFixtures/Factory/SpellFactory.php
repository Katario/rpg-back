<?php

namespace App\Fixtures\DataFixtures\Factory;

use App\Entity\Spell;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Spell>
 */
final class SpellFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Spell::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'description' => self::faker()->text(),
            'isPrivate' => self::faker()->boolean(),
            'isReady' => self::faker()->boolean(),
            'manaCost' => self::faker()->numberBetween(10, 100),
            'actionPointCost' => self::faker()->randomNumber(),
            'damageLines' => [],
            'name' => self::faker()->text(),
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Spell $spell): void {})
        ;
    }
}
