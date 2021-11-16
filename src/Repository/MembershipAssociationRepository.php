<?php

namespace App\Repository;

use App\Entity\MembershipAssociation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MembershipAssociation|null find($id, $lockMode = null, $lockVersion = null)
 * @method MembershipAssociation|null findOneBy(array $criteria, array $orderBy = null)
 * @method MembershipAssociation[]    findAll()
 * @method MembershipAssociation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MembershipAssociationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MembershipAssociation::class);
    }


    public function findByPlayerNotPaid($player,$membership)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.membership = :membership ')
            ->andWhere('m.member = :player')
            ->andWhere('m.paid = 0')
            ->setParameter('player', $player)
            ->setParameter('membership', $membership)
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }




    /*
    public function findOneBySomeField($value): ?MembershipAssociation
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
