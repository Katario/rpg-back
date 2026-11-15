<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Character;
use App\Entity\GameMembership;
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

    /**
     * Who can be given a character of the game (back office character form): its players (memberships) who have no
     * character in the game yet, plus the current owner so that editing a character keeps them.
     */
    public function playersAvailableForCharacterQueryBuilder(int $gameId, ?int $currentOwnerId = null): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('u');
        $expr = $queryBuilder->expr();

        $isAvailablePlayer = $expr->andX(
            $expr->exists(
                $this->getEntityManager()->createQueryBuilder()
                    ->select('m.id')
                    ->from(GameMembership::class, 'm')
                    ->where('m.game = :gameId')
                    ->andWhere('m.user = u')
                    ->getDQL()
            ),
            $expr->not($expr->exists(
                $this->getEntityManager()->createQueryBuilder()
                    ->select('c.id')
                    ->from(Character::class, 'c')
                    ->where('c.game = :gameId')
                    ->andWhere('c.user = u')
                    ->getDQL()
            )),
        );

        $queryBuilder
            ->where(null === $currentOwnerId ? $isAvailablePlayer : $expr->orX($isAvailablePlayer, 'u.id = :currentOwnerId'))
            ->setParameter('gameId', $gameId)
            ->orderBy('u.email', 'ASC');

        if (null !== $currentOwnerId) {
            $queryBuilder->setParameter('currentOwnerId', $currentOwnerId);
        }

        return $queryBuilder;
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
