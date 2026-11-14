<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ApiTokenRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:api-token:purge',
    description: 'Delete expired API access tokens (meant to run from a cron)',
)]
class PurgeApiTokensCommand extends Command
{
    public function __construct(
        private readonly ApiTokenRepository $apiTokenRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $deleted = $this->apiTokenRepository->deleteExpired();

        new SymfonyStyle($input, $output)->success(sprintf('%d expired API token(s) deleted.', $deleted));

        return Command::SUCCESS;
    }
}
