<?php

namespace App\Repository;

use App\Entity\Equipment;
use App\Enum\BeingEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Equipment>
 */
class EquipmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipment::class);
    }

    /** @return Equipment[] */
    public function findByGameBySearch(int $gameId, ?string $query, ?int $limit = null, string $orderBy = 'ASC'): array
    {
        $queryBuilder = $this->createQueryBuilder('a');
        $queryBuilder->where('a.name LIKE :query')
            ->andWhere('a.game = :gameId')
            ->setParameter('query', '%'.$query.'%')
            ->setParameter('gameId', $gameId)
            ->orderBy('a.name', $orderBy)
        ;

        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    public function availableArmamentsQueryBuilder(int $gameId, BeingEnum $owner, ?int $ownerId = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a');
        $qb->leftJoin('a.being', 'b');

        $ownerClass = BeingEnum::toDiscriminatorMapping()[$owner->value];

        $qb
            ->where($qb->expr()->andX(
                $qb->expr()->eq('a.game', ':gameId'),
                $qb->expr()->orX(
                    $qb->expr()->eq('a.isOwned', 'false'),
                    $qb->expr()->andX(
                        $qb->expr()->eq('a.isOwned', 'true'),
                        "b INSTANCE OF {$ownerClass}",
                        $ownerId
                            ? $qb->expr()->eq('a.being', ':ownerId')
                            : '1 = 1',
                    )
                )
            ))
            ->setParameter('gameId', $gameId);

        if ($ownerId) {
            $qb->setParameter('ownerId', $ownerId);
        }

        return $qb;
    }

    public function deleteFromGame(int $gameId): void
    {
        $this->createQueryBuilder('a')
            ->delete()
            ->where('a.game = :gameId')
            ->setParameter('gameId', $gameId)
            ->getQuery()
            ->getResult()
        ;
    }

    public function delete(Equipment $armament): void
    {
        $this->getEntityManager()->remove($armament);
        $this->getEntityManager()->flush();
    }

    public function save(Equipment $armament): void
    {
        $this->getEntityManager()->persist($armament);
        $this->getEntityManager()->flush();
    }
}
