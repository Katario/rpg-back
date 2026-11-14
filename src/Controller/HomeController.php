<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\GameRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Twig\Environment;

#[AsController]
class HomeController
{
    public function __construct(
        public Environment $twig,
        public Security $security,
        public GameRepository $gameRepository,
    ) {
    }

    #[Route('/', name: 'home', methods: ['GET'])]
    public function showGames(#[CurrentUser] User $user): Response
    {
        // Game masters see their games; admins see every game.
        $games = $this->security->isGranted('ROLE_ADMIN')
            ? $this->gameRepository->findBy([], ['name' => 'ASC'])
            : $this->gameRepository->getGamesAsGameMaster($user);

        return new Response(
            $this->twig->render('main/main.html.twig', [
                'user' => $user,
                'games' => $games,
            ])
        );
    }
}
