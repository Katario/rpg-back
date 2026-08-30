<?php

namespace App\Fixtures\DataFixtures\Factory;

use App\Entity\Skill;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Skill>
 */
final class SkillFactory extends PersistentProxyObjectFactory
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
        return Skill::class;
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
            'name' => self::faker()->text(),
            'exhaustPointCost' => self::faker()->randomNumber(),
            'actionPointCost' => self::faker()->randomNumber(),
            'damageLines' => [],
            'updatedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Skill $skill): void {})
        ;
    }
}
