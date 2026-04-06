<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WeaponRepository;
use App\ValueObject\DamageLine;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WeaponRepository::class)]
class Weapon extends Equipment
{
    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    private array $damageLines = [];

    public function getCategory(): string
    {
        return 'weapon';
    }

    /** @return DamageLine[] */
    public function getDamageLines(): array
    {
        return array_map(fn (array $line) => DamageLine::fromArray($line), $this->damageLines);
    }

    /** @param DamageLine[] $damageLines */
    public function setDamageLines(array $damageLines): static
    {
        $this->damageLines = array_map(fn (DamageLine $line) => $line->toArray(), $damageLines);

        return $this;
    }

    public function addDamageLine(DamageLine $line): static
    {
        $this->damageLines[] = $line->toArray();

        return $this;
    }

    public function removeDamageLine(int $faces): static
    {
        $this->damageLines = array_values(
            array_filter($this->damageLines, fn (array $line) => $line['diceFaces'] !== $faces)
        );

        return $this;
    }
}
