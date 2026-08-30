<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Character;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepositoryInterface<Character>
 */
class CharacterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Character::class);
    }

    public function findOneByToken(string $token): ?Character
    {
        return $this->findOneBy(['token' => $token]);
    }

    /** @return Character[] */
    public function findByGameBySearch(int $gameId, ?string $query, ?int $limit = null, string $orderBy = 'ASC'): array
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder->where('c.name LIKE :query')
            ->orWhere('c.lastName LIKE :query')
            ->andWhere('c.game = :gameId')
            ->setParameter('query', '%'.$query.'%')
            ->setParameter('gameId', $gameId)
            ->orderBy('c.name', $orderBy)
        ;

        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    public function deleteFromGame(int $gameId): void
    {
        $this->createQueryBuilder('c')
            ->delete()
            ->where('c.game = :gameId')
            ->setParameter('gameId', $gameId)
            ->getQuery()
            ->getResult()
        ;
    }

    public function delete(Character $character): void
    {
        $this->getEntityManager()->remove($character);
        $this->getEntityManager()->flush();
    }

    public function save(Character $character): void
    {
        $this->getEntityManager()->persist($character);
        $this->getEntityManager()->flush();
    }
}
