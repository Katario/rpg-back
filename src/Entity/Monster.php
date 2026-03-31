<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MonsterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MonsterRepository::class)]
class Monster extends Being
{
    #[ORM\Column(type: 'boolean')]
    private bool $isBoss = false;

    #[ORM\JoinTable(name: 'monsters_specie')]
    #[ORM\JoinColumn(name: 'being_id', referencedColumnName: 'id', unique: true, onDelete: 'cascade')]
    #[ORM\InverseJoinColumn(name: 'specie_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: 'Specie')]
    private Collection $specie;

    public function __construct()
    {
        parent::__construct();
        $this->specie = new ArrayCollection();
    }

    public function isBoss(): bool
    {
        return $this->isBoss;
    }

    public function setIsBoss(bool $isBoss): Monster
    {
        $this->isBoss = $isBoss;

        return $this;
    }

    public function getSpecie(): ?Specie
    {
        if (0 === $this->specie->count()) {
            return null;
        }

        return $this->specie->first();
    }

    public function setSpecie(Specie $specie): Monster
    {
        if (!$this->specie->contains($specie)) {
            $this->specie->clear();
            $this->specie->add($specie);
        }

        return $this;
    }
}
