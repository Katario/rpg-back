<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Game;
use App\Entity\User;
use App\FormType\GameType;
use App\Repository\GameRepository;
use App\Security\Voter\GameVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Environment;

#[AsController]
class GameController
{
    public function __construct(
        public Environment $twig,
        public FormFactoryInterface $formFactory,
        public UrlGeneratorInterface $router,
        public GameRepository $gameRepository,
        public Security $security,
    ) {
    }

    #[Route('/games/{id}', name: 'show_game', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted(GameVoter::MANAGE, 'game')]
    public function showGame(
        #[MapEntity(id: 'id')] Game $game,
    ): Response {
        return new Response(
            $this->twig->render('game/show_game.html.twig', [
                'game' => $game,
            ])
        );
    }

    #[Route('/games/create', name: 'create_game', methods: ['GET', 'POST'])]
    public function createGame(
        Request $request,
    ): Response|RedirectResponse {
        $form = $this->formFactory->create(GameType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Game $game */
            $game = $form->getData();
            /** @var User $user */
            $user = $this->security->getUser();
            $game->setGameMaster($user);

            $this->gameRepository->save($game);

            return new RedirectResponse($this->router->generate('show_game', ['id' => $game->getId()]));
        }

        return new Response(
            $this->twig->render('game/create_game.html.twig', [
                'form' => $form->createView(),
            ])
        );
    }

    /**
     * Archiving replaces deletion: nothing is deleted, the game is hidden and closed (see docs/game.md).
     */
    #[Route('/games/{id}/archive', name: 'archive_game', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(GameVoter::MANAGE, 'game')]
    #[IsCsrfTokenValid(new Expression('"game-archive-" ~ args["game"].getId()'))]
    public function archiveGame(#[MapEntity(id: 'id')] Game $game): RedirectResponse
    {
        $game->archive();
        $this->gameRepository->save($game);

        return new RedirectResponse($this->router->generate('show_game', ['id' => $game->getId()]));
    }

    #[Route('/games/{id}/unarchive', name: 'unarchive_game', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(GameVoter::MANAGE, 'game')]
    #[IsCsrfTokenValid(new Expression('"game-archive-" ~ args["game"].getId()'))]
    public function unarchiveGame(#[MapEntity(id: 'id')] Game $game): RedirectResponse
    {
        $game->unarchive();
        $this->gameRepository->save($game);

        return new RedirectResponse($this->router->generate('show_game', ['id' => $game->getId()]));
    }
}
