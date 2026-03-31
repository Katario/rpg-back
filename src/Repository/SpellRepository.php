<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Spell;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Spell>
 */
class SpellRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Spell::class);
    }

    public function findOneByName(string $name): ?Spell
    {
        return $this->findOneBy(['name' => $name]);
    }

    /** @return Spell[] */
    public function getLastFiveSpells(): ?array
    {
        return $this->findBy([], ['updatedAt' => 'DESC'], 5);
    }

    public function delete(Spell $spell): void
    {
        $this->getEntityManager()->remove($spell);
        $this->getEntityManager()->flush();
    }

    public function save(Spell $spell): void
    {
        $this->getEntityManager()->persist($spell);
        $this->getEntityManager()->flush();
    }
}
