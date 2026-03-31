<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Armor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Armor>
 */
class ArmorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Armor::class);
    }

    public function save(Armor $armor): void
    {
        $this->getEntityManager()->persist($armor);
        $this->getEntityManager()->flush();
    }

    public function delete(Armor $armor): void
    {
        $this->getEntityManager()->remove($armor);
        $this->getEntityManager()->flush();
    }
}
