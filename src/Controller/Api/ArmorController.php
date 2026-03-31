<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Armor;
use App\Entity\Skill;
use App\Repository\ArmorRepository;
use App\Repository\CharacterRepository;
use App\Repository\SkillRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ArmorController extends AbstractController
{
    #[Route('/characters/{token}/armors', name: 'api_character_armor_add', methods: ['POST'])]
    public function add(
        string $token,
        Request $request,
        CharacterRepository $characterRepository,
        ArmorRepository $armorRepository,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true);

        if (empty($body['name'])) {
            return $this->json(['error' => 'Field "name" is required'], Response::HTTP_BAD_REQUEST);
        }

        $armor = (new Armor())
            ->setName($body['name'])
            ->setDescription($body['description'] ?? '')
            ->setCurrentDurabilityPoints($body['currentDurabilityPoints'] ?? 0)
            ->setMaxDurabilityPoints($body['maxDurabilityPoints'] ?? 0)
            ->setWeight($body['weight'] ?? 0)
            ->setIsEquipped($body['isEquipped'] ?? false)
            ->setValue(0)
            ->setGame($character->getGame())
            ->setBeing($character);

        $armorRepository->save($armor);

        return $this->json(['id' => $armor->getId()], Response::HTTP_CREATED);
    }

    #[Route('/characters/{token}/armors/{id}', name: 'api_character_armor_update', methods: ['PATCH'])]
    public function update(
        string $token,
        int $id,
        Request $request,
        CharacterRepository $characterRepository,
        ArmorRepository $armorRepository,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $armor = $armorRepository->find($id);

        if (!$armor || $armor->getBeing()?->getId() !== $character->getId()) {
            return $this->json(['error' => 'Armor not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true);

        if (!is_array($body)) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $map = [
            'name'                    => 'setName',
            'description'             => 'setDescription',
            'currentDurabilityPoints' => 'setCurrentDurabilityPoints',
            'maxDurabilityPoints'     => 'setMaxDurabilityPoints',
            'weight'                  => 'setWeight',
            'isEquipped'              => 'setIsEquipped',
        ];

        foreach ($map as $field => $setter) {
            if (array_key_exists($field, $body)) {
                $armor->$setter($body[$field]);
            }
        }

        $armorRepository->save($armor);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/characters/{token}/armors/{id}', name: 'api_character_armor_delete', methods: ['DELETE'])]
    public function delete(
        string $token,
        int $id,
        CharacterRepository $characterRepository,
        ArmorRepository $armorRepository,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $armor = $armorRepository->find($id);

        if (!$armor || $armor->getBeing()?->getId() !== $character->getId()) {
            return $this->json(['error' => 'Armor not found'], Response::HTTP_NOT_FOUND);
        }

        $armorRepository->delete($armor);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/characters/{token}/armors/{id}/skills', name: 'api_character_armor_skill_add', methods: ['POST'])]
    public function addSkill(
        string $token,
        int $id,
        Request $request,
        CharacterRepository $characterRepository,
        ArmorRepository $armorRepository,
        SkillRepository $skillRepository,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $armor = $armorRepository->find($id);

        if (!$armor || $armor->getBeing()?->getId() !== $character->getId()) {
            return $this->json(['error' => 'Armor not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true);

        if (empty($body['name'])) {
            return $this->json(['error' => 'Field "name" is required'], Response::HTTP_BAD_REQUEST);
        }

        $skill = $skillRepository->findOneByName($body['name']);
        if (!$skill) {
            $skill = (new Skill())
                ->setName($body['name'])
                ->setDescription('')
                ->setExhaustPointCost(0)
                ->setActionPointCost(0)
                ->setIsReady(true)
                ->setIsPrivate(false);
            $skillRepository->save($skill);
        }

        $armor->addSkill($skill);
        $armorRepository->save($armor);

        return $this->json(['id' => $skill->getId()], Response::HTTP_OK);
    }

    #[Route('/characters/{token}/armors/{id}/skills/{skillId}', name: 'api_character_armor_skill_delete', methods: ['DELETE'])]
    public function removeSkill(
        string $token,
        int $id,
        int $skillId,
        CharacterRepository $characterRepository,
        ArmorRepository $armorRepository,
        SkillRepository $skillRepository,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $armor = $armorRepository->find($id);

        if (!$armor || $armor->getBeing()?->getId() !== $character->getId()) {
            return $this->json(['error' => 'Armor not found'], Response::HTTP_NOT_FOUND);
        }

        $skill = $skillRepository->find($skillId);

        if (!$skill) {
            return $this->json(['error' => 'Skill not found'], Response::HTTP_NOT_FOUND);
        }

        $armor->removeSkill($skill);
        $armorRepository->save($armor);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
