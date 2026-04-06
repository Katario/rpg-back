<?php

namespace App\Entity;

use App\Repository\SkillRepository;
use App\ValueObject\DamageLine;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Skill extends Encyclopedia
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
    private int $exhaustPointCost;

    #[ORM\Column(type: 'integer')]
    private int $actionPointCost;

    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    private array $damageLines = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Skill
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): Skill
    {
        $this->description = $description;

        return $this;
    }

    public function getExhaustPointCost(): int
    {
        return $this->exhaustPointCost;
    }

    public function setExhaustPointCost(int $exhaustPointCost): Skill
    {
        $this->exhaustPointCost = $exhaustPointCost;

        return $this;
    }

    public function getActionPointCost(): int
    {
        return $this->actionPointCost;
    }

    public function setActionPointCost(int $actionPointCost): Skill
    {
        $this->actionPointCost = $actionPointCost;

        return $this;
    }

    /** @return DamageLine[] */
    public function getDamageLines(): array
    {
        return array_map(fn (array $line) => DamageLine::fromArray($line), $this->damageLines);
    }

    /** @param DamageLine[] $damageLines */
    public function setDamageLines(array $damageLines): Skill
    {
        $this->damageLines = array_map(fn (DamageLine $line) => $line->toArray(), $damageLines);

        return $this;
    }

    public function addDamageLine(DamageLine $line): Skill
    {
        $this->damageLines[] = $line->toArray();

        return $this;
    }

    public function removeDamageLine(int $faces): Skill
    {
        $this->damageLines = array_values(
            array_filter($this->damageLines, fn (array $line) => $line['diceFaces'] !== $faces)
        );

        return $this;
    }
}
