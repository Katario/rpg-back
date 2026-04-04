<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Armor;
use App\Entity\BeingItem;
use App\Entity\BeingTalent;
use App\Entity\Character;
use App\Entity\Item;
use App\Entity\Kind;
use App\Entity\KindTalentBonus;
use App\Entity\Skill;
use App\Entity\Spell;
use App\Entity\Talent;
use App\Entity\Weapon;
use App\ValueObject\Damage;
use App\Calculator\LoadCalculator;
use App\Repository\CharacterRepository;
use App\Repository\GameRepository;
use App\Repository\ItemRepository;
use App\Repository\KindRepository;
use App\Repository\SkillRepository;
use App\Repository\SpellRepository;
use App\Repository\TalentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class CharacterController extends AbstractController
{
    private const array ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    private const string AVATAR_UPLOAD_DIR = '/uploads/avatars';

    #[Route('/characters/import', name: 'api_character_import', methods: ['POST'])]
    public function import(
        Request $request,
        EntityManagerInterface $em,
        GameRepository $gameRepository,
        CharacterRepository $characterRepository,
        KindRepository $kindRepository,
        TalentRepository $talentRepository,
        SpellRepository $spellRepository,
        ItemRepository $itemRepository,
        SkillRepository $skillRepository,
    ): JsonResponse {
        $body = json_decode($request->getContent(), true);

        if (!isset($body['gameId'], $body['data']) || !is_array($body['data'])) {
            return $this->json(['error' => 'Missing or invalid gameId or data'], Response::HTTP_BAD_REQUEST);
        }

        $game = $gameRepository->find($body['gameId']);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $body['data'];

        $kind = !empty($data['race']['name']) ? $kindRepository->findOneByName($data['race']['name']) : null;

        if ($characterRepository->findDuplicate($data['name'], $game, $kind)) {
            return $this->json(['error' => 'A character with this name already exists in this game'], Response::HTTP_CONFLICT);
        }

        if (!empty($data['race']['name']) && !$kind) {
            $kind = new Kind()
                ->setName($data['race']['name'])
                ->setIsReady(true)
                ->setIsPrivate(false);
            $em->persist($kind);

            foreach ($data['race']['bonuses'] ?? [] as $bonusData) {
                $talent = $talentRepository->findOneByName($bonusData['skill']);
                if (!$talent) {
                    $talent = new Talent()
                        ->setName($bonusData['skill'])
                        ->setDescription('')
                        ->setIsReady(true)
                        ->setIsPrivate(false);
                    $em->persist($talent);
                }

                $bonus = new KindTalentBonus()
                    ->setTalent($talent)
                    ->setValue((int) $bonusData['bonus']);
                $kind->addBonus($bonus);
            }
        }

        $stats = $data['stats'];

        $character = new Character();
        $character
            ->setName($data['name'])
            ->setLevel((int) $data['level'])
            ->setToken(bin2hex(random_bytes(16)))
            ->setGame($game)
            ->setCurrentHealthPoints($stats['pv']['current'])
            ->setMaxHealthPoints($stats['pv']['max'])
            ->setCurrentManaPoints($stats['ma']['current'])
            ->setMaxManaPoints($stats['ma']['max'])
            ->setCurrentActionPoints($stats['pa']['current'])
            ->setMaxActionPoints($stats['pa']['max'])
            ->setCurrentExhaustPoints($stats['fa']['current'])
            ->setMaxExhaustPoints($stats['fa']['max'])
            ->setMaxLoadPoints($stats['ch']['max'])
            ->setCurrentMentalPoints($stats['sm']['current'])
            ->setMaxMentalPoints($stats['sm']['max'])
        ;

        if ($kind) {
            $character->setKind($kind);
        }

        $em->persist($character);

        foreach ($data['primarySkills'] ?? [] as $talentName) {
            $talent = $talentRepository->findOneByName($talentName);
            if (!$talent) {
                $talent = new Talent()
                    ->setName($talentName)
                    ->setDescription('')
                    ->setIsReady(true)
                    ->setIsPrivate(false);
                $em->persist($talent);
            }
            $character->addPrimaryTalent($talent);
        }

        foreach ($data['secondarySkills'] ?? [] as $talentName) {
            $talent = $talentRepository->findOneByName($talentName);
            if (!$talent) {
                $talent = new Talent()
                    ->setName($talentName)
                    ->setDescription('')
                    ->setIsReady(true)
                    ->setIsPrivate(false);
                $em->persist($talent);
            }
            $character->addSecondaryTalent($talent);
        }

        $allSkills = array_merge($data['skills1'] ?? [], $data['skills2'] ?? [], $data['skills3'] ?? []);
        foreach ($allSkills as $skillData) {
            $talent = $talentRepository->findOneByName($skillData['name']);
            if (!$talent) {
                $talent = new Talent()
                    ->setName($skillData['name'])
                    ->setDescription('')
                    ->setIsReady(true)
                    ->setIsPrivate(false);
                $em->persist($talent);
            }

            $beingTalent = new BeingTalent()
                ->setBeing($character)
                ->setTalent($talent)
                ->setValue((int) $skillData['value']);
            $em->persist($beingTalent);
        }

        foreach ($data['equipment'] ?? [] as $equipData) {
            $hasAttacks = !empty($equipData['attacks']);
            $isArmor = !$hasAttacks && ($equipData['type'] === 'armor' || ($equipData['hp']['max'] ?? 0) > 0);

            if ($hasAttacks || $isArmor) {
                $equipment = $hasAttacks ? new Weapon() : new Armor();
                $equipment
                    ->setName($equipData['name'])
                    ->setCurrentDurabilityPoints($equipData['hp']['current'] ?? 0)
                    ->setMaxDurabilityPoints($equipData['hp']['max'] ?? 0)
                    ->setDescription($equipData['description'] ?? '')
                    ->setValue(0)
                    ->setWeight($equipData['weight'] ?? 0)
                    ->setIsEquipped($equipData['equipped'] ?? false)
                    ->setGame($game)
                    ->setBeing($character);

                if ($equipment instanceof Weapon && !empty($equipData['attacks'])) {
                    $firstAttack = $equipData['attacks'][0];
                    $equipment->setDamage($this->parseDamageString($firstAttack['damage'] ?? ''));

                    foreach ($equipData['attacks'] as $attackData) {
                        $skill = $skillRepository->findOneByName($attackData['name']);
                        if (!$skill) {
                            $skill = new Skill()
                                ->setName($attackData['name'])
                                ->setDescription('')
                                ->setExhaustPointCost((int) ($attackData['faCost'] ?? 0))
                                ->setActionPointCost((int) ($attackData['paCost'] ?? 0))
                                ->setDamage($this->parseDamageString($attackData['damage'] ?? ''))
                                ->setIsReady(true)
                                ->setIsPrivate(false);
                            $em->persist($skill);
                        }
                        $equipment->addSkill($skill);
                    }
                }

                $em->persist($equipment);
            } else {
                $item = $itemRepository->findOneByName($equipData['name']);
                if (!$item) {
                    $item = new Item()
                        ->setName($equipData['name'])
                        ->setDescription($equipData['description'] ?? '')
                        ->setValue(0)
                        ->setWeight($equipData['weight'] ?? 0)
                        ->setIsReady(true)
                        ->setIsPrivate(false);
                    $em->persist($item);
                }
                $beingItem = new BeingItem()
                    ->setBeing($character)
                    ->setItem($item)
                    ->setQuantity(1);
                $em->persist($beingItem);
            }
        }

        foreach ($data['spells'] ?? [] as $spellData) {
            $spell = $spellRepository->findOneByName($spellData['name']);
            if (!$spell) {
                $spell = new Spell()
                    ->setName($spellData['name'])
                    ->setDescription($spellData['effect'] ?? '')
                    ->setManaCost($spellData['maCost'] ?? 0)
                    ->setActionPointCost($spellData['paCost'] ?? 0)
                    ->setDamage($this->parseDamageString($spellData['damage'] ?? ''))
                    ->setIsReady(true)
                    ->setIsPrivate(false);
                $em->persist($spell);
            }
            $character->addSpell($spell);
        }

        $em->flush();

        return $this->json(['token' => $character->getToken(), 'id' => $character->getId()], Response::HTTP_CREATED);
    }

    #[Route('/characters/{token}', name: 'api_character_show', methods: ['GET'])]
    public function show(string $token, Request $request, CharacterRepository $characterRepository, LoadCalculator $loadCalculator): JsonResponse
    {
        $character = $characterRepository->findOneByToken($token);

        if (!$character) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $kind = $character->getKind();
        $characterClass = $character->getCharacterClass();

        $serializeSkill = fn ($skill) => [
            'id'               => $skill->getId(),
            'name'             => $skill->getName(),
            'description'      => $skill->getDescription(),
            'exhaustPointCost' => $skill->getExhaustPointCost(),
            'actionPointCost'  => $skill->getActionPointCost(),
            'damage'           => ['dice' => $skill->getDamage()->getDice(), 'bonus' => $skill->getDamage()->getBonus()],
        ];

        $serializeEquipment = fn ($equipment) => [
            'id'                      => $equipment->getId(),
            'name'                    => $equipment->getName(),
            'value'                   => $equipment->getValue(),
            'weight'                  => $equipment->getWeight(),
            'currentDurabilityPoints' => $equipment->getCurrentDurabilityPoints(),
            'maxDurabilityPoints'     => $equipment->getMaxDurabilityPoints(),
            'description'             => $equipment->getDescription(),
            'isEquipped'              => $equipment->isEquipped(),
            'skills'                  => array_map($serializeSkill, $equipment->getSkills()->toArray()),
        ];

        $allEquipments = $character->getEquipments()->toArray();

        $weapons = array_map(function (Weapon $weapon) use ($serializeEquipment) {
            $damage = $weapon->getDamage();

            return array_merge($serializeEquipment($weapon), [
                'damage' => ['dice' => $damage->getDice(), 'bonus' => $damage->getBonus()],
            ]);
        }, array_values(array_filter($allEquipments, fn ($e) => $e instanceof Weapon)));

        $armors = array_map(
            $serializeEquipment,
            array_values(array_filter($allEquipments, fn ($e) => $e instanceof Armor)),
        );

        $spells = array_map(
            function ($spell) {
                $damage = $spell->getDamage();

                return [
                    'id'              => $spell->getId(),
                    'name'            => $spell->getName(),
                    'description'     => $spell->getDescription(),
                    'school'          => $spell->getSchool(),
                    'manaCost'        => $spell->getManaCost(),
                    'actionPointCost' => $spell->getActionPointCost(),
                    'damage'          => ['dice' => $damage->getDice(), 'bonus' => $damage->getBonus()],
                    'range'           => $spell->getRange(),
                    'impactZone'      => $spell->getImpactZone(),
                    'duration'        => $spell->getDuration(),
                    'type'            => $spell->getType(),
                ];
            },
            $character->getSpells()->toArray(),
        );

        $skills = array_map($serializeSkill, $character->getSkills()->toArray());

        $talents = array_map(
            fn ($characterTalent) => [
                'name' => $characterTalent->getName(),
                'value' => $characterTalent->getValue(),
            ],
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

        return $this->json([
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
        ]);
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
            'currentHealthPoints'  => 'setCurrentHealthPoints',
            'currentManaPoints'    => 'setCurrentManaPoints',
            'currentActionPoints'  => 'setCurrentActionPoints',
            'currentExhaustPoints' => 'setCurrentExhaustPoints',
            'currentMentalPoints'  => 'setCurrentMentalPoints',
        ];

        foreach ($map as $field => $setter) {
            if (array_key_exists($field, $body)) {
                $character->$setter((int) $body[$field]);
            }
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

    private function parseDamageString(string $damage): Damage
    {
        $dice = [];
        preg_match_all('/(\d+)d(\d+)/i', $damage, $diceMatches, PREG_SET_ORDER);
        foreach ($diceMatches as $match) {
            $dice[] = ['count' => (int) $match[1], 'faces' => (int) $match[2]];
        }

        $bonus = 0;
        if (preg_match('/\+\s*(\d+)\s*$/', $damage, $bonusMatch)) {
            $bonus = (int) $bonusMatch[1];
        }

        return new Damage($dice, $bonus);
    }

    #[Route('/characters/{token}/avatar', name: 'api_character_avatar_upload', methods: ['POST'])]
    public function uploadAvatar(
        string $token,
        Request $request,
        CharacterRepository $characterRepository,
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

        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            return $this->json(
                ['error' => 'Invalid file type. Allowed: jpeg, png, webp'],
                Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
            );
        }

        $filename = bin2hex(random_bytes(16)).'.'.$file->guessExtension();
        $uploadDir = $projectDir.'/public'.self::AVATAR_UPLOAD_DIR;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file->move($uploadDir, $filename);

        $avatarPath = self::AVATAR_UPLOAD_DIR.'/'.$filename;
        $character->setAvatarUrl($avatarPath);
        $characterRepository->save($character);

        return $this->json(
            ['avatarUrl' => $request->getSchemeAndHttpHost().$avatarPath],
            Response::HTTP_OK,
        );
    }
}
