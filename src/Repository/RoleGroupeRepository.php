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

    /**
     * @return RoleGroupe[] Returns an array of RoleGroupe objects
     */

    public function findOnlyNotUsed($roles)
    {
        foreach ($roles as $role) {
            $qb = $this->createQueryBuilder('r')
                ->andWhere('r.name != :val')
                ->andWhere('r.name != :public')
                ->setParameter('public', 'public')
                ->setParameter('val', $role->getName());
        }
        return $qb->getQuery()->getResult();
    }

    public function findAllButPublic()
    {

        return $this->createQueryBuilder('r')
            ->andWhere('r.name != :public')
            ->setParameter('public', 'public')
            ->getQuery()
            ->getResult();
    }
    public function findAllPlayerRoleButPublic($player)
    {

        return $this->createQueryBuilder('r')
            ->join('r.player','p')
            ->where('p = :player')
            ->andWhere('r.name != :public')
            ->setParameter('player',$player)
            ->setParameter('public', 'public')
            ->getQuery()
            ->getResult();
    }



    public function findOneByGame($game): ?RoleGroupe
    {
        return $this->createQueryBuilder('r')
            ->join('r.game','g')
            ->andWhere('r.name = :public')
            ->andWhere('r.game = :game')
            ->setParameter('game', $game)
            ->setParameter('public', 'public')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

}
