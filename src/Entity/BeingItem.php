<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BeingItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'being_item')]
#[ORM\Entity(repositoryClass: BeingItemRepository::class)]
class BeingItem
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Being::class, inversedBy: 'items')]
    private Being $being;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Item::class)]
    private Item $item;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $quantity = 1;

    public function getBeing(): Being
    {
        return $this->being;
    }

    public function setBeing(Being $being): BeingItem
    {
        $this->being = $being;

        return $this;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function setItem(Item $item): BeingItem
    {
        $this->item = $item;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): BeingItem
    {
        $this->quantity = $quantity;

        return $this;
    }
}
