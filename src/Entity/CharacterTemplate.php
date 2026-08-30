<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CharacterTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CharacterTemplateRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CharacterTemplate extends Encyclopedia
{
    use HasDateTimeTrait;
    use HasNoteTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(type: 'string')]
    private string $name;
    #[ORM\Column(type: 'integer')]
    private int $minHealthPoints;
    #[ORM\Column(type: 'integer')]
    private int $maxHealthPoints;
    #[ORM\Column(type: 'integer')]
    private int $minManaPoints;
    #[ORM\Column(type: 'integer')]
    private int $maxManaPoints;
    #[ORM\Column(type: 'integer')]
    private int $minActionPoints;
    #[ORM\Column(type: 'integer')]
    private int $maxActionPoints;
    #[ORM\Column(type: 'integer')]
    private int $minExhaustPoints;
    #[ORM\Column(type: 'integer')]
    private int $maxExhaustPoints;
    #[ORM\JoinTable(name: 'character_templates_kind')]
    #[ORM\JoinColumn(name: 'character_template_id', referencedColumnName: 'id', unique: true)]
    #[ORM\InverseJoinColumn(name: 'kind_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: 'Kind')]
    private Collection $kind;
    #[ORM\JoinTable(name: 'character_templates_character_class')]
    #[ORM\JoinColumn(name: 'character_template_id', referencedColumnName: 'id', unique: true)]
    #[ORM\InverseJoinColumn(name: 'character_class_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: 'CharacterClass')]
    private Collection $characterClass;
    #[ORM\JoinTable(name: 'character_templates_spells')]
    #[ORM\JoinColumn(name: 'character_template_id', referencedColumnName: 'id', onDelete: 'cascade')]
    #[ORM\InverseJoinColumn(name: 'spell_id', referencedColumnName: 'id', onDelete: 'cascade')]
    /** @var Collection<int, Spell> */
    #[ORM\ManyToMany(targetEntity: Spell::class)]
    private Collection $spells;
    #[ORM\JoinTable(name: 'character_templates_skills')]
    #[ORM\JoinColumn(name: 'character_template_id', referencedColumnName: 'id', onDelete: 'cascade')]
    #[ORM\InverseJoinColumn(name: 'skill_id', referencedColumnName: 'id', onDelete: 'cascade')]
    /** @var Collection<int, Skill> */
    #[ORM\ManyToMany(targetEntity: Skill::class)]
    private Collection $skills;

    #[ORM\JoinTable(name: 'character_templates_items')]
    #[ORM\JoinColumn(name: 'character_template_id', referencedColumnName: 'id', onDelete: 'cascade')]
    #[ORM\InverseJoinColumn(name: 'item_id', referencedColumnName: 'id', onDelete: 'cascade')]
    /** @var Collection<int, Item> */
    #[ORM\ManyToMany(targetEntity: Item::class)]
    private Collection $items;

    public function __construct()
    {
        $this->kind = new ArrayCollection();
        $this->characterClass = new ArrayCollection();
        $this->spells = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->skills = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): CharacterTemplate
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): CharacterTemplate
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Spell>
     */
    public function getSpells(): Collection
    {
        return $this->spells;
    }

    /** @param Collection<int, Spell>|Spell[] $spells */
    public function setSpells(Collection|array $spells): CharacterTemplate
    {
        $this->spells = $spells instanceof Collection ? $spells : new ArrayCollection($spells);

        return $this;
    }

    public function addSpell(Spell $spell): CharacterTemplate
    {
        if (!$this->getSpells()->contains($spell)) {
            $this->spells->add($spell);
        }

        return $this;
    }

    public function removeSpell(Spell $spell): CharacterTemplate
    {
        if ($this->getSpells()->contains($spell)) {
            $this->spells->removeElement($spell);
        }

        return $this;
    }

    /**
     * @return Collection<int, Item>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /** @param Collection<int, Item>|Item[] $items */
    public function setItems(Collection|array $items): CharacterTemplate
    {
        $this->items = $items instanceof Collection ? $items : new ArrayCollection($items);

        return $this;
    }

    public function addItem(Item $item): CharacterTemplate
    {
        if (!$this->getItems()->contains($item)) {
            $this->items->add($item);
        }

        return $this;
    }

    public function removeItem(Item $item): CharacterTemplate
    {
        if ($this->getItems()->contains($item)) {
            $this->items->removeElement($item);
        }

        return $this;
    }

    /**
     * @return Collection<int, Skill>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    /** @param Collection<int, Skill>|Skill[] $skills */
    public function setSkills(Collection|array $skills): CharacterTemplate
    {
        $this->skills = $skills instanceof Collection ? $skills : new ArrayCollection($skills);

        return $this;
    }

    public function addSkill(Skill $skill): CharacterTemplate
    {
        if (!$this->getSkills()->contains($skill)) {
            $this->skills->add($skill);
        }

        return $this;
    }

    public function removeSkill(Skill $skill): CharacterTemplate
    {
        if ($this->getSkills()->contains($skill)) {
            $this->skills->removeElement($skill);
        }

        return $this;
    }

    public function getMinHealthPoints(): int
    {
        return $this->minHealthPoints;
    }

    public function setMinHealthPoints(int $minHealthPoints): CharacterTemplate
    {
        $this->minHealthPoints = $minHealthPoints;

        return $this;
    }

    public function getMaxHealthPoints(): int
    {
        return $this->maxHealthPoints;
    }

    public function setMaxHealthPoints(int $maxHealthPoints): CharacterTemplate
    {
        $this->maxHealthPoints = $maxHealthPoints;

        return $this;
    }

    public function getMinManaPoints(): int
    {
        return $this->minManaPoints;
    }

    public function setMinManaPoints(int $minManaPoints): CharacterTemplate
    {
        $this->minManaPoints = $minManaPoints;

        return $this;
    }

    public function getMaxManaPoints(): int
    {
        return $this->maxManaPoints;
    }

    public function setMaxManaPoints(int $maxManaPoints): CharacterTemplate
    {
        $this->maxManaPoints = $maxManaPoints;

        return $this;
    }

    public function getMinActionPoints(): int
    {
        return $this->minActionPoints;
    }

    public function setMinActionPoints(int $minActionPoints): CharacterTemplate
    {
        $this->minActionPoints = $minActionPoints;

        return $this;
    }

    public function getMaxActionPoints(): int
    {
        return $this->maxActionPoints;
    }

    public function setMaxActionPoints(int $maxActionPoints): CharacterTemplate
    {
        $this->maxActionPoints = $maxActionPoints;

        return $this;
    }

    public function getMinExhaustPoints(): int
    {
        return $this->minExhaustPoints;
    }

    public function setMinExhaustPoints(int $minExhaustPoints): CharacterTemplate
    {
        $this->minExhaustPoints = $minExhaustPoints;

        return $this;
    }

    public function getMaxExhaustPoints(): int
    {
        return $this->maxExhaustPoints;
    }

    public function setMaxExhaustPoints(int $maxExhaustPoints): CharacterTemplate
    {
        $this->maxExhaustPoints = $maxExhaustPoints;

        return $this;
    }

    public function getKind(): ?Kind
    {
        if (0 === $this->kind->count()) {
            return null;
        }

        return $this->kind->first();
    }

    public function setKind(Kind $kind): CharacterTemplate
    {
        if (!$this->kind->contains($kind)) {
            $this->kind->clear();
            $this->kind->add($kind);
        }

        return $this;
    }

    public function getCharacterClass(): ?CharacterClass
    {
        if (0 === $this->characterClass->count()) {
            return null;
        }

        return $this->characterClass->first();
    }

    public function setCharacterClass(CharacterClass $characterClass): CharacterTemplate
    {
        if (!$this->characterClass->contains($characterClass)) {
            $this->characterClass->clear();
            $this->characterClass->add($characterClass);
        }

        return $this;
    }
}
