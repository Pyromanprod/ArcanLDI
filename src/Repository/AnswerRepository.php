<?php

namespace App\Repository;

use App\Entity\Answer;
use App\Entity\Game;
use App\Entity\Question;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Answer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Answer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Answer[]    findAll()
 * @method Answer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Answer::class);
    }


    public function findByUserQuestion(User $user, Question $question): bool
    {
        $response = $this->createQueryBuilder('a')
            ->andWhere('a.player = :user')
            ->andWhere('a.question = :question')
            ->setParameter('user', $user)
            ->setParameter('question', $question)
            ->getQuery()
            ->getResult();
        if ($response) {
            return true;
        } else {
            return false;
        }
    }
    public function findByQuestionPlayer(Question $question, User $player)
    {
        return $this->createQueryBuilder('a')

            ->andWhere('a.question = :question')
            ->andWhere('a.player = :player')
            ->setParameter('question', $question)
            ->setParameter('player', $player)
            ->getQuery()
            ->getOneOrNullResult();
    }


    public function findByGame(Game $game)
    {
        return $this->createQueryBuilder('a')
            ->join('a.question', 'question')
            ->join('question.survey', 'survey')
            ->join('survey.surveyTickets', 'survey_tickets')
            ->join('survey_tickets.ticket', 'ticket')
            ->join('ticket.game', 'game')
            ->andWhere('game = :game')
            ->setParameter('game', $game)
            ->getQuery()
            ->getResult();
    }
    public function findByUserGame(Game $game, User $user)
    {
        return $this->createQueryBuilder('a')
            ->join('a.question', 'question')
            ->join('question.survey', 'survey')
            ->join('survey.surveyTickets', 'survey_tickets')
            ->join('survey_tickets.ticket', 'ticket')
            ->join('ticket.game', 'game')
            ->andWhere('game = :game')
            ->andWhere('a.player = :player')
            ->setParameter('game', $game)
            ->setParameter('player', $user)
            ->getQuery()
            ->getResult();
    }


    /*
    public function findOneBySomeField($value): ?Answer
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
