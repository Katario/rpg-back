<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Character;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->findOneByEmail($identifier);
    }

    public function findOneByEmail(string $email): ?User
    {
        $email = trim($email);

        // Exact match first: the unique index guarantees at most one row, and it disambiguates
        // legacy accounts whose emails only differ by case.
        $user = $this->findOneBy(['email' => $email]);
        if ($user) {
            return $user;
        }

        /** @var User|null $user */
        $user = $this->createQueryBuilder('u')
            ->where('LOWER(u.email) = :email')
            ->setParameter('email', mb_strtolower($email))
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $user;
    }

    public function getAvailableForNewCharacterUser(int $gameId, int $userId): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('u');

        return $queryBuilder
            ->where(
                $queryBuilder->expr()->not(
                    $queryBuilder->expr()->exists(
                        $this->createQueryBuilder('uc')
                            ->from(Character::class, 'c')
                            ->where('c.game = :gameId')
                            ->andWhere('c.user = u.id')
                            ->getDQL()
                    )
                )
            )
            ->andWhere('u.id != :userId')
            ->setParameter('gameId', $gameId)
            ->setParameter('userId', $userId);
    }

    public function delete(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }

    public function save(User $user): void
    {
        //        $user->setUpdatedAt(new \DateTimeImmutable());

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}
