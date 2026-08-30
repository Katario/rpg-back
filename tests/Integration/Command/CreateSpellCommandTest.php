<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Command\CreateSpellCommand;
use App\Entity\Spell;
use App\Fixtures\DataFixtures\Factory\SpellFactory;
use App\Repository\SpellRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[CoversClass(CreateSpellCommand::class)]
class CreateSpellCommandTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testCreatesNewSpellWithoutDamage(): void
    {
        $tester = $this->commandTester();
        // name, description, MA cost, PA cost, "no" (add damage line?), "no" (create another?)
        $tester->setInputs(['Heal', 'Restores health', '5', '2', 'no', 'no']);
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('created', $tester->getDisplay());

        $spell = $this->spellRepository()->findOneByName('Heal');
        \assert($spell instanceof Spell);
        self::assertSame('Restores health', $spell->getDescription());
        self::assertSame(5, $spell->getManaCost());
        self::assertSame(2, $spell->getActionPointCost());
        self::assertSame([], $spell->getDamageLines());
        self::assertTrue($spell->isPassive());
    }

    public function testCreatesSpellWithDamageLine(): void
    {
        $tester = $this->commandTester();
        // name, description, MA, PA, "yes" (add line), diceCount, diceFaces, fixedAmount,
        // type, element, "no" (add another line?), "no" (create another?)
        $tester->setInputs(['Fireball', 'A ball of fire', '8', '3', 'yes', '3', '6', '0', 'magical', 'fire', 'no', 'no']);
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();

        $spell = $this->spellRepository()->findOneByName('Fireball');
        \assert($spell instanceof Spell);

        $lines = $spell->getDamageLines();
        self::assertCount(1, $lines);
        self::assertSame(3, $lines[0]->getDiceCount());
        self::assertSame(6, $lines[0]->getDiceFaces());
        self::assertSame(0, $lines[0]->getFixedAmount());
        self::assertSame('magical', $lines[0]->getType()->value);
        self::assertSame('fire', $lines[0]->getElement()?->value);
        self::assertFalse($spell->isPassive());
    }

    public function testSkipsExistingSpell(): void
    {
        SpellFactory::createOne(['name' => 'Fireball']);

        $tester = $this->commandTester();
        $tester->setInputs(['Fireball', 'no']);
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('already exists', $tester->getDisplay());

        // No duplicate created.
        self::assertCount(1, $this->spellRepository()->findBy(['name' => 'Fireball']));
    }

    private function commandTester(): CommandTester
    {
        $kernel = self::bootKernel();
        $command = new Application($kernel)->find('app:spell:create');

        return new CommandTester($command);
    }

    private function spellRepository(): SpellRepository
    {
        return self::getContainer()->get(SpellRepository::class);
    }
}
