<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\EquipmentTemplate;
use App\Entity\Weapon;

class WeaponFactory
{
    public function createOneFromEquipmentTemplate(EquipmentTemplate $equipmentTemplate): Weapon
    {
        $weapon = new Weapon();
        $weapon
            ->setName($equipmentTemplate->getName())
            ->setValue($equipmentTemplate->getValue())
            ->setCurrentDurabilityPoints($equipmentTemplate->getMaxDurability())
            ->setMaxDurabilityPoints($equipmentTemplate->getMaxDurability())
            ->setDescription($equipmentTemplate->getDescription())
            ->setSpells($equipmentTemplate->getSpells())
            ->setSkills($equipmentTemplate->getSkills());

        return $weapon;
    }
}
