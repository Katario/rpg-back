<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ApiToken;
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
