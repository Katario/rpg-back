<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\CharacterRepository;
use App\Serializer\CharacterSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ExportController extends AbstractController
{
    #[Route('/characters/{token}/export', name: 'api_character_export', methods: ['GET'])]
    public function export(
        string $token,
        Request $request,
        CharacterRepository $characterRepository,
        CharacterSerializer $characterSerializer,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$character->getKind()) {
            return $this->json(['error' => 'Character has no kind'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($characterSerializer->serialize($character, $request->getSchemeAndHttpHost()));
    }
}
