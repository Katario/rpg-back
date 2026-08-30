<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Talent;
use App\Repository\TalentRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:talent:create',
    description: 'Create new talents interactively (one by one)',
)]
class CreateTalentCommand extends Command
{
    public function __construct(
        private readonly TalentRepository $talentRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        do {
            $name = $io->ask('Talent name');

            if (!is_string($name) || '' === trim($name)) {
                $io->warning('Empty name, skipped.');
                continue;
            }

            $name = trim($name);

            if ($this->talentRepository->findOneByName($name)) {
                $io->warning(sprintf('Talent "%s" already exists, skipped.', $name));
                continue;
            }

            $talent = (new Talent())
                ->setName($name)
                ->setDescription('')
                ->setIsReady(true)
                ->setIsPrivate(false);

            $this->talentRepository->save($talent);

            $io->success(sprintf('Talent "%s" created.', $name));
        } while ($io->confirm('Create another talent?'));

        return Command::SUCCESS;
    }
}
