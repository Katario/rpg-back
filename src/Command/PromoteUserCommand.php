<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:promote',
    description: 'Grant (or revoke with --revoke) the admin role to a user',
)]
class PromoteUserCommand extends Command
{
    private const string ROLE = 'ROLE_ADMIN';

    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the user')
            ->addOption('revoke', null, InputOption::VALUE_NONE, 'Remove the admin role instead of granting it');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $email */
        $email = $input->getArgument('email');

        $user = $this->userRepository->findOneByEmail($email);
        if (!$user) {
            $io->error(sprintf('No user with the email "%s".', $email));

            return Command::FAILURE;
        }

        // Stored roles only: ROLE_USER is added by User::getRoles().
        $roles = array_values(array_diff($user->getRoles(), ['ROLE_USER', self::ROLE]));
        if (!$input->getOption('revoke')) {
            $roles[] = self::ROLE;
        }

        $user->setRoles($roles);
        $this->userRepository->save($user);

        $io->success(sprintf(
            $input->getOption('revoke') ? '%s is no longer an admin.' : '%s is now an admin.',
            $user->getEmail(),
        ));

        return Command::SUCCESS;
    }
}
