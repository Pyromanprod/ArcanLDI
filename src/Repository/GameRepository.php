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

    public function findLast()
    {
        return $this->createQueryBuilder('g')
            ->orderBy('g.createdAt', 'DESC')
            ->where('g.isPublished = 1')
            ->setMaxResults(1)
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

    /**
      * @return Game[] Returns an array of Game objects
      */
    public function findOneOutdatedGame($date): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.dateEnd < :date')
            ->setParameter(':date',$date)
            ->getQuery()
            ->getResult()
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
