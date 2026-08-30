<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\BeingTalent;
use App\Entity\Character;
use App\Entity\Talent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Character::class)]
class CharacterLevelUpTest extends TestCase
{
    public function testLevelUpOnLoadAddsOneKilogramPerPoint(): void
    {
        $character = new Character();
        $character->setLevel(1);
        $character->setMaxLoadPoints(5000);
        $character->setMaxHealthPoints(10);

        $this->giveFiveTalents($character);

        // 1 point on load (+1000 g = 1 kg) + 1 point on health (+1) = 2 points.
        $character->levelUp(
            ['maxLoadPoints' => 1000, 'maxHealthPoints' => 1],
            ['T1', 'T2', 'T3', 'T4', 'T5'],
        );

        self::assertSame(6000, $character->getMaxLoadPoints());
        self::assertSame(11, $character->getMaxHealthPoints());
        self::assertSame(2, $character->getLevel());
    }

    public function testLevelUpRejectsLoadIncrementNotMultipleOfOneKilogram(): void
    {
        $character = new Character();
        $character->setMaxLoadPoints(5000);
        $this->giveFiveTalents($character);

        $this->expectException(\InvalidArgumentException::class);

        // 10 g is no longer a valid load increment (must be a multiple of 1000).
        $character->levelUp(
            ['maxLoadPoints' => 10, 'maxHealthPoints' => 1],
            ['T1', 'T2', 'T3', 'T4', 'T5'],
        );
    }

    private function giveFiveTalents(Character $character): void
    {
        foreach (['T1', 'T2', 'T3', 'T4', 'T5'] as $name) {
            $talent = new Talent()->setName($name);
            $beingTalent = new BeingTalent()
                ->setBeing($character)
                ->setTalent($talent)
                ->setValue(0);
            $character->addTalent($beingTalent);
        }
    }
}
