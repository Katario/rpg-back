<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Command\AssignTalentCommand;
use App\Entity\BeingTalent;
use App\Fixtures\DataFixtures\Factory\CharacterFactory;
use App\Fixtures\DataFixtures\Factory\GameFactory;
use App\Fixtures\DataFixtures\Factory\TalentFactory;
use App\Repository\BeingTalentRepository;
use App\Repository\CharacterRepository;
use App\Repository\TalentRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(AssignTalentCommand::class)]
class AssignTalentCommandTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testAssignsTalentAsPrimaryWithLevel(): void
    {
        $game = GameFactory::createOne();
        CharacterFactory::createOne(['game' => $game, 'token' => 'assign-token', 'name' => 'Aldric']);
        TalentFactory::createOne(['name' => 'Alchimie']);

        $tester = $this->commandTester();
        // character (list index), talent name, category, initial level
        $tester->setInputs(['0', 'Alchimie', 'primary', '3']);
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();

        $character = $this->characterRepository()->findOneByToken('assign-token');
        $talent = $this->talentRepository()->findOneByName('Alchimie');
        self::assertNotNull($character);
        self::assertNotNull($talent);

        $beingTalent = $this->beingTalentRepository()->findOneBy(['being' => $character, 'talent' => $talent]);
        \assert($beingTalent instanceof BeingTalent);
        self::assertSame(3, $beingTalent->getValue());

        self::assertTrue($character->getPrimaryTalents()->contains($talent));
        self::assertFalse($character->getSecondaryTalents()->contains($talent));
    }

    public function testAssignsTalentAsClassicWithoutClassification(): void
    {
        $game = GameFactory::createOne();
        CharacterFactory::createOne(['game' => $game, 'token' => 'assign-token-2']);
        TalentFactory::createOne(['name' => 'Discretion']);

        $tester = $this->commandTester();
        $tester->setInputs(['0', 'Discretion', 'classic', '5']);
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();

        $character = $this->characterRepository()->findOneByToken('assign-token-2');
        $talent = $this->talentRepository()->findOneByName('Discretion');
        self::assertNotNull($character);
        self::assertNotNull($talent);

        $beingTalent = $this->beingTalentRepository()->findOneBy(['being' => $character, 'talent' => $talent]);
        \assert($beingTalent instanceof BeingTalent);
        self::assertSame(5, $beingTalent->getValue());

        self::assertFalse($character->getPrimaryTalents()->contains($talent));
        self::assertFalse($character->getSecondaryTalents()->contains($talent));
    }

    public function testFailsWhenNoCharacterExists(): void
    {
        $tester = $this->commandTester();
        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('No character found', $tester->getDisplay());
    }

    public function testFailsWhenTalentNotFound(): void
    {
        $game = GameFactory::createOne();
        CharacterFactory::createOne(['game' => $game, 'token' => 'assign-token-3']);

        $tester = $this->commandTester();
        $tester->setInputs(['0', 'Missing talent']);
        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('No talent named', $tester->getDisplay());
    }

    private function commandTester(): CommandTester
    {
        $kernel = self::bootKernel();
        $command = new Application($kernel)->find('app:talent:assign');

        return new CommandTester($command);
    }

    private function characterRepository(): CharacterRepository
    {
        return self::getContainer()->get(CharacterRepository::class);
    }

    private function talentRepository(): TalentRepository
    {
        return self::getContainer()->get(TalentRepository::class);
    }

    private function beingTalentRepository(): BeingTalentRepository
    {
        return self::getContainer()->get(BeingTalentRepository::class);
    }
}
