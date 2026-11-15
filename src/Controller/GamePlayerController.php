<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Game;
use App\Entity\User;
use App\Game\Exception\NotAMemberException;
use App\Game\GameMembershipManager;
use App\Security\Voter\GameVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Back office: the game master manages the players of their game and its invite code (see docs/game.md).
 * Every action is a POST protected by a CSRF token, then redirects to the game page.
 */
#[AsController]
class GamePlayerController
{
    public function __construct(
        private readonly UrlGeneratorInterface $router,
        private readonly GameMembershipManager $gameMembershipManager,
    ) {
    }

    #[Route('/games/{id}/invite-code', name: 'generate_game_invite_code', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(GameVoter::MANAGE, 'game')]
    #[IsCsrfTokenValid(new Expression('"game-invite-code-" ~ args["game"].getId()'))]
    public function generateInviteCode(#[MapEntity(id: 'id')] Game $game): RedirectResponse
    {
        if ($game->isArchived()) {
            throw new ConflictHttpException('An archived game cannot be opened to new players.');
        }

        $this->gameMembershipManager->generateInviteCode($game);

        return $this->toGame($game);
    }

    #[Route('/games/{id}/invite-code/delete', name: 'delete_game_invite_code', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted(GameVoter::MANAGE, 'game')]
    #[IsCsrfTokenValid(new Expression('"game-invite-code-" ~ args["game"].getId()'))]
    public function deleteInviteCode(#[MapEntity(id: 'id')] Game $game): RedirectResponse
    {
        $this->gameMembershipManager->deleteInviteCode($game);

        return $this->toGame($game);
    }

    #[Route('/games/{id}/players/{userId}/remove',
        name: 'remove_game_player',
        requirements: ['id' => '\d+', 'userId' => '\d+'],
        methods: ['POST'],
    )]
    #[IsGranted(GameVoter::MANAGE, 'game')]
    #[IsCsrfTokenValid(new Expression('"game-player-remove-" ~ args["game"].getId() ~ "-" ~ args["player"].getId()'))]
    public function removePlayer(
        #[MapEntity(id: 'id')] Game $game,
        #[MapEntity(id: 'userId')] User $player,
    ): RedirectResponse {
        try {
            $this->gameMembershipManager->remove($game, $player);
        } catch (NotAMemberException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        }

        return $this->toGame($game);
    }

    private function toGame(Game $game): RedirectResponse
    {
        return new RedirectResponse($this->router->generate('show_game', ['id' => $game->getId()]));
    }
}
