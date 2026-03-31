<?php

namespace App\Repository;

use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Item>
 */
class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    public function findOneByName(string $name): ?Item
    {
        return $this->findOneBy(['name' => $name]);
    }

    /** @return Item[] */
    public function getLastFiveItems(): array
    {
        return $this->findBy([], ['updatedAt' => 'DESC'], 5);
    }

    public function delete(Item $item): void
    {
        $this->getEntityManager()->remove($item);
        $this->getEntityManager()->flush();
    }

    public function save(Item $item): void
    {
        $this->getEntityManager()->persist($item);
        $this->getEntityManager()->flush();
    }
}
