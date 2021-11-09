<?php

namespace App\Repository;

use App\Entity\GameComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GameComment|null find($id, $lockMode = null, $lockVersion = null)
 * @method GameComment|null findOneBy(array $criteria, array $orderBy = null)
 * @method GameComment[]    findAll()
 * @method GameComment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GameCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameComment::class);
    }

    // /**
    //  * @return GameComment[] Returns an array of GameComment objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?GameComment
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
