<?php

namespace App\Repository;

use App\Entity\Skill;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Skill>
 */
class SkillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Skill::class);
    }

    public function findOneByName(string $name): ?Skill
    {
        return $this->findOneBy(['name' => $name]);
    }

    /** @return Skill[] */
    public function getLastFiveSkills(): array
    {
        return $this->findBy([], ['updatedAt' => 'DESC'], 5);
    }

    public function delete(Skill $skill): void
    {
        $this->getEntityManager()->remove($skill);
        $this->getEntityManager()->flush();
    }

    public function save(Skill $skill): void
    {
        $this->getEntityManager()->persist($skill);
        $this->getEntityManager()->flush();
    }
}
