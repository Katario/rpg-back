<?php

declare(strict_types=1);

namespace App\Calculator;

use App\Entity\Being;
use App\Entity\BeingItem;
use App\Entity\Equipment;

class LoadCalculator
{
    public function computeCurrentLoadPoints(Being $being): int
    {
        $total = 0;

        foreach ($being->getEquipments() as $equipment) {
            /** @var Equipment $equipment */
            $total += $equipment->getWeight();
        }

        foreach ($being->getItems() as $beingItem) {
            /** @var BeingItem $beingItem */
            $total += $beingItem->getItem()->getWeight() * $beingItem->getQuantity();
        }

        return $total;
    }
}
