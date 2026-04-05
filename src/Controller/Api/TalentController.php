<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Talent;
use App\Entity\TalentLevel;
use App\Enum\TierEnum;
use App\Repository\CharacterRepository;
use App\Repository\TalentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class TalentController extends AbstractController
{
    #[Route('/talents/import', name: 'api_talent_import', methods: ['POST'])]
    public function import(
        Request $request,
        TalentRepository $talentRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $body = json_decode($request->getContent(), true);

        if (!isset($body['talents']) || !is_array($body['talents'])) {
            return $this->json(['error' => 'Field "talents" is required and must be an array'], Response::HTTP_BAD_REQUEST);
        }

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($body['talents'] as $index => $talentData) {
            if (empty($talentData['name'])) {
                $errors[] = sprintf('Entry %d: field "name" is required', $index);
                continue;
            }

            $talent = $talentRepository->findOneByName($talentData['name']);

            if (!$talent) {
                $talent = (new Talent())
                    ->setName($talentData['name'])
                    ->setDescription($talentData['description'] ?? '')
                    ->setIsReady(true)
                    ->setIsPrivate(false);
                $em->persist($talent);
                ++$created;
            } else {
                $talent->setDescription($talentData['description'] ?? $talent->getDescription());
                ++$updated;
            }

            $existingTiers = $talent->getTalentLevels()
                ->map(fn (TalentLevel $tl) => $tl->getTier()->value)
                ->toArray();

            foreach ($talentData['talentLevels'] ?? [] as $levelData) {
                $tierValue = $levelData['tiers'] ?? null;

                $tier = TierEnum::tryFrom((string) $tierValue);
                if (!$tier) {
                    $errors[] = sprintf('Talent "%s": unknown tier "%s"', $talentData['name'], $tierValue);
                    continue;
                }

                if (in_array($tier->value, $existingTiers, true)) {
                    continue;
                }

                $talentLevel = (new TalentLevel())
                    ->setTier($tier)
                    ->setRequiredPoints((int) ($levelData['requiredPoints'] ?? 0))
                    ->setDescription($levelData['description'] ?? '');

                $talent->addTalentLevel($talentLevel);
                $existingTiers[] = $tier->value;
            }
        }

        $em->flush();

        return $this->json([
            'created' => $created,
            'updated' => $updated,
            'errors'  => $errors,
        ], Response::HTTP_OK);
    }

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
            'talentId'     => $talent->getId(),
            'talentName'   => $talent->getName(),
            'currentValue' => $characterValue,
            'talentLevels' => array_map(fn (TalentLevel $tl) => [
                'tier'           => $tl->getTier()->value,
                'requiredPoints' => $tl->getRequiredPoints(),
                'description'    => $tl->getDescription(),
            ], $unlockedLevels),
        ]);
    }
}
