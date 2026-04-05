<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CharacterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CharacterRepository::class)]
class Character extends Being
{
    #[Assert\NotBlank]
    #[ORM\Column(type: 'string')]
    private string $token;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $avatarUrl = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'characters')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    private ?User $user;

    #[ORM\JoinTable(name: 'playable_characters_kind')]
    #[ORM\JoinColumn(name: 'being_id', referencedColumnName: 'id', unique: true, onDelete: 'cascade')]
    #[ORM\InverseJoinColumn(name: 'kind_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: 'Kind')]
    private Collection $kind;

    #[ORM\JoinTable(name: 'playable_characters_character_class')]
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

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): Character
    {
        $this->token = $token;

        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): Character
    {
        $this->avatarUrl = $avatarUrl;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): Character
    {
        $this->user = $user;

        return $this;
    }

    public function getKind(): ?Kind
    {
        if (0 === $this->kind->count()) {
            return null;
        }

        return $this->kind->first();
    }

    public function setKind(Kind $kind): Character
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

    public function setCharacterClass(CharacterClass $characterClass): Character
    {
        if (!$this->characterClass->contains($characterClass)) {
            $this->characterClass->clear();
            $this->characterClass->add($characterClass);
        }

        return $this;
    }

    private const array LEVELUP_ALLOWED_STATS = [
        'maxHealthPoints'  => ['getter' => 'getMaxHealthPoints',  'setter' => 'setMaxHealthPoints',  'unit' => 1],
        'maxManaPoints'    => ['getter' => 'getMaxManaPoints',    'setter' => 'setMaxManaPoints',    'unit' => 1],
        'maxActionPoints'  => ['getter' => 'getMaxActionPoints',  'setter' => 'setMaxActionPoints',  'unit' => 10],
        'maxExhaustPoints' => ['getter' => 'getMaxExhaustPoints', 'setter' => 'setMaxExhaustPoints', 'unit' => 10],
        'maxMentalPoints'  => ['getter' => 'getMaxMentalPoints',  'setter' => 'setMaxMentalPoints',  'unit' => 10],
        'maxLoadPoints'    => ['getter' => 'getMaxLoadPoints',    'setter' => 'setMaxLoadPoints',    'unit' => 10],
    ];

    /**
     * @param array<string, int> $statIncrements
     * @param string[]           $talentNames
     */
    public function levelUp(array $statIncrements, array $talentNames): void
    {
        $invalidKeys = array_diff(array_keys($statIncrements), array_keys(self::LEVELUP_ALLOWED_STATS));
        if (!empty($invalidKeys)) {
            throw new \InvalidArgumentException(sprintf('Invalid stats: %s', implode(', ', $invalidKeys)));
        }

        $pointsSpent = 0;
        foreach ($statIncrements as $stat => $increment) {
            $unit = self::LEVELUP_ALLOWED_STATS[$stat]['unit'];
            if ($increment % $unit !== 0) {
                throw new \InvalidArgumentException(sprintf('Increment for "%s" must be a multiple of %d, got %d', $stat, $unit, $increment));
            }
            $pointsSpent += $increment / $unit;
        }

        if ($pointsSpent !== 2) {
            throw new \InvalidArgumentException(sprintf('Stats must total 2 points, got %d', $pointsSpent));
        }

        if (count($talentNames) !== 5) {
            throw new \InvalidArgumentException(sprintf('Exactly 5 talents must be selected, got %d', count($talentNames)));
        }

        $primaryTalentNames = $this->primaryTalents->map(fn ($t) => $t->getName())->toArray();
        $secondaryTalentNames = $this->secondaryTalents->map(fn ($t) => $t->getName())->toArray();

        foreach ($talentNames as $talentName) {
            $beingTalent = $this->talents->filter(fn ($bt) => $bt->getTalent()->getName() === $talentName)->first();

            if (!$beingTalent) {
                throw new \InvalidArgumentException(sprintf('Talent "%s" not found for this character', $talentName));
            }

            $increment = match (true) {
                in_array($talentName, $primaryTalentNames, true)   => 3,
                in_array($talentName, $secondaryTalentNames, true) => 2,
                default                                            => 1,
            };

            $beingTalent->setValue($beingTalent->getValue() + $increment);
        }

        foreach ($statIncrements as $stat => $increment) {
            ['getter' => $getter, 'setter' => $setter] = self::LEVELUP_ALLOWED_STATS[$stat];
            $this->$setter($this->$getter() + $increment);
        }

        ++$this->level;
    }
}
