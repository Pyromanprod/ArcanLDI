<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\SurveyTicket;
use App\Entity\Ticket;
use App\Form\TicketType;
use App\Repository\GameRepository;
use App\Repository\SurveyRepository;
use App\Repository\TicketRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('ROLE_ADMIN')]
#[Route('/ticket')]
class TicketController extends AbstractController
{
    #[Route('/', name: 'ticket_index', methods: ['GET'])]
    public function index(TicketRepository $ticketRepository,GameRepository $gameRepository): Response
    {

        return $this->render('ticket/index.html.twig', [
            'games' => $gameRepository->findAll(),
            'tickets' => $ticketRepository->findAll(),
        ]);
    }

    #[Route('/game/{id}', name: 'ticket_index_game', methods: ['GET'])]
    public function indexGame(TicketRepository $ticketRepository, Game $game, GameRepository $gameRepository): Response
    {
        $tickets = $ticketRepository->findByGame($game);

        return $this->render('ticket/index.html.twig', [
            'tickets' => $tickets,
            'games' =>  $gameRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'ticket_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): Response
    {
        $ticket = new Ticket();
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($ticket);
            $entityManager->flush();

            return $this->redirectToRoute('ticket_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('ticket/new.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'ticket_show', methods: ['GET', 'POST'])]
    public function show(Ticket $ticket, Request $request, SurveyRepository $surveyRepository): Response
    {

//TODO: make:form
        $formNotGeneral = $this->createFormBuilder()
            ->add('survey', EntityType::class, [
                'class' => 'App\Entity\Survey',
                'choices' => $surveyRepository->findByNotOnTicket(),
                'choice_label' => 'name',
            ])->getForm()
        ;
            $surveyTicket = new SurveyTicket();
            $surveyTicket->setTicket($ticket);

        $formNotGeneral->handleRequest($request);

        if ($formNotGeneral->isSubmitted() && $formNotGeneral->isValid()) {

            $surveyTicket->setSurvey($formNotGeneral->get('survey')->getData());

            $this->getDoctrine()->getManager()->persist($surveyTicket);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('ticket_show', ['id'=>$ticket->getId()]);
        }


        $formGeneral = $this->createFormBuilder()
            ->add('survey', EntityType::class, [
                'class' => 'App\Entity\Survey',
                'choices' => $surveyRepository->findByGeneral('1'),
                'choice_label' => 'name',
            ])->getForm()
        ;

        // Ajout des questionnaires "Généraux"
        $formGeneral->handleRequest($request);
        if ($formGeneral->isSubmitted() && $formGeneral->isValid()) {

            $surveyTicket->setSurvey($formGeneral->get('survey')->getData());

            $this->getDoctrine()->getManager()->persist($surveyTicket);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('ticket_show', ['id'=>$ticket->getId()]);
        }




        return $this->renderForm('ticket/show.html.twig', [
            'ticket' => $ticket,
            'formgeneral' => $formGeneral,
            'formnotgeneral' => $formNotGeneral,
        ]);


    }

    #[Route('/{id}/edit', name: 'ticket_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ticket $ticket): Response
    {
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('ticket_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('ticket/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'ticket_delete', methods: ['POST'])]
    public function delete(Request $request, Ticket $ticket): Response
    {
        if ($this->isCsrfTokenValid('delete' . $ticket->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($ticket);
            $entityManager->flush();
        }

        return $this->redirectToRoute('ticket_index', [], Response::HTTP_SEE_OTHER);
    }
}
