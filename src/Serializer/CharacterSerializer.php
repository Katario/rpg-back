<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Calculator\LoadCalculator;
use App\Entity\Armor;
use App\Entity\Character;
use App\Entity\KindTalentBonus;
use App\Entity\Weapon;
use App\ValueObject\DamageLine;

/**
 * Single source of truth for the full character read model.
 *
 * Both the character sheet endpoint (GET /api/characters/{token}) and the export
 * endpoint call this service, so the payload can never diverge between them.
 */
class CharacterSerializer
{
    public function __construct(
        private readonly LoadCalculator $loadCalculator,
    ) {
    }

    /**
     * @param string $baseUrl scheme + host used to build the absolute avatar URL (e.g. "https://api.example.com")
     *
     * @return array<string, mixed>
     */
    public function serialize(Character $character, string $baseUrl): array
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
                $allLevels = array_values($characterTalent->getTalent()->getTalentLevels()->toArray());
                $unlockedLevels = array_values(array_filter(
                    $allLevels,
                    fn ($tl) => $tl->getRequiredPoints() <= $value,
                ));
                $serializeLevel = fn ($tl) => [
                    'tier' => $tl->getTier()->value,
                    'requiredPoints' => $tl->getRequiredPoints(),
                    'description' => $tl->getDescription(),
                ];

                return [
                    'name' => $characterTalent->getName(),
                    'value' => $value,
                    // Levels unlocked by the character's value (what the sheet displays).
                    'talentLevels' => array_map($serializeLevel, $unlockedLevels),
                    // Every level of the talent, locked ones included: lets an import recreate
                    // the full encyclopedia entry on a database that does not have it yet.
                    'allTalentLevels' => array_map($serializeLevel, $allLevels),
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
                'current' => $this->loadCalculator->computeCurrentLoadPoints($character),
                'max' => $character->getMaxLoadPoints(),
            ],
            'mentalPoints' => [
                'current' => $character->getCurrentMentalPoints(),
                'max' => $character->getMaxMentalPoints(),
            ],
            'avatarUrl' => $character->getAvatarUrl()
                ? $baseUrl.$character->getAvatarUrl()
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
}
