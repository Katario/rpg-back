<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Item;

class ItemFactory
{
    public function createOne(string $name, string $description = '', int $value = 0, int $weight = 0): Item
    {
        return (new Item())
            ->setName($name)
            ->setDescription($description)
            ->setValue($value)
            ->setWeight($weight)
            ->setIsReady(true)
            ->setIsPrivate(false);
    }
}
