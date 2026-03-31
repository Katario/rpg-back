<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\KindBonusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KindBonusRepository::class)]
#[ORM\Table(name: 'kind_bonus')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    'talent' => KindTalentBonus::class,
])]
abstract class KindBonus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Kind::class, inversedBy: 'bonuses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Kind $kind;

    #[ORM\Column(type: 'integer')]
    private int $value;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKind(): Kind
    {
        return $this->kind;
    }

    public function setKind(Kind $kind): static
    {
        $this->kind = $kind;

        return $this;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): static
    {
        $this->value = $value;

        return $this;
    }
}
