<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Being;
use App\Entity\BeingItem;
use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BeingItem>
 */
class BeingItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BeingItem::class);
    }

    public function findOneByBeingAndItem(Being $being, Item $item): ?BeingItem
    {
        return $this->findOneBy(['being' => $being, 'item' => $item]);
    }

    public function save(BeingItem $beingItem): void
    {
        $this->getEntityManager()->persist($beingItem);
        $this->getEntityManager()->flush();
    }

    public function delete(BeingItem $beingItem): void
    {
        $this->getEntityManager()->remove($beingItem);
        $this->getEntityManager()->flush();
    }
}
