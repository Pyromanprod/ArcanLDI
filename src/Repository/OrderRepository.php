<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findOneorder($game_id,$player_id){
        return $this->createQueryBuilder('a')
            ->innerJoin('a.ticket','b')
            ->innerJoin('b.game','c')
            ->innerJoin('a.player','d')
            ->where('c.id = :id')
            ->andWhere('d.id = :player')
            ->setParameter(':player',$player_id)
            ->setParameter(':id', $game_id)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
}
