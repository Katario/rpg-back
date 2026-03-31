<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SpellRepository;
use App\ValueObject\Damage;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SpellRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Spell extends Encyclopedia
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
    #[ORM\Column(type: 'integer')]
    private int $manaCost;
    #[ORM\Column(type: 'integer')]
    private int $actionPointCost;
    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    private array $damageDice = [];

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $damageBonus = 0;

    public const string TYPE_ACTIVE = 'active';
    public const string TYPE_PASSIVE = 'passive';

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $school = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $range = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $impactZone = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $duration = 0;

    #[ORM\Column(type: 'string', options: ['default' => self::TYPE_ACTIVE])]
    private string $type = self::TYPE_ACTIVE;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Spell
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Spell
    {
        $this->description = $description;

        return $this;
    }

    public function getManaCost(): int
    {
        return $this->manaCost;
    }

    public function setManaCost(int $manaCost): Spell
    {
        $this->manaCost = $manaCost;

        return $this;
    }

    public function getActionPointCost(): int
    {
        return $this->actionPointCost;
    }

    public function setActionPointCost(int $actionPointCost): Spell
    {
        $this->actionPointCost = $actionPointCost;

        return $this;
    }

    public function getDamage(): Damage
    {
        return new Damage($this->damageDice, $this->damageBonus);
    }

    public function setDamage(Damage $damage): Spell
    {
        $this->damageDice = $damage->getDice();
        $this->damageBonus = $damage->getBonus();

        return $this;
    }

    public function getSchool(): ?string
    {
        return $this->school;
    }

    public function setSchool(?string $school): Spell
    {
        $this->school = $school;

        return $this;
    }

    public function getRange(): int
    {
        return $this->range;
    }

    public function setRange(int $range): Spell
    {
        $this->range = $range;

        return $this;
    }

    public function getImpactZone(): int
    {
        return $this->impactZone;
    }

    public function setImpactZone(int $impactZone): Spell
    {
        $this->impactZone = $impactZone;

        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): Spell
    {
        $this->duration = $duration;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): Spell
    {
        $this->type = $type;

        return $this;
    }
}
