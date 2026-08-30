<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Command\CreateSkillCommand;
use App\Entity\Skill;
use App\Fixtures\DataFixtures\Factory\SkillFactory;
use App\Repository\SkillRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(CreateSkillCommand::class)]
class CreateSkillCommandTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testCreatesNewSkillWithoutDamage(): void
    {
        $tester = $this->commandTester();
        // name, description, FA cost, PA cost, "no" (add damage line?), "no" (create another?)
        $tester->setInputs(['Backstab', 'A sneaky strike', '2', '1', 'no', 'no']);
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('created', $tester->getDisplay());

        $skill = $this->skillRepository()->findOneByName('Backstab');
        \assert($skill instanceof Skill);
        self::assertSame('A sneaky strike', $skill->getDescription());
        self::assertSame(2, $skill->getExhaustPointCost());
        self::assertSame(1, $skill->getActionPointCost());
        self::assertSame([], $skill->getDamageLines());
        self::assertTrue($skill->isPassive());
    }

    public function testCreatesSkillWithDamageLine(): void
    {
        $tester = $this->commandTester();
        // name, description, FA, PA, "yes" (add line), diceCount, diceFaces, fixedAmount,
        // type, element, "no" (add another line?), "no" (create another?)
        $tester->setInputs(['Heavy Strike', 'A crushing blow', '3', '2', 'yes', '2', '6', '3', 'physical', 'none', 'no', 'no']);
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();

        $skill = $this->skillRepository()->findOneByName('Heavy Strike');
        \assert($skill instanceof Skill);

        $lines = $skill->getDamageLines();
        self::assertCount(1, $lines);
        self::assertSame(2, $lines[0]->getDiceCount());
        self::assertSame(6, $lines[0]->getDiceFaces());
        self::assertSame(3, $lines[0]->getFixedAmount());
        self::assertSame('physical', $lines[0]->getType()->value);
        self::assertNull($lines[0]->getElement());
        self::assertFalse($skill->isPassive());
    }

    public function testSkipsExistingSkill(): void
    {
        SkillFactory::createOne(['name' => 'Backstab']);

        $tester = $this->commandTester();
        $tester->setInputs(['Backstab', 'no']);
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('already exists', $tester->getDisplay());

        // No duplicate created.
        self::assertCount(1, $this->skillRepository()->findBy(['name' => 'Backstab']));
    }

    private function commandTester(): CommandTester
    {
        $kernel = self::bootKernel();
        $command = new Application($kernel)->find('app:skill:create');

        return new CommandTester($command);
    }

    private function skillRepository(): SkillRepository
    {
        return self::getContainer()->get(SkillRepository::class);
    }
}
