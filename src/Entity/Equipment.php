<?php

namespace App\Entity;

use App\Repository\EquipmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquipmentRepository::class)]
#[ORM\Table(name: 'equipment')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'category', type: 'string')]
#[ORM\DiscriminatorMap(['weapon' => Weapon::class, 'armor' => Armor::class])]
#[ORM\HasLifecycleCallbacks]
abstract class Equipment
{
    use HasDateTimeTrait;
    use HasNoteTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string')]
    private string $name;

    #[ORM\Column(type: 'integer', nullable: true)]
    private int $value = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $weight = 0;

    #[ORM\Column(type: 'integer')]
    private int $currentDurabilityPoints;

    #[ORM\Column(type: 'integer')]
    private int $maxDurabilityPoints;

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isOwned = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isEquipped = false;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'equipments')]
    private Game $game;

    #[ORM\ManyToOne(targetEntity: Being::class, inversedBy: 'equipments')]
    #[ORM\JoinColumn(name: 'being_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Being $being = null;

    #[ORM\JoinTable(name: 'equipments_skills')]
    #[ORM\JoinColumn(name: 'equipment_id', referencedColumnName: 'id', onDelete: 'cascade')]
    #[ORM\InverseJoinColumn(name: 'skill_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: Skill::class)]
    private Collection $skills;

    #[ORM\JoinTable(name: 'equipments_spells')]
    #[ORM\JoinColumn(name: 'equipment_id', referencedColumnName: 'id', onDelete: 'cascade')]
    #[ORM\InverseJoinColumn(name: 'spell_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: Spell::class)]
    private Collection $spells;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
        $this->spells = new ArrayCollection();
    }

    abstract public function getCategory(): string;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(?int $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    public function getMaxDurabilityPoints(): int
    {
        return $this->maxDurabilityPoints;
    }

    public function setMaxDurabilityPoints(int $maxDurabilityPoints): static
    {
        $this->maxDurabilityPoints = $maxDurabilityPoints;

        return $this;
    }

    public function getCurrentDurabilityPoints(): int
    {
        return $this->currentDurabilityPoints;
    }

    public function setCurrentDurabilityPoints(int $currentDurabilityPoints): static
    {
        $this->currentDurabilityPoints = $currentDurabilityPoints;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isOwned(): bool
    {
        return $this->isOwned;
    }

    public function setIsOwned(bool $isOwned): static
    {
        $this->isOwned = $isOwned;

        return $this;
    }

    public function isEquipped(): bool
    {
        return $this->isEquipped;
    }

    public function setIsEquipped(bool $isEquipped): static
    {
        $this->isEquipped = $isEquipped;

        return $this;
    }

    public function getSkills(): Collection|array
    {
        return $this->skills;
    }

    public function addSkill(Skill $skill): static
    {
        if (!$this->getSkills()->contains($skill)) {
            $this->skills->add($skill);
        }

        return $this;
    }

    public function removeSkill(Skill $skill): static
    {
        if ($this->getSkills()->contains($skill)) {
            $this->skills->removeElement($skill);
        }

        return $this;
    }

    public function getSpells(): Collection|array
    {
        return $this->spells;
    }

    public function addSpell(Spell $spell): static
    {
        if (!$this->getSpells()->contains($spell)) {
            $this->spells->add($spell);
        }

        return $this;
    }

    public function removeSpell(Spell $spell): static
    {
        if ($this->getSpells()->contains($spell)) {
            $this->spells->removeElement($spell);
        }

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

    public function getBeing(): ?Being
    {
        return $this->being;
    }

    public function setBeing(?Being $being): static
    {
        $this->being = $being;
        if ($this->being) {
            $this->setIsOwned(true);
        }

        return $this;
    }

    public function getOwnerName(): ?string
    {
        return $this->being?->getFullName();
    }
}
