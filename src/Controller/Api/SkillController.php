<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Skill;
use App\Repository\CharacterRepository;
use App\Repository\SkillRepository;
use App\ValueObject\DamageLine;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class SkillController extends AbstractController
{
    #[Route('/skills', name: 'api_skills_list', methods: ['GET'])]
    public function list(SkillRepository $skillRepository): JsonResponse
    {
        $skills = $skillRepository->findAll();

        return $this->json(array_map(fn (Skill $skill) => $this->serializeSkill($skill), $skills));
    }

    #[Route('/characters/{token}/skills', name: 'api_character_skill_attach', methods: ['POST'])]
    public function attach(
        string $token,
        Request $request,
        CharacterRepository $characterRepository,
        SkillRepository $skillRepository,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true);

        if (empty($body['skillId'])) {
            return $this->json(['error' => 'Field "skillId" is required'], Response::HTTP_BAD_REQUEST);
        }

        $skill = $skillRepository->find((int) $body['skillId']);

        if (!$skill) {
            return $this->json(['error' => 'Skill not found'], Response::HTTP_NOT_FOUND);
        }

        if ($character->getSkills()->contains($skill)) {
            return $this->json(['error' => 'Skill already attached'], Response::HTTP_CONFLICT);
        }

        $character->addSkill($skill);
        $characterRepository->save($character);

        return $this->json($this->serializeSkill($skill), Response::HTTP_CREATED);
    }

    #[Route('/characters/{token}/skills/{skillId}', name: 'api_character_skill_detach', methods: ['DELETE'])]
    public function detach(
        string $token,
        int $skillId,
        CharacterRepository $characterRepository,
        SkillRepository $skillRepository,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $skill = $skillRepository->find($skillId);

        if (!$skill || !$character->getSkills()->contains($skill)) {
            return $this->json(['error' => 'Skill not attached to character'], Response::HTTP_NOT_FOUND);
        }

        $character->removeSkill($skill);
        $characterRepository->save($character);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSkill(Skill $skill): array
    {
        return [
            'id' => $skill->getId(),
            'name' => $skill->getName(),
            'description' => $skill->getDescription(),
            'exhaustPointCost' => $skill->getExhaustPointCost(),
            'actionPointCost' => $skill->getActionPointCost(),
            'damageLines' => array_map(fn (DamageLine $l) => $l->toArray(), $skill->getDamageLines()),
            'isPassive' => $skill->isPassive(),
        ];
    }
}
