<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BeingTalentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'being_talent')]
#[ORM\Entity(repositoryClass: BeingTalentRepository::class)]
class BeingTalent
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Being::class, inversedBy: 'talents')]
    private Being $being;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Talent::class)]
    private Talent $talent;

    #[ORM\Column(type: 'integer')]
    private int $value;

    public function getBeing(): Being
    {
        return $this->being;
    }

    public function setBeing(Being $being): BeingTalent
    {
        $this->being = $being;

        return $this;
    }

    public function getTalent(): Talent
    {
        return $this->talent;
    }

    public function setTalent(Talent $talent): BeingTalent
    {
        $this->talent = $talent;

        return $this;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): BeingTalent
    {
        $this->value = $value;

        return $this;
    }

    public function getName(): string
    {
        return $this->talent->getName();
    }
}
