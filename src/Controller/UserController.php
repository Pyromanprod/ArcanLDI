<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository,TicketRepository $ticketRepository): Response
    {
        $tickets = $ticketRepository->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
            'tickets' => $tickets
        ]);
    }

    #[Route('/{id}/ticket', name: 'user_index_ticket', methods: ['GET'])]
    public function indexticket(Ticket $ticket,UserRepository $userRepository,TicketRepository $ticketRepository): Response
    {
        $tickets = $ticketRepository->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findplayer($ticket),
            'tickets' => $tickets
        ]);
    }

    #[Route('/new', name: 'user_new', methods: ['GET','POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

//    #[Route('/{id}/edit', name: 'user_edit', methods: ['GET','POST'])]
//    public function edit(Request $request, User $user): Response
//    {
//        $form = $this->createForm(UserType::class, $user);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $this->getDoctrine()->getManager()->flush();
//
//            return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
//        }
//
//        return $this->renderForm('user/edit.html.twig', [
//            'user' => $user,
//            'form' => $form,
//        ]);
//    }

}
