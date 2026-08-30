<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Calculator\LoadCalculator;
use App\Entity\Armor;
use App\Entity\Character;
use App\Entity\KindTalentBonus;
use App\Entity\Weapon;
use App\Repository\CharacterRepository;
use App\ValueObject\DamageLine;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class CharacterController extends AbstractController
{
    private const array ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    private const string AVATAR_UPLOAD_DIR = '/uploads/avatars';
    private const string AVATAR_MAX_SIZE = '5M';

    #[Route('/characters/{token}', name: 'api_character_show', methods: ['GET'])]
    public function show(string $token, Request $request, CharacterRepository $characterRepository, LoadCalculator $loadCalculator): JsonResponse
    {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$character->getKind()) {
            return $this->json(['error' => 'Character has no kind'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->serializeCharacter($character, $request, $loadCalculator));
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCharacter(Character $character, Request $request, LoadCalculator $loadCalculator): array
    {
        $kind = $character->getKind();
        $characterClass = $character->getCharacterClass();

        $serializeDamageLines = fn ($entity) => array_map(
            fn (DamageLine $line) => $line->toArray(),
            $entity->getDamageLines(),
        );

        $serializeSkill = fn ($skill) => [
            'id' => $skill->getId(),
            'name' => $skill->getName(),
            'description' => $skill->getDescription(),
            'exhaustPointCost' => $skill->getExhaustPointCost(),
            'actionPointCost' => $skill->getActionPointCost(),
            'damageLines' => $serializeDamageLines($skill),
            'isPassive' => $skill->isPassive(),
        ];

        $serializeEquipment = fn ($equipment) => [
            'id' => $equipment->getId(),
            'name' => $equipment->getName(),
            'value' => $equipment->getValue(),
            'weight' => $equipment->getWeight(),
            'currentDurabilityPoints' => $equipment->getCurrentDurabilityPoints(),
            'maxDurabilityPoints' => $equipment->getMaxDurabilityPoints(),
            'description' => $equipment->getDescription(),
            'isEquipped' => $equipment->isEquipped(),
            'damageLines' => $serializeDamageLines($equipment),
            'skills' => array_map($serializeSkill, $equipment->getSkills()->toArray()),
        ];

        $allEquipments = $character->getEquipments()->toArray();

        $weapons = array_map(
            $serializeEquipment,
            array_values(array_filter($allEquipments, fn ($e) => $e instanceof Weapon)),
        );

        $armors = array_map(
            $serializeEquipment,
            array_values(array_filter($allEquipments, fn ($e) => $e instanceof Armor)),
        );

        $spells = array_map(
            fn ($spell) => [
                'id' => $spell->getId(),
                'name' => $spell->getName(),
                'description' => $spell->getDescription(),
                'school' => $spell->getSchool(),
                'manaCost' => $spell->getManaCost(),
                'actionPointCost' => $spell->getActionPointCost(),
                'damageLines' => $serializeDamageLines($spell),
                'range' => $spell->getRange(),
                'impactZone' => $spell->getImpactZone(),
                'duration' => $spell->getDuration(),
                'type' => $spell->getType(),
                'isPassive' => $spell->isPassive(),
            ],
            $character->getSpells()->toArray(),
        );

        $skills = array_map($serializeSkill, $character->getSkills()->toArray());

        $talents = array_map(
            function ($characterTalent) {
                $value = $characterTalent->getValue();
                $unlockedLevels = array_values(array_filter(
                    $characterTalent->getTalent()->getTalentLevels()->toArray(),
                    fn ($tl) => $tl->getRequiredPoints() <= $value,
                ));

                return [
                    'name' => $characterTalent->getName(),
                    'value' => $value,
                    'talentLevels' => array_map(fn ($tl) => [
                        'tier' => $tl->getTier()->value,
                        'requiredPoints' => $tl->getRequiredPoints(),
                        'description' => $tl->getDescription(),
                    ], $unlockedLevels),
                ];
            },
            $character->getTalents()->toArray(),
        );

        $items = array_map(
            fn ($beingItem) => [
                'id' => $beingItem->getItem()->getId(),
                'name' => $beingItem->getItem()->getName(),
                'description' => $beingItem->getItem()->getDescription(),
                'value' => $beingItem->getItem()->getValue(),
                'weight' => $beingItem->getItem()->getWeight(),
                'quantity' => $beingItem->getQuantity(),
            ],
            $character->getItems()->toArray(),
        );

        $primaryTalents = array_map(
            fn ($talent) => ['id' => $talent->getId(), 'name' => $talent->getName()],
            $character->getPrimaryTalents()->toArray(),
        );

        $secondaryTalents = array_map(
            fn ($talent) => ['id' => $talent->getId(), 'name' => $talent->getName()],
            $character->getSecondaryTalents()->toArray(),
        );

        return [
            'id' => $character->getId(),
            'token' => $character->getToken(),
            'name' => $character->getName(),
            'lastName' => $character->getLastName(),
            'level' => $character->getLevel(),
            'health' => [
                'current' => $character->getCurrentHealthPoints(),
                'max' => $character->getMaxHealthPoints(),
            ],
            'mana' => [
                'current' => $character->getCurrentManaPoints(),
                'max' => $character->getMaxManaPoints(),
            ],
            'actionPoints' => [
                'current' => $character->getCurrentActionPoints(),
                'max' => $character->getMaxActionPoints(),
            ],
            'exhaustPoints' => [
                'current' => $character->getCurrentExhaustPoints(),
                'max' => $character->getMaxExhaustPoints(),
            ],
            'loadPoints' => [
                'current' => $loadCalculator->computeCurrentLoadPoints($character),
                'max' => $character->getMaxLoadPoints(),
            ],
            'mentalPoints' => [
                'current' => $character->getCurrentMentalPoints(),
                'max' => $character->getMaxMentalPoints(),
            ],
            'avatarUrl' => $character->getAvatarUrl()
                ? $request->getSchemeAndHttpHost().$character->getAvatarUrl()
                : null,
            'kind' => $kind ? [
                'id' => $kind->getId(),
                'name' => $kind->getName(),
                'bonuses' => array_map(
                    function ($bonus) {
                        if ($bonus instanceof KindTalentBonus) {
                            return [
                                'type' => 'talent',
                                'talent' => ['id' => $bonus->getTalent()->getId(), 'name' => $bonus->getTalent()->getName()],
                                'value' => $bonus->getValue(),
                            ];
                        }

                        return null;
                    },
                    $kind->getBonuses()->toArray(),
                ),
            ] : null,
            'characterClass' => $characterClass ? ['id' => $characterClass->getId(), 'name' => $characterClass->getName()] : null,
            'weapons' => $weapons,
            'armors' => $armors,
            'items' => $items,
            'spells' => $spells,
            'skills' => $skills,
            'talents' => $talents,
            'primaryTalents' => $primaryTalents,
            'secondaryTalents' => $secondaryTalents,
        ];
    }

    #[Route('/characters/{token}/level-up', name: 'api_character_level_up', methods: ['POST'])]
    public function levelUp(string $token, Request $request, CharacterRepository $characterRepository): JsonResponse
    {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true);

        if (!is_array($body) || !isset($body['stats'], $body['talents'])) {
            return $this->json(['error' => 'Fields "stats" and "talents" are required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $character->levelUp($body['stats'], $body['talents']);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $characterRepository->save($character);

        return $this->json(['level' => $character->getLevel()], Response::HTTP_OK);
    }

    #[Route('/characters/{token}/stats', name: 'api_character_stats_update', methods: ['PATCH'])]
    public function updateStats(
        string $token,
        Request $request,
        CharacterRepository $characterRepository,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true);

        if (!is_array($body)) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $map = [
            'currentHealthPoints' => ['setCurrentHealthPoints', 'getMaxHealthPoints'],
            'currentManaPoints' => ['setCurrentManaPoints', 'getMaxManaPoints'],
            'currentActionPoints' => ['setCurrentActionPoints', 'getMaxActionPoints'],
            'currentExhaustPoints' => ['setCurrentExhaustPoints', 'getMaxExhaustPoints'],
            'currentMentalPoints' => ['setCurrentMentalPoints', 'getMaxMentalPoints'],
        ];

        foreach ($map as $field => [$setter, $maxGetter]) {
            if (!array_key_exists($field, $body)) {
                continue;
            }

            $value = $body[$field];
            if (!is_int($value)) {
                return $this->json(
                    ['error' => sprintf('Field "%s" must be an integer', $field)],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            $max = $character->$maxGetter();
            if ($value < 0 || $value > $max) {
                return $this->json(
                    ['error' => sprintf('Field "%s" must be between 0 and %d', $field, $max)],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            $character->$setter($value);
        }

        $characterRepository->save($character);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/characters/{token}', name: 'api_character_delete', methods: ['DELETE'])]
    public function delete(string $token, CharacterRepository $characterRepository): JsonResponse
    {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $characterRepository->delete($character);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/characters/{token}/avatar', name: 'api_character_avatar_upload', methods: ['POST'])]
    public function uploadAvatar(
        string $token,
        Request $request,
        CharacterRepository $characterRepository,
        ValidatorInterface $validator,
        #[Autowire('%kernel.project_dir%')] string $projectDir,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $file = $request->files->get('avatar');

        if (!$file instanceof UploadedFile) {
            return $this->json(['error' => 'No file provided'], Response::HTTP_BAD_REQUEST);
        }

        $violations = $validator->validate($file, new Assert\Image([
            'maxSize' => self::AVATAR_MAX_SIZE,
            'mimeTypes' => self::ALLOWED_MIME_TYPES,
            'mimeTypesMessage' => 'Invalid file type. Allowed: jpeg, png, webp.',
            'maxSizeMessage' => 'Avatar must be smaller than '.self::AVATAR_MAX_SIZE.'.',
        ]));

        foreach ($violations as $violation) {
            return $this->json(
                ['error' => $violation->getMessage()],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $extension = $file->guessExtension() ?? match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'bin',
        };

        $filename = bin2hex(random_bytes(16)).'.'.$extension;
        $uploadDir = $projectDir.'/public'.self::AVATAR_UPLOAD_DIR;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $previousAvatarUrl = $character->getAvatarUrl();

        $file->move($uploadDir, $filename);

        $avatarPath = self::AVATAR_UPLOAD_DIR.'/'.$filename;

        try {
            $character->setAvatarUrl($avatarPath);
            $characterRepository->save($character);
        } catch (\Throwable $e) {
            @unlink($uploadDir.'/'.$filename);
            throw $e;
        }

        if ($previousAvatarUrl) {
            $previousFile = $projectDir.'/public'.$previousAvatarUrl;
            if (is_file($previousFile)) {
                @unlink($previousFile);
            }
        }

        return $this->json(
            ['avatarUrl' => $request->getSchemeAndHttpHost().$avatarPath],
            Response::HTTP_OK,
        );
    }

    #[Route('/characters/{token}/avatar', name: 'api_character_avatar_delete', methods: ['DELETE'])]
    public function deleteAvatar(
        string $token,
        CharacterRepository $characterRepository,
        #[Autowire('%kernel.project_dir%')] string $projectDir,
    ): JsonResponse {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $avatarUrl = $character->getAvatarUrl();

        if ($avatarUrl) {
            $character->setAvatarUrl(null);
            $characterRepository->save($character);

            $file = $projectDir.'/public'.$avatarUrl;
            if (is_file($file)) {
                @unlink($file);
            }
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
