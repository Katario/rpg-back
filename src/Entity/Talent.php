<?php

namespace App\Entity;

use App\Repository\TalentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TalentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Talent extends Encyclopedia
{
    use HasDateTimeTrait;
    use HasNoteTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    #[ORM\Column(type: 'string')]
    private string $name;
    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\OneToMany(targetEntity: TalentLevel::class, mappedBy: 'talent', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $talentLevels;

    public function __construct()
    {
        $this->talentLevels = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Talent
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Talent
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Talent
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, TalentLevel>
     */
    public function getTalentLevels(): Collection
    {
        return $this->talentLevels;
    }

    public function addTalentLevel(TalentLevel $talentLevel): self
    {
        if (!$this->talentLevels->contains($talentLevel)) {
            $this->talentLevels[] = $talentLevel;
            $talentLevel->setTalent($this);
        }

        return $this;
    }

    public function removeTalentLevel(TalentLevel $talentLevel): self
    {
        $this->talentLevels->removeElement($talentLevel);

        return $this;
    }
}
