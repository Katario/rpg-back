<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\NonPlayableCharacterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NonPlayableCharacterRepository::class)]
class NonPlayableCharacter extends Being
{
    #[ORM\JoinTable(name: 'non_playable_characters_kind')]
    #[ORM\JoinColumn(name: 'being_id', referencedColumnName: 'id', unique: true, onDelete: 'cascade')]
    #[ORM\InverseJoinColumn(name: 'kind_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: 'Kind')]
    private Collection $kind;

    #[ORM\JoinTable(name: 'non_playable_characters_character_class')]
    #[ORM\JoinColumn(name: 'being_id', referencedColumnName: 'id', unique: true, onDelete: 'cascade')]
    #[ORM\InverseJoinColumn(name: 'character_class_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: 'CharacterClass')]
    private Collection $characterClass;

    public function __construct()
    {
        parent::__construct();
        $this->kind = new ArrayCollection();
        $this->characterClass = new ArrayCollection();
    }

    public function getKind(): ?Kind
    {
        if (0 === $this->kind->count()) {
            return null;
        }

        return $this->kind->first();
    }

    public function setKind(Kind $kind): NonPlayableCharacter
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

    public function setCharacterClass(CharacterClass $characterClass): NonPlayableCharacter
    {
        if (!$this->characterClass->contains($characterClass)) {
            $this->characterClass->clear();
            $this->characterClass->add($characterClass);
        }

        return $this;
    }
}
