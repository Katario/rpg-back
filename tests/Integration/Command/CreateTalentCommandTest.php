<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Command\CreateTalentCommand;
use App\Fixtures\DataFixtures\Factory\TalentFactory;
use App\Repository\TalentRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(CreateTalentCommand::class)]
class CreateTalentCommandTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testCreatesNewTalent(): void
    {
        $tester = $this->commandTester();
        // Talent name, then "no" to the "create another?" prompt.
        $tester->setInputs(['Alchimie', 'no']);
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('created', $tester->getDisplay());

        self::assertNotNull($this->talentRepository()->findOneByName('Alchimie'));
    }

    public function testSkipsExistingTalent(): void
    {
        TalentFactory::createOne(['name' => 'Alchimie']);

        $tester = $this->commandTester();
        $tester->setInputs(['Alchimie', 'no']);
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('already exists', $tester->getDisplay());

        // No duplicate created.
        self::assertCount(1, $this->talentRepository()->findBy(['name' => 'Alchimie']));
    }

    private function commandTester(): CommandTester
    {
        $kernel = self::bootKernel();
        $command = new Application($kernel)->find('app:talent:create');

        return new CommandTester($command);
    }

    private function talentRepository(): TalentRepository
    {
        return self::getContainer()->get(TalentRepository::class);
    }
}
