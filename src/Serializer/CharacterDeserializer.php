<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\Armor;
use App\Entity\BeingItem;
use App\Entity\BeingTalent;
use App\Entity\Character;
use App\Entity\CharacterClass;
use App\Entity\Game;
use App\Entity\Item;
use App\Entity\Kind;
use App\Entity\KindTalentBonus;
use App\Entity\Skill;
use App\Entity\Spell;
use App\Entity\Talent;
use App\Entity\TalentLevel;
use App\Entity\Weapon;
use App\Enum\TierEnum;
use App\Repository\CharacterClassRepository;
use App\Repository\ItemRepository;
use App\Repository\KindRepository;
use App\Repository\SkillRepository;
use App\Repository\SpellRepository;
use App\Repository\TalentRepository;
use App\ValueObject\DamageLine;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Inverse of {@see CharacterSerializer}: rebuilds a Character from an export payload
 * (the serializer's own output shape). The two live side by side so the mapping stays
 * symmetric — a new field is added to both.
 *
 * Shared encyclopedia entries (kind, class, skills, spells, items, talents) are looked
 * up by name and reused; only missing ones are created. Weights are already in grams and
 * damage is the structured `damageLines` shape, so nothing is converted.
 *
 * Create-only: the caller must reject a payload whose token already exists (409).
 */
class CharacterDeserializer
{
    /**
     * Entries resolved during the current import, keyed by type then name. Entities created
     * here are not flushed yet, so repository lookups cannot see them: without this map, a
     * name appearing twice in the payload (e.g. a talent in kind bonuses and in talents)
     * would be created twice. Reset at the start of each deserialize() call.
     *
     * @var array<string, array<string, object>>
     */
    private array $resolved = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly KindRepository $kindRepository,
        private readonly CharacterClassRepository $characterClassRepository,
        private readonly TalentRepository $talentRepository,
        private readonly SkillRepository $skillRepository,
        private readonly SpellRepository $spellRepository,
        private readonly ItemRepository $itemRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $data an export payload (see CharacterSerializer::serialize)
     */
    public function deserialize(array $data, Game $game): Character
    {
        $this->resolved = [];

        // Resolve owned talents first: they carry the full level list, whereas kind bonuses
        // and primary/secondary talents only carry a name. Whichever reference comes first
        // creates the talent, so the one with levels must win.
        foreach ($this->rows($data, 'talents') as $talentData) {
            $this->resolveTalent((string) ($talentData['name'] ?? ''), $this->talentLevelRows($talentData));
        }

        $character = new Character()
            ->setToken(is_string($data['token'] ?? null) ? $data['token'] : bin2hex(random_bytes(16)))
            ->setGame($game)
            ->setName((string) ($data['name'] ?? ''))
            ->setLastName(isset($data['lastName']) ? (string) $data['lastName'] : null)
            ->setLevel($this->int($data, 'level'))
            ->setCurrentHealthPoints($this->stat($data, 'health', 'current'))
            ->setMaxHealthPoints($this->stat($data, 'health', 'max'))
            ->setCurrentManaPoints($this->stat($data, 'mana', 'current'))
            ->setMaxManaPoints($this->stat($data, 'mana', 'max'))
            ->setCurrentActionPoints($this->stat($data, 'actionPoints', 'current'))
            ->setMaxActionPoints($this->stat($data, 'actionPoints', 'max'))
            ->setCurrentExhaustPoints($this->stat($data, 'exhaustPoints', 'current'))
            ->setMaxExhaustPoints($this->stat($data, 'exhaustPoints', 'max'))
            ->setMaxLoadPoints($this->stat($data, 'loadPoints', 'max'))
            ->setCurrentMentalPoints($this->stat($data, 'mentalPoints', 'current'))
            ->setMaxMentalPoints($this->stat($data, 'mentalPoints', 'max'))
        ;

        // loadPoints.current is computed by LoadCalculator — never stored, so it is ignored.
        // avatarUrl is ignored: the referenced file does not exist on the target.

        $character->setKind($this->resolveKind(is_array($data['kind'] ?? null) ? $data['kind'] : []));

        if (is_array($data['characterClass'] ?? null)) {
            $character->setCharacterClass($this->resolveCharacterClass((string) ($data['characterClass']['name'] ?? '')));
        }

        $this->em->persist($character);

        foreach ($this->rows($data, 'weapons') as $weaponData) {
            $weapon = $this->buildEquipment(new Weapon(), $weaponData, $game);
            $character->addArmament($weapon);
            $this->em->persist($weapon);
        }

        foreach ($this->rows($data, 'armors') as $armorData) {
            $armor = $this->buildEquipment(new Armor(), $armorData, $game);
            $character->addArmament($armor);
            $this->em->persist($armor);
        }

        foreach ($this->rows($data, 'items') as $itemData) {
            $beingItem = new BeingItem()
                ->setItem($this->resolveItem($itemData))
                ->setQuantity($this->int($itemData, 'quantity', 1));
            $character->addItem($beingItem);
            $this->em->persist($beingItem);
        }

        foreach ($this->rows($data, 'spells') as $spellData) {
            $character->addSpell($this->resolveSpell($spellData));
        }

        foreach ($this->rows($data, 'skills') as $skillData) {
            $character->addSkill($this->resolveSkill($skillData));
        }

        foreach ($this->rows($data, 'talents') as $talentData) {
            $talent = $this->resolveTalent((string) ($talentData['name'] ?? ''));
            $beingTalent = new BeingTalent()
                ->setBeing($character)
                ->setTalent($talent)
                ->setValue($this->int($talentData, 'value'));
            $character->addTalent($beingTalent);
            $this->em->persist($beingTalent);
        }

        foreach ($this->rows($data, 'primaryTalents') as $talentData) {
            $character->addPrimaryTalent($this->resolveTalent((string) ($talentData['name'] ?? '')));
        }

        foreach ($this->rows($data, 'secondaryTalents') as $talentData) {
            $character->addSecondaryTalent($this->resolveTalent((string) ($talentData['name'] ?? '')));
        }

        return $character;
    }

    /**
     * @param array<string, mixed> $kindData
     */
    private function resolveKind(array $kindData): Kind
    {
        $name = (string) ($kindData['name'] ?? '');
        $known = $this->resolved[Kind::class][$name] ?? $this->kindRepository->findOneByName($name);
        if ($known instanceof Kind) {
            return $this->resolved[Kind::class][$name] = $known;
        }

        $kind = new Kind()
            ->setName($name)
            ->setIsReady(true)
            ->setIsPrivate(false);

        foreach ($this->rows($kindData, 'bonuses') as $bonusData) {
            if ('talent' !== ($bonusData['type'] ?? null) || !is_array($bonusData['talent'] ?? null)) {
                continue;
            }

            $bonus = new KindTalentBonus()
                ->setTalent($this->resolveTalent((string) ($bonusData['talent']['name'] ?? '')))
                ->setValue($this->int($bonusData, 'value'));
            $kind->addBonus($bonus);
        }

        $this->em->persist($kind);
        $this->resolved[Kind::class][$name] = $kind;

        return $kind;
    }

    private function resolveCharacterClass(string $name): CharacterClass
    {
        $known = $this->resolved[CharacterClass::class][$name] ?? $this->characterClassRepository->findOneBy(['name' => $name]);
        if ($known instanceof CharacterClass) {
            return $this->resolved[CharacterClass::class][$name] = $known;
        }

        $characterClass = new CharacterClass()
            ->setName($name)
            ->setIsReady(true)
            ->setIsPrivate(false);
        $this->em->persist($characterClass);
        $this->resolved[CharacterClass::class][$name] = $characterClass;

        return $characterClass;
    }

    /**
     * @param array<int, array<string, mixed>> $talentLevels
     */
    private function resolveTalent(string $name, array $talentLevels = []): Talent
    {
        $known = $this->resolved[Talent::class][$name] ?? $this->talentRepository->findOneByName($name);
        if ($known instanceof Talent) {
            return $this->resolved[Talent::class][$name] = $known;
        }

        $talent = new Talent()
            ->setName($name)
            ->setDescription('')
            ->setIsReady(true)
            ->setIsPrivate(false);

        foreach ($talentLevels as $levelData) {
            $tier = TierEnum::tryFrom((string) ($levelData['tier'] ?? ''));
            if (!$tier) {
                continue;
            }

            $talent->addTalentLevel(
                new TalentLevel()
                    ->setTier($tier)
                    ->setRequiredPoints($this->int($levelData, 'requiredPoints'))
                    ->setDescription((string) ($levelData['description'] ?? '')),
            );
        }

        $this->em->persist($talent);
        $this->resolved[Talent::class][$name] = $talent;

        return $talent;
    }

    /**
     * @param array<string, mixed> $skillData
     */
    private function resolveSkill(array $skillData): Skill
    {
        $name = (string) ($skillData['name'] ?? '');
        $known = $this->resolved[Skill::class][$name] ?? $this->skillRepository->findOneByName($name);
        if ($known instanceof Skill) {
            return $this->resolved[Skill::class][$name] = $known;
        }

        $skill = new Skill()
            ->setName($name)
            ->setDescription((string) ($skillData['description'] ?? ''))
            ->setExhaustPointCost($this->int($skillData, 'exhaustPointCost'))
            ->setActionPointCost($this->int($skillData, 'actionPointCost'))
            ->setDamageLines($this->mapDamageLines($this->rows($skillData, 'damageLines')))
            ->setIsReady(true)
            ->setIsPrivate(false);
        $this->em->persist($skill);
        $this->resolved[Skill::class][$name] = $skill;

        return $skill;
    }

    /**
     * @param array<string, mixed> $spellData
     */
    private function resolveSpell(array $spellData): Spell
    {
        $name = (string) ($spellData['name'] ?? '');
        $known = $this->resolved[Spell::class][$name] ?? $this->spellRepository->findOneByName($name);
        if ($known instanceof Spell) {
            return $this->resolved[Spell::class][$name] = $known;
        }

        $spell = new Spell()
            ->setName($name)
            ->setDescription((string) ($spellData['description'] ?? ''))
            ->setSchool(isset($spellData['school']) ? (string) $spellData['school'] : null)
            ->setManaCost($this->int($spellData, 'manaCost'))
            ->setActionPointCost($this->int($spellData, 'actionPointCost'))
            ->setDamageLines($this->mapDamageLines($this->rows($spellData, 'damageLines')))
            ->setRange($this->int($spellData, 'range'))
            ->setImpactZone($this->int($spellData, 'impactZone'))
            ->setDuration($this->int($spellData, 'duration'))
            ->setType((string) ($spellData['type'] ?? Spell::TYPE_ACTIVE))
            ->setIsReady(true)
            ->setIsPrivate(false);
        $this->em->persist($spell);
        $this->resolved[Spell::class][$name] = $spell;

        return $spell;
    }

    /**
     * @param array<string, mixed> $itemData
     */
    private function resolveItem(array $itemData): Item
    {
        $name = (string) ($itemData['name'] ?? '');
        $known = $this->resolved[Item::class][$name] ?? $this->itemRepository->findOneByName($name);
        if ($known instanceof Item) {
            return $this->resolved[Item::class][$name] = $known;
        }

        $item = new Item()
            ->setName($name)
            ->setDescription((string) ($itemData['description'] ?? ''))
            ->setValue($this->int($itemData, 'value'))
            ->setWeight($this->int($itemData, 'weight'))
            ->setIsReady(true)
            ->setIsPrivate(false);
        $this->em->persist($item);
        $this->resolved[Item::class][$name] = $item;

        return $item;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildEquipment(Weapon|Armor $equipment, array $data, Game $game): Weapon|Armor
    {
        $equipment
            ->setName((string) ($data['name'] ?? ''))
            ->setValue($this->int($data, 'value'))
            ->setWeight($this->int($data, 'weight'))
            ->setCurrentDurabilityPoints($this->int($data, 'currentDurabilityPoints'))
            ->setMaxDurabilityPoints($this->int($data, 'maxDurabilityPoints'))
            ->setDescription((string) ($data['description'] ?? ''))
            ->setIsEquipped((bool) ($data['isEquipped'] ?? false))
            ->setDamageLines($this->mapDamageLines($this->rows($data, 'damageLines')))
            ->setGame($game);

        foreach ($this->rows($data, 'skills') as $skillData) {
            $equipment->addSkill($this->resolveSkill($skillData));
        }

        return $equipment;
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     *
     * @return DamageLine[]
     */
    private function mapDamageLines(array $lines): array
    {
        return array_map(fn (array $line) => DamageLine::fromArray($line), $lines);
    }

    /**
     * Talent levels to recreate a missing talent with: the full list (`allTalentLevels`) when
     * present, otherwise the unlocked levels only (`talentLevels`, exports predating the full list).
     *
     * @param array<string, mixed> $talentData
     *
     * @return array<int, array<string, mixed>>
     */
    private function talentLevelRows(array $talentData): array
    {
        return $this->rows($talentData, 'allTalentLevels') ?: $this->rows($talentData, 'talentLevels');
    }

    /**
     * Reads a list of objects at $data[$key], keeping only the array (object) rows.
     *
     * @param array<string, mixed> $data
     *
     * @return array<int, array<string, mixed>>
     */
    private function rows(array $data, string $key): array
    {
        $value = $data[$key] ?? null;
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_array'));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function int(array $data, string $key, int $default = 0): int
    {
        $value = $data[$key] ?? null;

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function stat(array $data, string $group, string $key): int
    {
        $stat = $data[$group] ?? null;

        return is_array($stat) ? $this->int($stat, $key) : 0;
    }
}
