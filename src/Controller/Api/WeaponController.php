<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Skill;
use App\Entity\Weapon;
use App\Repository\CharacterRepository;
use App\Repository\SkillRepository;
use App\Repository\WeaponRepository;
use App\ValueObject\DamageLine;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class WeaponController extends AbstractController
{
    #[Route('/characters/{token}/weapons', name: 'api_character_weapon_add', methods: ['POST'])]
    public function add(
        string $token,
        Request $request,
        CharacterRepository $characterRepository,
        WeaponRepository $weaponRepository,
        SkillRepository $skillRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true);

        if (empty($body['name'])) {
            return $this->json(['error' => 'Field "name" is required'], Response::HTTP_BAD_REQUEST);
        }

        $weapon = (new Weapon())
            ->setName($body['name'])
            ->setDescription($body['description'] ?? '')
            ->setCurrentDurabilityPoints($body['currentDurabilityPoints'] ?? 0)
            ->setMaxDurabilityPoints($body['maxDurabilityPoints'] ?? 0)
            ->setWeight($body['weight'] ?? 0)
            ->setIsEquipped($body['isEquipped'] ?? false)
            ->setValue(0)
            ->setGame($character->getGame())
            ->setBeing($character);

        if (isset($body['damageLines'])) {
            $weapon->setDamageLines(array_map(
                fn (array $line) => DamageLine::fromArray($line),
                $body['damageLines'],
            ));
        }

        foreach ($body['skills'] ?? [] as $skillData) {
            if (empty($skillData['name'])) {
                continue;
            }
            $skill = $skillRepository->findOneByName($skillData['name']);
            if (!$skill) {
                $skill = (new Skill())
                    ->setName($skillData['name'])
                    ->setDescription('')
                    ->setExhaustPointCost((int) ($skillData['exhaustPointCost'] ?? 0))
                    ->setActionPointCost((int) ($skillData['actionPointCost'] ?? 0))
                    ->setDamageLines(array_map(
                        fn (array $line) => DamageLine::fromArray($line),
                        $skillData['damageLines'] ?? [],
                    ))
                    ->setIsReady(true)
                    ->setIsPrivate(false);
                $em->persist($skill);
            }
            $weapon->addSkill($skill);
        }

        $weaponRepository->save($weapon);

        return $this->json([
            'id'                      => $weapon->getId(),
            'name'                    => $weapon->getName(),
            'value'                   => $weapon->getValue(),
            'weight'                  => $weapon->getWeight(),
            'currentDurabilityPoints' => $weapon->getCurrentDurabilityPoints(),
            'maxDurabilityPoints'     => $weapon->getMaxDurabilityPoints(),
            'description'             => $weapon->getDescription(),
            'isEquipped'              => $weapon->isEquipped(),
            'damageLines'             => array_map(fn (DamageLine $l) => $l->toArray(), $weapon->getDamageLines()),
            'skills'                  => array_map(fn ($skill) => [
                'id'               => $skill->getId(),
                'name'             => $skill->getName(),
                'description'      => $skill->getDescription(),
                'exhaustPointCost' => $skill->getExhaustPointCost(),
                'actionPointCost'  => $skill->getActionPointCost(),
                'damageLines'      => array_map(fn (DamageLine $l) => $l->toArray(), $skill->getDamageLines()),
            ], $weapon->getSkills()->toArray()),
        ], Response::HTTP_CREATED);
    }

    #[Route('/characters/{token}/weapons/{id}', name: 'api_character_weapon_update', methods: ['PATCH'])]
    public function update(
        string $token,
        int $id,
        Request $request,
        CharacterRepository $characterRepository,
        WeaponRepository $weaponRepository,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $weapon = $weaponRepository->find($id);

        if (!$weapon || $weapon->getBeing()?->getId() !== $character->getId()) {
            return $this->json(['error' => 'Weapon not found'], Response::HTTP_NOT_FOUND);
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
                $weapon->$setter($body[$field]);
            }
        }

        if (array_key_exists('damageLines', $body)) {
            $weapon->setDamageLines(array_map(
                fn (array $line) => DamageLine::fromArray($line),
                $body['damageLines'],
            ));
        }

        $weaponRepository->save($weapon);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/characters/{token}/weapons/{id}', name: 'api_character_weapon_delete', methods: ['DELETE'])]
    public function delete(
        string $token,
        int $id,
        CharacterRepository $characterRepository,
        WeaponRepository $weaponRepository,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $weapon = $weaponRepository->find($id);

        if (!$weapon || $weapon->getBeing()?->getId() !== $character->getId()) {
            return $this->json(['error' => 'Weapon not found'], Response::HTTP_NOT_FOUND);
        }

        $weaponRepository->delete($weapon);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/characters/{token}/weapons/{id}/skills', name: 'api_character_weapon_skill_add', methods: ['POST'])]
    public function addSkill(
        string $token,
        int $id,
        Request $request,
        CharacterRepository $characterRepository,
        WeaponRepository $weaponRepository,
        SkillRepository $skillRepository,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $weapon = $weaponRepository->find($id);

        if (!$weapon || $weapon->getBeing()?->getId() !== $character->getId()) {
            return $this->json(['error' => 'Weapon not found'], Response::HTTP_NOT_FOUND);
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

        $weapon->addSkill($skill);
        $weaponRepository->save($weapon);

        return $this->json(['id' => $skill->getId()], Response::HTTP_OK);
    }

    #[Route('/characters/{token}/weapons/{id}/skills/{skillId}', name: 'api_character_weapon_skill_delete', methods: ['DELETE'])]
    public function removeSkill(
        string $token,
        int $id,
        int $skillId,
        CharacterRepository $characterRepository,
        WeaponRepository $weaponRepository,
        SkillRepository $skillRepository,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $weapon = $weaponRepository->find($id);

        if (!$weapon || $weapon->getBeing()?->getId() !== $character->getId()) {
            return $this->json(['error' => 'Weapon not found'], Response::HTTP_NOT_FOUND);
        }

        $skill = $skillRepository->find($skillId);

        if (!$skill) {
            return $this->json(['error' => 'Skill not found'], Response::HTTP_NOT_FOUND);
        }

        $weapon->removeSkill($skill);
        $weaponRepository->save($weapon);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
