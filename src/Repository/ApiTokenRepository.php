<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ApiToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiToken>
 */
class ApiTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiToken::class);
    }

    /**
     * Looks a token up by its plain value (hashed before querying). Expiry is not checked here.
     */
    public function findOneByPlainToken(string $plainToken): ?ApiToken
    {
        return $this->findOneBy(['tokenHash' => ApiToken::hash($plainToken)]);
    }

    /**
     * @return int number of deleted tokens
     */
    public function deleteExpired(\DateTimeImmutable $now = new \DateTimeImmutable()): int
    {
        /** @var int $deleted */
        $deleted = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.expiresAt <= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();

        return $deleted;
    }

    /**
     * Signs the user out of every device (e.g. after a password reset).
     *
     * @return int number of deleted tokens
     */
    public function deleteForUser(User $user): int
    {
        /** @var int $deleted */
        $deleted = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();

        return $deleted;
    }

    public function delete(ApiToken $apiToken): void
    {
        $this->getEntityManager()->remove($apiToken);
        $this->getEntityManager()->flush();
    }

    public function save(ApiToken $apiToken): void
    {
        $this->getEntityManager()->persist($apiToken);
        $this->getEntityManager()->flush();
    }
}
