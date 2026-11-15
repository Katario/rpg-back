<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Game\GameMembershipManager;
use App\Repository\CharacterRepository;
use App\Serializer\CharacterSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Characters of the signed-in user. The sheet itself is still read and played through the token routes.
 */
#[Route('/api/me/characters')]
#[IsGranted('IS_AUTHENTICATED')]
class MyCharacterController extends AbstractController
{
    #[Route('', name: 'api_me_characters', methods: ['GET'])]
    public function list(
        #[CurrentUser] User $user,
        Request $request,
        CharacterRepository $characterRepository,
        CharacterSerializer $characterSerializer,
    ): JsonResponse {
        $characters = $characterRepository->findBy(['user' => $user], ['name' => 'ASC']);

        return $this->json(array_map(
            fn ($character) => $characterSerializer->summarize($character, $request->getSchemeAndHttpHost()),
            $characters,
        ));
    }

    /**
     * Links an existing sheet (so far only reachable by its token) to the signed-in user.
     */
    #[Route('/claim', name: 'api_me_characters_claim', methods: ['POST'])]
    public function claim(
        #[CurrentUser] User $user,
        Request $request,
        CharacterRepository $characterRepository,
        CharacterSerializer $characterSerializer,
        GameMembershipManager $gameMembershipManager,
    ): JsonResponse {
        $body = json_decode($request->getContent(), true);

        if (!is_array($body) || !is_string($body['token'] ?? null) || '' === $body['token']) {
            return $this->json(['error' => 'Field "token" is required'], Response::HTTP_BAD_REQUEST);
        }

        $character = $characterRepository->findOneByToken($body['token']);
        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $owner = $character->getUser();
        if ($owner && $owner->getId() !== $user->getId()) {
            return $this->json(['error' => 'This character already belongs to another account'], Response::HTTP_CONFLICT);
        }

        // Only a new claim makes the user a player of the sheet's game: re-claiming an owned sheet must not bring back
        // a player the game master removed (their sheets stay owned, but disabled).
        if (!$owner) {
            $character->setUser($user);
            $characterRepository->save($character);
            $gameMembershipManager->addOwnerAsPlayer($character->getGame(), $user);
        }

        return $this->json($characterSerializer->summarize($character, $request->getSchemeAndHttpHost()));
    }
}
