<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Game;
use App\Entity\User;
use App\Repository\GameRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:init',
    description: 'Initialize the project with a default game master and game',
)]
class InitProjectCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly GameRepository $gameRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = $this->userRepository->findOneBy(['email' => 'toto3399@gmail.com']);

        if (!$user) {
            $user = (new User())
                ->setEmail('toto3399@gmail.com')
                ->setUsername('toto3399')
                ->setRoles(['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $this->userRepository->save($user);
            $io->success('User created: toto3399@gmail.com');
        } else {
            $io->note('User already exists: toto3399@gmail.com');
        }

        $game = $this->gameRepository->findOneBy(['name' => 'First Game']);

        if (!$game) {
            $game = (new Game())
                ->setName('First Game')
                ->setGameMaster($user);
            $this->gameRepository->save($game);
            $io->success('Game created: First Game');
        } else {
            $io->note('Game already exists: First Game');
        }

        return Command::SUCCESS;
    }
}
