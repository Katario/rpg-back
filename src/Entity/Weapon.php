<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WeaponRepository;
use App\ValueObject\Damage;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WeaponRepository::class)]
class Weapon extends Equipment
{
    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    private array $damageDice = [];

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $damageBonus = 0;

    public function getCategory(): string
    {
        return 'weapon';
    }

    public function getDamage(): Damage
    {
        return new Damage($this->damageDice, $this->damageBonus);
    }

    public function setDamage(Damage $damage): static
    {
        $this->damageDice = $damage->getDice();
        $this->damageBonus = $damage->getBonus();

        return $this;
    }
}
