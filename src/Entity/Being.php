<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'being')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    'character' => Character::class,
    'monster' => Monster::class,
    'non_playable_character' => NonPlayableCharacter::class,
])]
#[ORM\HasLifecycleCallbacks]
abstract class Being
{
    use HasDateTimeTrait;
    use HasNoteTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(type: 'string')]
    protected string $name;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $lastName = null;

    #[ORM\Column(type: 'integer')]
    protected int $level;

    #[ORM\Column(type: 'integer')]
    protected int $currentHealthPoints;

    #[ORM\Column(type: 'integer')]
    protected int $maxHealthPoints;

    #[ORM\Column(type: 'integer')]
    protected int $currentManaPoints;

    #[ORM\Column(type: 'integer')]
    protected int $maxManaPoints;

    #[ORM\Column(type: 'integer')]
    protected int $currentActionPoints;

    #[ORM\Column(type: 'integer')]
    protected int $maxActionPoints;

    #[ORM\Column(type: 'integer')]
    protected int $currentExhaustPoints;

    #[ORM\Column(type: 'integer')]
    protected int $maxExhaustPoints;

    #[ORM\Column(type: 'integer')]
    protected int $maxLoadPoints;

    #[ORM\Column(type: 'integer')]
    protected int $currentMentalPoints;

    #[ORM\Column(type: 'integer')]
    protected int $maxMentalPoints;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(name: 'game_id', referencedColumnName: 'id')]
    protected Game $game;

    #[ORM\JoinTable(name: 'being_spells')]
    #[ORM\JoinColumn(name: 'being_id', referencedColumnName: 'id', onDelete: 'cascade')]
    #[ORM\InverseJoinColumn(name: 'spell_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: Spell::class)]
    protected Collection $spells;

    #[ORM\JoinTable(name: 'being_skills')]
    #[ORM\JoinColumn(name: 'being_id', referencedColumnName: 'id', onDelete: 'cascade')]
    #[ORM\InverseJoinColumn(name: 'skill_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: Skill::class)]
    protected Collection $skills;

    #[ORM\OneToMany(targetEntity: BeingItem::class, mappedBy: 'being', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $items;

    #[ORM\OneToMany(targetEntity: BeingTalent::class, mappedBy: 'being', cascade: ['remove'], orphanRemoval: true)]
    protected Collection $talents;

    #[ORM\JoinTable(name: 'being_primary_talents')]
    #[ORM\JoinColumn(name: 'being_id', referencedColumnName: 'id', onDelete: 'cascade')]
    #[ORM\InverseJoinColumn(name: 'talent_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: Talent::class)]
    protected Collection $primaryTalents;

    #[ORM\JoinTable(name: 'being_secondary_talents')]
    #[ORM\JoinColumn(name: 'being_id', referencedColumnName: 'id', onDelete: 'cascade')]
    #[ORM\InverseJoinColumn(name: 'talent_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: Talent::class)]
    protected Collection $secondaryTalents;

    #[ORM\OneToMany(targetEntity: Equipment::class, mappedBy: 'being', cascade: ['remove'], orphanRemoval: true)]
    protected Collection $equipments;

    public function __construct()
    {
        $this->spells = new ArrayCollection();
        $this->skills = new ArrayCollection();
        $this->items = new ArrayCollection();
        $this->talents = new ArrayCollection();
        $this->equipments = new ArrayCollection();
        $this->primaryTalents = new ArrayCollection();
        $this->secondaryTalents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->name.($this->lastName ? ' '.$this->lastName : '');
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getCurrentHealthPoints(): int
    {
        return $this->currentHealthPoints;
    }

    public function setCurrentHealthPoints(int $currentHealthPoints): static
    {
        $this->currentHealthPoints = $currentHealthPoints;

        return $this;
    }

    public function getMaxHealthPoints(): int
    {
        return $this->maxHealthPoints;
    }

    public function setMaxHealthPoints(int $maxHealthPoints): static
    {
        $this->maxHealthPoints = $maxHealthPoints;

        return $this;
    }

    public function getCurrentManaPoints(): int
    {
        return $this->currentManaPoints;
    }

    public function setCurrentManaPoints(int $currentManaPoints): static
    {
        $this->currentManaPoints = $currentManaPoints;

        return $this;
    }

    public function getMaxManaPoints(): int
    {
        return $this->maxManaPoints;
    }

    public function setMaxManaPoints(int $maxManaPoints): static
    {
        $this->maxManaPoints = $maxManaPoints;

        return $this;
    }

    public function getCurrentActionPoints(): int
    {
        return $this->currentActionPoints;
    }

    public function setCurrentActionPoints(int $currentActionPoints): static
    {
        $this->currentActionPoints = $currentActionPoints;

        return $this;
    }

    public function getMaxActionPoints(): int
    {
        return $this->maxActionPoints;
    }

    public function setMaxActionPoints(int $maxActionPoints): static
    {
        $this->maxActionPoints = $maxActionPoints;

        return $this;
    }

    public function getCurrentExhaustPoints(): int
    {
        return $this->currentExhaustPoints;
    }

    public function setCurrentExhaustPoints(int $currentExhaustPoints): static
    {
        $this->currentExhaustPoints = $currentExhaustPoints;

        return $this;
    }

    public function getMaxExhaustPoints(): int
    {
        return $this->maxExhaustPoints;
    }

    public function setMaxExhaustPoints(int $maxExhaustPoints): static
    {
        $this->maxExhaustPoints = $maxExhaustPoints;

        return $this;
    }

    public function getMaxLoadPoints(): int
    {
        return $this->maxLoadPoints;
    }

    public function setMaxLoadPoints(int $maxLoadPoints): static
    {
        $this->maxLoadPoints = $maxLoadPoints;

        return $this;
    }

    public function getCurrentMentalPoints(): int
    {
        return $this->currentMentalPoints;
    }

    public function setCurrentMentalPoints(int $currentMentalPoints): static
    {
        $this->currentMentalPoints = $currentMentalPoints;

        return $this;
    }

    public function getMaxMentalPoints(): int
    {
        return $this->maxMentalPoints;
    }

    public function setMaxMentalPoints(int $maxMentalPoints): static
    {
        $this->maxMentalPoints = $maxMentalPoints;

        return $this;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function setGame(Game $game): static
    {
        $this->game = $game;

        return $this;
    }

    public function getEquipments(): Collection
    {
        return $this->equipments;
    }

    public function addArmament(Equipment $armament): static
    {
        if (!$this->equipments->contains($armament)) {
            $armament->setIsOwned(true);
            $armament->setBeing($this);
            $this->equipments->add($armament);
        }

        return $this;
    }

    public function removeArmament(Equipment $armament): static
    {
        if ($this->equipments->contains($armament)) {
            $armament->setBeing(null);
            $armament->setIsOwned(false);
            $this->equipments->removeElement($armament);
        }

        return $this;
    }

    public function getSpells(): Collection
    {
        return $this->spells;
    }

    public function setSpells(Collection $spells): static
    {
        $this->spells = $spells;

        return $this;
    }

    public function addSpell(Spell $spell): static
    {
        if (!$this->spells->contains($spell)) {
            $this->spells->add($spell);
        }

        return $this;
    }

    public function removeSpell(Spell $spell): static
    {
        if ($this->spells->contains($spell)) {
            $this->spells->removeElement($spell);
        }

        return $this;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(BeingItem $item): static
    {
        if (!$this->items->contains($item)) {
            $item->setBeing($this);
            $this->items->add($item);
        }

        return $this;
    }

    public function removeItem(BeingItem $item): static
    {
        $this->items->removeElement($item);

        return $this;
    }

    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function setSkills(Collection $skills): static
    {
        $this->skills = $skills;

        return $this;
    }

    public function addSkill(Skill $skill): static
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
        }

        return $this;
    }

    public function removeSkill(Skill $skill): static
    {
        if ($this->skills->contains($skill)) {
            $this->skills->removeElement($skill);
        }

        return $this;
    }

    public function getTalents(): Collection
    {
        return $this->talents;
    }

    public function setTalents(Collection $talents): static
    {
        $this->talents = $talents;

        return $this;
    }

    public function addTalent(BeingTalent $talent): static
    {
        if (!$this->talents->contains($talent)) {
            $this->talents->add($talent);
        }

        return $this;
    }

    public function removeTalent(BeingTalent $talent): static
    {
        if ($this->talents->contains($talent)) {
            $this->talents->removeElement($talent);
        }

        return $this;
    }

    public function getPrimaryTalents(): Collection
    {
        return $this->primaryTalents;
    }

    public function addPrimaryTalent(Talent $talent): static
    {
        if (!$this->primaryTalents->contains($talent)) {
            $this->primaryTalents->add($talent);
        }

        return $this;
    }

    public function getSecondaryTalents(): Collection
    {
        return $this->secondaryTalents;
    }

    public function addSecondaryTalent(Talent $talent): static
    {
        if (!$this->secondaryTalents->contains($talent)) {
            $this->secondaryTalents->add($talent);
        }

        return $this;
    }
}
