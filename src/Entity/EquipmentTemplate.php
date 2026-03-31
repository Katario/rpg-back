<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EquipmentTemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquipmentTemplateRepository::class)]
#[ORM\HasLifecycleCallbacks]
class EquipmentTemplate extends Encyclopedia
{
    use HasDateTimeTrait;
    use HasNoteTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string')]
    private string $name;

    // @TODO: Type it with an enum
    #[ORM\Column(type: 'string')]
    private string $category;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $value;

    #[ORM\Column(type: 'integer')]
    private int $minDurability;

    #[ORM\Column(type: 'integer')]
    private int $maxDurability;

    #[ORM\Column(type: 'integer')]
    private int $weight;

    #[ORM\Column(type: 'text')]
    private string $description = '';

    // @TODO: update these two relations to point to a custom ManyToMany table that allows to add a percent change to get them.
    #[ORM\JoinTable(name: 'equipment_templates_skills')]
    #[ORM\JoinColumn(name: 'equipment_template_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'skill_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: Skill::class)]
    private Collection|array $skills;

    #[ORM\JoinTable(name: 'equipment_templates_spells')]
    #[ORM\JoinColumn(name: 'equipment_template_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'spell_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: Spell::class)]
    private Collection|array $spells;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
        $this->spells = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): EquipmentTemplate
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): EquipmentTemplate
    {
        $this->category = $category;

        return $this;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(?int $value): EquipmentTemplate
    {
        $this->value = $value;

        return $this;
    }

    public function getMinDurability(): int
    {
        return $this->minDurability;
    }

    public function setMinDurability(int $minDurability): EquipmentTemplate
    {
        $this->minDurability = $minDurability;

        return $this;
    }

    public function getMaxDurability(): int
    {
        return $this->maxDurability;
    }

    public function setMaxDurability(int $maxDurability): EquipmentTemplate
    {
        $this->maxDurability = $maxDurability;

        return $this;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): EquipmentTemplate
    {
        $this->weight = $weight;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): EquipmentTemplate
    {
        $this->description = $description;

        return $this;
    }

    public function getSkills(): Collection|array
    {
        return $this->skills;
    }

    /** @param Skill[] $skills */
    public function setSkills(Collection|array $skills): EquipmentTemplate
    {
        $this->skills = $skills;

        return $this;
    }

    public function addSkill(Skill $skill): EquipmentTemplate
    {
        if (!$this->getSkills()->contains($skill)) {
            $this->skills->add($skill);
        }

        return $this;
    }

    public function removeSkill(Skill $skill): EquipmentTemplate
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

    /** @param Spell[] $spells */
    public function setSpells(Collection|array $spells): EquipmentTemplate
    {
        $this->spells = $spells;

        return $this;
    }

    public function addSpell(Spell $spell): EquipmentTemplate
    {
        if (!$this->getSpells()->contains($spell)) {
            $this->spells->add($spell);
        }

        return $this;
    }

    public function removeSpell(Spell $spell): EquipmentTemplate
    {
        if ($this->getSpells()->contains($spell)) {
            $this->spells->removeElement($spell);
        }

        return $this;
    }
}
