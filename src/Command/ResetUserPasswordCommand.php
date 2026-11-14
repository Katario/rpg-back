<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ApiTokenRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Manual password reset, until reset by email is available (no SMTP in production yet).
 */
#[AsCommand(
    name: 'app:user:reset-password',
    description: 'Set a new password for a user (asked twice, hidden) and sign them out of the API',
)]
class ResetUserPasswordCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ApiTokenRepository $apiTokenRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email of the user');
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

        $password = (string) $io->askQuestion((new Question('New password'))->setHidden(true));

        // Same rules as registration (App\Dto\RegisterUserRequest).
        $violations = $this->validator->validate($password, [new NotBlank(), new Length(min: 8, max: 4096)]);
        if (count($violations) > 0) {
            $io->error((string) $violations->get(0)->getMessage());

            return Command::FAILURE;
        }

        if ($password !== (string) $io->askQuestion((new Question('Repeat the password'))->setHidden(true))) {
            $io->error('The passwords do not match.');

            return Command::FAILURE;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $this->userRepository->save($user);
        $revoked = $this->apiTokenRepository->deleteForUser($user);

        $io->success(sprintf('Password updated for %s (%d API token(s) revoked).', $user->getEmail(), $revoked));

        return Command::SUCCESS;
    }
}
