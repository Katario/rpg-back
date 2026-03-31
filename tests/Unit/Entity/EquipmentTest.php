<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Character;
use App\Entity\Equipment;
use App\Entity\Monster;
use App\Entity\NonPlayableCharacter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Equipment::class)]
class EquipmentTest extends TestCase
{
    public function testReturnFullNameIfOwnerIsAMonster(): void
    {
        $armament = new Equipment();

        $monster = new Monster();
        $monster->setName('Little Goblin');

        $armament->setBeing($monster);

        self::assertSame('Little Goblin', $armament->getOwnerName());
    }

    public function testReturnFullNameIfOwnerIsACharacter(): void
    {
        $armament = new Equipment();

        $character = new Character();

        $character->setName('Billy');
        $character->setLastName('O\'Neil');

        $armament->setBeing($character);

        self::assertSame('Billy O\'Neil', $armament->getOwnerName());
    }

    public function testReturnFullNameIfOwnerIsANonPlayableCharacter(): void
    {
        $armament = new Equipment();

        $nonPlayableCharacter = new NonPlayableCharacter();

        $nonPlayableCharacter->setName('Jack');
        $nonPlayableCharacter->setLastName('Doe');

        $armament->setBeing($nonPlayableCharacter);

        self::assertSame('Jack Doe', $armament->getOwnerName());
    }

    public function testReturnFullNameIfNoOwner(): void
    {
        $armament = new Equipment();

        self::assertNull($armament->getOwnerName());
    }

    public function testIsOwnedWhenBeingIsSet(): void
    {
        $armament = new Equipment();
        $character = new Character();
        $armament->setBeing($character);

        self::assertSame($character, $armament->getBeing());
        self::assertTrue($armament->isOwned());
    }

    public function testIsNotOwnedWhenBeingIsNull(): void
    {
        $armament = new Equipment();

        self::assertNull($armament->getBeing());
        self::assertFalse($armament->isOwned());
    }
}
