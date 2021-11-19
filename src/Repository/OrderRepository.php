<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\Order;
use App\Entity\User;
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

    public function findOneorder(Game $game, User $player){
        return $this->createQueryBuilder('a')
            ->innerJoin('a.ticket','b')
            ->where('b.game = :game')
            ->andWhere('a.player = :player')
            ->setParameter(':player',$player)
            ->setParameter(':game', $game)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
    public function findByGame(Game $game){
        return $this->createQueryBuilder('a')
            ->innerJoin('a.ticket','b')
            ->where('b.game = :game')
            ->setParameter(':game', $game)
            ->getQuery()
            ->getResult()
            ;
    }
    public function findRefundRequestedOrder(){
        return $this->createQueryBuilder('a')
            ->andWhere('a.refundRequest is not null')
            ->andWhere('a.refundRequest != :reject ')
            ->setParameter(':reject','rejected')
            ->getQuery()
            ->getResult()
            ;
    }
}
