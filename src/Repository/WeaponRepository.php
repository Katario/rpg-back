<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Weapon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Weapon>
 */
class WeaponRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Weapon::class);
    }

    public function save(Weapon $weapon): void
    {
        $this->getEntityManager()->persist($weapon);
        $this->getEntityManager()->flush();
    }

    public function delete(Weapon $weapon): void
    {
        $this->getEntityManager()->remove($weapon);
        $this->getEntityManager()->flush();
    }
}
