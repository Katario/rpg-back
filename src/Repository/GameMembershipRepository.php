<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Game;
use App\Entity\GameMembership;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameMembership>
 */
class GameMembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameMembership::class);
    }

    public function findOneByGameAndUser(Game $game, User $user): ?GameMembership
    {
        return $this->findOneBy(['game' => $game, 'user' => $user]);
    }

    /**
     * The games the user plays in (not the ones they run), with the game and its game master loaded.
     *
     * @return list<GameMembership>
     */
    public function findByPlayer(User $user): array
    {
        /** @var list<GameMembership> $memberships */
        $memberships = $this->createQueryBuilder('m')
            ->addSelect('g', 'gm')
            ->join('m.game', 'g')
            ->join('g.gameMaster', 'gm')
            ->where('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $memberships;
    }
}
