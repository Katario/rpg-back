<?php

namespace App\Repository;

use App\Entity\TalentLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TalentLevel>
 */
class TalentLevelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TalentLevel::class);
    }

    public function save(TalentLevel $talentLevel): void
    {
        $this->getEntityManager()->persist($talentLevel);
        $this->getEntityManager()->flush();
    }
}
