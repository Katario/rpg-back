<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Enum\BeingEnum;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use App\Fixtures\DataFixtures\Factory\WeaponFactory;
use App\Repository\EquipmentRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(EquipmentRepository::class)]
class ArmamentRepositoryTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    #[DataProvider('possibleOwnerEnumProvider')]
    public function testIsArmamentAvailableWithNoOwner(
        BeingEnum $possibleOwnerEnum,
    ): void {
        self::bootKernel();

        $game = GameFactory::createOne();
        WeaponFactory::createOne([
            'game' => $game,
        ]);

        $result = $this->getArmamentRepository()
            ->availableArmamentsQueryBuilder(
                $game->getId(),
                $possibleOwnerEnum
            )
            ->getQuery()
            ->getResult();

        self::assertCount(1, $result);
    }

    public static function possibleOwnerEnumProvider(): \Generator
    {
        yield 'Armament available for Monsters' => [BeingEnum::MONSTER];
        yield 'Armament available for Characters' => [BeingEnum::CHARACTER];
        yield 'Armament available for NPCs' => [BeingEnum::NON_PLAYABLE_CHARACTER];
    }

    private function getArmamentRepository(): EquipmentRepository
    {
        return self::getContainer()->get(EquipmentRepository::class);
    }
}
