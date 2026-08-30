<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\TalentLevel;
use App\Repository\CharacterRepository;
use App\Repository\TalentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class TalentController extends AbstractController
{
    #[Route('/characters/{token}/talents/{talentId}/levels', name: 'api_character_talent_levels', methods: ['GET'])]
    public function levels(
        string $token,
        int $talentId,
        CharacterRepository $characterRepository,
        TalentRepository $talentRepository,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $talent = $talentRepository->find($talentId);

        if (!$talent) {
            return $this->json(['error' => 'Talent not found'], Response::HTTP_NOT_FOUND);
        }

        $beingTalent = $character->getTalents()->filter(
            fn ($bt) => $bt->getTalent()->getId() === $talentId,
        )->first();

        $characterValue = $beingTalent ? $beingTalent->getValue() : 0;

        $unlockedLevels = array_values(array_filter(
            $talent->getTalentLevels()->toArray(),
            fn (TalentLevel $tl) => $tl->getRequiredPoints() <= $characterValue,
        ));

        return $this->json([
            'talentId' => $talent->getId(),
            'talentName' => $talent->getName(),
            'currentValue' => $characterValue,
            'talentLevels' => array_map(fn (TalentLevel $tl) => [
                'tier' => $tl->getTier()->value,
                'requiredPoints' => $tl->getRequiredPoints(),
                'description' => $tl->getDescription(),
            ], $unlockedLevels),
        ]);
    }
}
