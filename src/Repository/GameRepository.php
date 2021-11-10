<?php

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Game|null find($id, $lockMode = null, $lockVersion = null)
 * @method Game|null findOneBy(array $criteria, array $orderBy = null)
 * @method Game[]    findAll()
 * @method Game[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }



    public function search($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.name LIKE :val')
            ->orWhere('g.description LIKE :val')
            ->andWhere('g.isPublished = 1')
            ->setParameter('val', '%'.$value.'%')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findLastThree()
    {
        return $this->createQueryBuilder('g')
            ->orderBy('g.createdAt', 'DESC')
            ->where('g.isPublished = 1')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult()
            ;
    }
    public function findPlayerGame($game,$player)
    {
        return $this->createQueryBuilder('g')
            ->join('g.tickets','t')
            ->join('t.orders','o')
            ->join('o.player','p')
            ->where('g = :game')
            ->andWhere('p = :player')
            ->setParameter(':game',$game)
            ->setParameter(':player',$player)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    public function findOneOutdatedGame($date): ?Game
    {
        return $this->createQueryBuilder('g')
            ->where('g.dateEnd < :date')
            ->setParameter(':date',$date)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    public function findOneNotStartedGame($date): ?Game
    {
        return $this->createQueryBuilder('g')
            ->where('g.dateStart > :date')
            ->setParameter(':date',$date)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }


}
