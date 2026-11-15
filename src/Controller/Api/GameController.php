<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Game;
use App\Entity\User;
use App\Game\Exception\GameMasterCannotJoinException;
use App\Game\Exception\InviteCodeNotFoundException;
use App\Game\Exception\NotAMemberException;
use App\Game\GameMembershipManager;
use App\Repository\GameMembershipRepository;
use App\Repository\GameRepository;
use App\Serializer\GameSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Games of the signed-in player (see docs/game.md). The games a user runs are managed in the back office.
 */
#[Route('/api/games')]
#[IsGranted('IS_AUTHENTICATED')]
class GameController extends AbstractController
{
    #[Route('', name: 'api_games', methods: ['GET'])]
    public function list(
        #[CurrentUser] User $user,
        GameMembershipRepository $gameMembershipRepository,
        GameSerializer $gameSerializer,
    ): JsonResponse {
        return $this->json(array_map(
            $gameSerializer->serializeForPlayer(...),
            $gameMembershipRepository->findByPlayer($user),
        ));
    }

    #[Route('/join', name: 'api_games_join', methods: ['POST'])]
    public function join(
        #[CurrentUser] User $user,
        Request $request,
        GameMembershipManager $gameMembershipManager,
        GameMembershipRepository $gameMembershipRepository,
        GameSerializer $gameSerializer,
        RateLimiterFactoryInterface $gameJoinLimiter,
    ): JsonResponse {
        $limit = $gameJoinLimiter->create((string) $user->getId())->consume();
        if (!$limit->isAccepted()) {
            return $this->json(
                ['error' => 'Too many attempts, please try again later.'],
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => (string) max(0, $limit->getRetryAfter()->getTimestamp() - time())],
            );
        }

        $body = json_decode($request->getContent(), true);
        if (!is_array($body) || !is_string($body['code'] ?? null) || '' === trim($body['code'])) {
            return $this->json(['error' => 'Field "code" is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $game = $gameMembershipManager->join($user, $body['code']);
        } catch (InviteCodeNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (GameMasterCannotJoinException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        $membership = $gameMembershipRepository->findOneByGameAndUser($game, $user)
            ?? throw new \LogicException('The membership was just created.');

        return $this->json($gameSerializer->serializeForPlayer($membership));
    }

    /**
     * The player keeps their characters of the game, but they are disabled.
     */
    #[Route('/{id}/leave', name: 'api_games_leave', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function leave(
        int $id,
        #[CurrentUser] User $user,
        GameRepository $gameRepository,
        GameMembershipManager $gameMembershipManager,
    ): JsonResponse {
        // Same answer for an unknown game and a game the user does not play in.
        $notFound = $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);

        $game = $gameRepository->find($id);
        if (!$game instanceof Game) {
            return $notFound;
        }

        try {
            $gameMembershipManager->leave($game, $user);
        } catch (NotAMemberException) {
            return $notFound;
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
