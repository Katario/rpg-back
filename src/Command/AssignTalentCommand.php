<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\BeingTalent;
use App\Repository\BeingTalentRepository;
use App\Repository\CharacterRepository;
use App\Repository\TalentRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:talent:assign',
    description: 'Assign a talent to a character as primary/secondary/classic with an initial level',
)]
class AssignTalentCommand extends Command
{
    public function __construct(
        private readonly CharacterRepository $characterRepository,
        private readonly TalentRepository $talentRepository,
        private readonly BeingTalentRepository $beingTalentRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $characters = $this->characterRepository->findAll();
        if (0 === count($characters)) {
            $io->error('No character found.');

            return Command::FAILURE;
        }

        $choices = [];
        foreach ($characters as $candidate) {
            $lastName = $candidate->getLastName();
            $label = sprintf(
                '%s%s (%s)',
                $candidate->getName(),
                $lastName ? ' '.$lastName : '',
                $candidate->getToken(),
            );
            $choices[$label] = $candidate;
        }

        $selected = (string) $io->choice('Character', array_keys($choices));
        $character = $choices[$selected];

        $talentName = $io->ask('Talent name');
        if (!is_string($talentName) || '' === trim($talentName)) {
            $io->error('A talent name is required.');

            return Command::FAILURE;
        }

        $talent = $this->talentRepository->findOneByName(trim($talentName));
        if (!$talent) {
            $io->error(sprintf('No talent named "%s". Create it first with app:talent:create.', trim($talentName)));

            return Command::FAILURE;
        }

        $category = $io->choice('Category', ['primary', 'secondary', 'classic'], 'classic');

        $level = (int) $io->ask('Initial level', '0', function (?string $value): string {
            if (null === $value || !ctype_digit($value)) {
                throw new \RuntimeException('The level must be a non-negative integer.');
            }

            return $value;
        });

        $beingTalent = $this->beingTalentRepository->findOneBy(['being' => $character, 'talent' => $talent]);
        if (!$beingTalent) {
            $beingTalent = new BeingTalent()
                ->setBeing($character)
                ->setTalent($talent);
        }
        $beingTalent->setValue($level);
        $this->beingTalentRepository->save($beingTalent);

        if ('primary' === $category) {
            $character->addPrimaryTalent($talent);
            $this->characterRepository->save($character);
        } elseif ('secondary' === $category) {
            $character->addSecondaryTalent($talent);
            $this->characterRepository->save($character);
        }

        $io->success(sprintf(
            'Talent "%s" assigned to "%s" as %s (level %d).',
            $talent->getName(),
            $character->getName(),
            $category,
            $level,
        ));

        return Command::SUCCESS;
    }
}
