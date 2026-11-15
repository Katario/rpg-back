<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\CharacterRepository;
use App\Repository\GameRepository;
use App\Security\Voter\GameVoter;
use App\Serializer\CharacterDeserializer;
use App\Serializer\CharacterSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api')]
class ImportController extends AbstractController
{
    #[Route('/characters/import', name: 'api_character_import', methods: ['POST'])]
    public function import(
        Request $request,
        GameRepository $gameRepository,
        CharacterRepository $characterRepository,
        CharacterDeserializer $characterDeserializer,
        CharacterSerializer $characterSerializer,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        $body = json_decode($request->getContent(), true);

        if (!is_array($body) || !isset($body['data']) || !is_array($body['data'])) {
            return $this->json(['error' => 'Missing or invalid "data"'], Response::HTTP_BAD_REQUEST);
        }

        $data = $body['data'];

        // Signed in: the game is required and must be one the user plays in (player or game master).
        // Anonymous (legacy, until production is migrated to accounts): defaults to game 1, any game is accepted.
        if (null !== $user && !is_int($body['gameId'] ?? null)) {
            return $this->json(['error' => 'Field "gameId" is required'], Response::HTTP_BAD_REQUEST);
        }

        $game = $gameRepository->find($body['gameId'] ?? 1);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }

        if ($game->isArchived()) {
            return $this->json(['error' => 'This game is archived'], Response::HTTP_CONFLICT);
        }

        if (null !== $user && !$this->isGranted(GameVoter::PLAY, $game)) {
            return $this->json(['error' => 'You do not play in this game'], Response::HTTP_FORBIDDEN);
        }

        if (!isset($data['kind']) || !is_array($data['kind'])) {
            return $this->json(['error' => 'Character has no kind'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (isset($data['token']) && is_string($data['token']) && $characterRepository->findOneByToken($data['token'])) {
            return $this->json(['error' => 'A character with this token already exists'], Response::HTTP_CONFLICT);
        }

        $character = $characterDeserializer->deserialize($data, $game);
        $character->setUser($user);
        $characterRepository->save($character);

        return $this->json(
            $characterSerializer->serialize($character, $request->getSchemeAndHttpHost()),
            Response::HTTP_CREATED,
        );
    }
}
