<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Spell;
use App\Enum\DamageTypeEnum;
use App\Enum\ElementEnum;
use App\Repository\SpellRepository;
use App\ValueObject\DamageLine;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:spell:create',
    description: 'Create new spells interactively (one by one)',
)]
class CreateSpellCommand extends Command
{
    public function __construct(
        private readonly SpellRepository $spellRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $costValidator = static function (?string $value): string {
            if (null === $value || !ctype_digit($value)) {
                throw new \RuntimeException('The value must be a non-negative integer.');
            }

            return $value;
        };

        do {
            $name = $io->ask('Spell name');

            if (!is_string($name) || '' === trim($name)) {
                $io->warning('Empty name, skipped.');
                continue;
            }

            $name = trim($name);

            if ($this->spellRepository->findOneByName($name)) {
                $io->warning(sprintf('Spell "%s" already exists, skipped.', $name));
                continue;
            }

            $description = (string) $io->ask('Description', '');
            $manaCost = (int) $io->ask('Mana cost (MA)', '0', $costValidator);
            $actionPointCost = (int) $io->ask('Action point cost (PA)', '0', $costValidator);

            $damageLines = [];
            while ($io->confirm('Add a damage line?', false)) {
                $diceCount = (int) $io->ask('Dice count', '0', $costValidator);
                $diceFaces = (int) $io->ask('Dice faces', '0', $costValidator);
                $fixedAmount = (int) $io->ask('Fixed amount', '0', $costValidator);
                $type = DamageTypeEnum::from((string) $io->choice('Damage type', ['physical', 'magical'], 'magical'));
                $elementChoice = (string) $io->choice('Element', ['none', 'fire', 'ice', 'thunder'], 'none');
                $element = 'none' === $elementChoice ? null : ElementEnum::from($elementChoice);

                $damageLines[] = new DamageLine($diceCount, $diceFaces, $fixedAmount, $type, $element);
            }

            $spell = new Spell()
                ->setName($name)
                ->setDescription($description)
                ->setManaCost($manaCost)
                ->setActionPointCost($actionPointCost)
                ->setDamageLines($damageLines)
                ->setIsReady(true)
                ->setIsPrivate(false);

            $this->spellRepository->save($spell);

            $io->success(sprintf('Spell "%s" created (%d damage line(s)).', $name, count($damageLines)));
        } while ($io->confirm('Create another spell?'));

        return Command::SUCCESS;
    }
}
