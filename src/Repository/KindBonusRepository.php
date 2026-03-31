<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\KindBonus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KindBonus>
 */
class KindBonusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KindBonus::class);
    }
}
