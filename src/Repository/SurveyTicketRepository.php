<?php

namespace App\Repository;

use App\Entity\SurveyTicket;
use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SurveyTicket|null find($id, $lockMode = null, $lockVersion = null)
 * @method SurveyTicket|null findOneBy(array $criteria, array $orderBy = null)
 * @method SurveyTicket[]    findAll()
 * @method SurveyTicket[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurveyTicket::class);
    }

    // /**
    //  * @return SurveyTicket[] Returns an array of SurveyTicket objects
    //  */

    public function findOrdered(Ticket $ticket)
    {
        return $this->createQueryBuilder('st')
            ->andWhere('st.ticket = :ticket')
            ->setParameter('ticket', $ticket)
            ->orderBy('st.orderBy', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?SurveyTicket
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
