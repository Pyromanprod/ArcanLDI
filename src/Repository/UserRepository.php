<?php

namespace App\Repository;


use App\Entity\MembershipAssociation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function findplayer($ticket){
        return $this->createQueryBuilder('a')
            ->innerJoin('a.orders','b')
            ->where('b.datePaid is NOT NULL')
            ->andWhere('b.ticket = :ticket')
            ->setParameter(':ticket',$ticket)
            ->getQuery()
            ->getResult();
    }


    /**
     * @return User[]
     */
    public function findPlayerIn($membership): array
    {
        return $this->createQueryBuilder('a')
            ->Join('a.membershipAssociations','m')
            ->where('m.member = a')
            ->andWhere('m.membership = :membership')
            ->setParameter(':membership',$membership)
            ->getQuery()
            ->getResult();
    }


    public function findByPlayersNotPaid($membership)
    {
        return $this->createQueryBuilder('a')
            ->Join('a.membershipAssociations','m')
            ->where('m.member = a')
            ->andWhere('m.membership = :membership')
            ->andWhere('m.paid = 0')
            ->setParameter(':membership',$membership)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findPlayerNotIn($players): array
    {

            if ($players == null ){
               return $this->findAll();
            }else{
            $qb = $this->createQueryBuilder('a')
                ->where('a NOT IN (:player)')
                ->setParameter('player',$players);


            return $qb->getQuery()->getResult();
            }



    }

    public function findRoleArticle( $role, User $user){
        return $this->createQueryBuilder('a')
            ->join('a.roleGroupes','r')
            ->andWhere('a = :user')
            ->andWhere('r = :val')
            ->setParameter(':user',$user)
            ->setParameter(':val',$role)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
