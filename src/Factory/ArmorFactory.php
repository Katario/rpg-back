<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Armor;
use App\Entity\EquipmentTemplate;

class ArmorFactory
{
    public function createOneFromEquipmentTemplate(EquipmentTemplate $equipmentTemplate): Armor
    {
        $armor = new Armor();
        $armor
            ->setName($equipmentTemplate->getName())
            ->setValue($equipmentTemplate->getValue())
            ->setCurrentDurabilityPoints($equipmentTemplate->getMaxDurability())
            ->setMaxDurabilityPoints($equipmentTemplate->getMaxDurability())
            ->setDescription($equipmentTemplate->getDescription())
            ->setSpells($equipmentTemplate->getSpells())
            ->setSkills($equipmentTemplate->getSkills());

        return $armor;
    }
}
