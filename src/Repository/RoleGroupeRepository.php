<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\RoleGroupe;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RoleGroupe|null find($id, $lockMode = null, $lockVersion = null)
 * @method RoleGroupe|null findOneBy(array $criteria, array $orderBy = null)
 * @method RoleGroupe[]    findAll()
 * @method RoleGroupe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoleGroupeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoleGroupe::class);
    }

    // /**
    //  * @return RoleGroupe[] Returns an array of RoleGroupe objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RoleGroupe
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

}
