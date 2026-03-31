<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BeingTalent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BeingTalent>
 */
class BeingTalentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BeingTalent::class);
    }

    public function delete(BeingTalent $beingTalent): void
    {
        $this->getEntityManager()->remove($beingTalent);
        $this->getEntityManager()->flush();
    }

    public function save(BeingTalent $beingTalent): void
    {
        $this->getEntityManager()->persist($beingTalent);
        $this->getEntityManager()->flush();
    }
}
