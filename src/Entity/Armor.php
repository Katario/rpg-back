<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ArmorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArmorRepository::class)]
class Armor extends Equipment
{
    public function getCategory(): string
    {
        return 'armor';
    }
}
