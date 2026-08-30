<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Skill;
use App\Enum\DamageTypeEnum;
use App\Enum\ElementEnum;
use App\Repository\SkillRepository;
use App\ValueObject\DamageLine;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:skill:create',
    description: 'Create new skills interactively (one by one)',
)]
class CreateSkillCommand extends Command
{
    public function __construct(
        private readonly SkillRepository $skillRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $costValidator = static function (?string $value): string {
            if (null === $value || !ctype_digit($value)) {
                throw new \RuntimeException('The cost must be a non-negative integer.');
            }

            return $value;
        };

        do {
            $name = $io->ask('Skill name');

            if (!is_string($name) || '' === trim($name)) {
                $io->warning('Empty name, skipped.');
                continue;
            }

            $name = trim($name);

            if ($this->skillRepository->findOneByName($name)) {
                $io->warning(sprintf('Skill "%s" already exists, skipped.', $name));
                continue;
            }

            $description = (string) $io->ask('Description', '');
            $exhaustPointCost = (int) $io->ask('Exhaust point cost (FA)', '0', $costValidator);
            $actionPointCost = (int) $io->ask('Action point cost (PA)', '0', $costValidator);

            $damageLines = [];
            while ($io->confirm('Add a damage line?', false)) {
                $diceCount = (int) $io->ask('Dice count', '0', $costValidator);
                $diceFaces = (int) $io->ask('Dice faces', '0', $costValidator);
                $fixedAmount = (int) $io->ask('Fixed amount', '0', $costValidator);
                $type = DamageTypeEnum::from((string) $io->choice('Damage type', ['physical', 'magical'], 'physical'));
                $elementChoice = (string) $io->choice('Element', ['none', 'fire', 'ice', 'thunder'], 'none');
                $element = 'none' === $elementChoice ? null : ElementEnum::from($elementChoice);

                $damageLines[] = new DamageLine($diceCount, $diceFaces, $fixedAmount, $type, $element);
            }

            $skill = new Skill()
                ->setName($name)
                ->setDescription($description)
                ->setExhaustPointCost($exhaustPointCost)
                ->setActionPointCost($actionPointCost)
                ->setDamageLines($damageLines)
                ->setIsReady(true)
                ->setIsPrivate(false);

            $this->skillRepository->save($skill);

            $io->success(sprintf('Skill "%s" created (%d damage line(s)).', $name, count($damageLines)));
        } while ($io->confirm('Create another skill?'));

        return Command::SUCCESS;
    }
}
