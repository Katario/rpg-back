<?php

namespace App\Repository;

use App\Entity\Talent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Talent>
 */
class TalentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Talent::class);
    }

    public function findOneByName(string $name): ?Talent
    {
        return $this->findOneBy(['name' => $name]);
    }

    /** @return Talent[] */
    public function getLastFiveTalents(): array
    {
        return $this->findBy([], ['updatedAt' => 'DESC'], 5);
    }

    public function delete(Talent $talent): void
    {
        $this->getEntityManager()->remove($talent);
        $this->getEntityManager()->flush();
    }

    public function save(Talent $talent): void
    {
        $this->getEntityManager()->persist($talent);
        $this->getEntityManager()->flush();
    }
}
