<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /** @return Game[] */
    public function getGamesAsGameMaster(UserInterface $user): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.gameMaster = :gameMaster')
            ->setParameter('gameMaster', $user)
            ->getQuery()
            ->getResult();
    }

    public function delete(Game $game): void
    {
        $this->getEntityManager()->remove($game);
        $this->getEntityManager()->flush();
    }

    public function save(Game $game): void
    {
        //        $game->setUpdatedAt(new \DateTimeImmutable());

        $this->getEntityManager()->persist($game);
        $this->getEntityManager()->flush();
    }
}
