<?php

namespace App\Entity;

use App\Repository\TalentLevelRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TalentLevelRepository::class)]
#[ORM\HasLifecycleCallbacks]
class TalentLevel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $level;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\ManyToOne(inversedBy: 'talentLevels')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Talent $talent = null;
    // @TODO: a Talent may be linked to a Skill, passive or active. The relation is a TalentLevel may be link to ONE skill, a skill may be linked to MULTIPLE TalentLevel.
    // @TODO: a TalentLevel may also be linked to Bonuses. The relation is a TalentLevel may be link to MULTIPLE bonuses, a bonus may be linked to MULTIPLE TalentLevel.

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getTalent(): ?Talent
    {
        return $this->talent;
    }

    public function setTalent(?Talent $talent): self
    {
        $this->talent = $talent;

        return $this;
    }
}
