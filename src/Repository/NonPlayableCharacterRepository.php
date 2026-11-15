<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\NonPlayableCharacter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NonPlayableCharacter>
 */
class NonPlayableCharacterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NonPlayableCharacter::class);
    }

    /** @return NonPlayableCharacter[] */
    public function findByGameBySearch(int $gameId, ?string $query, ?int $limit = null, string $orderBy = 'ASC'): array
    {
        $queryBuilder = $this->createQueryBuilder('npc');
        $queryBuilder->where('npc.name LIKE :query')
            ->orWhere('npc.lastName LIKE :query')
            ->andWhere('npc.game = :gameId')
            ->setParameter('query', '%'.$query.'%')
            ->setParameter('gameId', $gameId)
            ->orderBy('npc.name', $orderBy)
        ;

        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    public function delete(NonPlayableCharacter $nonPlayableCharacter): void
    {
        $this->getEntityManager()->remove($nonPlayableCharacter);
        $this->getEntityManager()->flush();
    }

    public function save(NonPlayableCharacter $nonPlayableCharacter): void
    {
        $this->getEntityManager()->persist($nonPlayableCharacter);
        $this->getEntityManager()->flush();
    }
}
