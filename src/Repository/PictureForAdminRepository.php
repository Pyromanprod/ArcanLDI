<?php

namespace App\Repository;

use App\Entity\PictureForAdmin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PictureForAdmin|null find($id, $lockMode = null, $lockVersion = null)
 * @method PictureForAdmin|null findOneBy(array $criteria, array $orderBy = null)
 * @method PictureForAdmin[]    findAll()
 * @method PictureForAdmin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PictureForAdminRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PictureForAdmin::class);
    }

    // /**
    //  * @return PictureForAdmin[] Returns an array of PictureForAdmin objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PictureForAdmin
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
