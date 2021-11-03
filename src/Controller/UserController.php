<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\GameRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/user')]
class UserController extends AbstractController
{

    #[Route('/', name: 'user_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(UserRepository $userRepository,TicketRepository $ticketRepository,GameRepository $gameRepository): Response
    {
        $tickets = $ticketRepository->findOneBydate(new \DateTime());

        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
            'tickets' => $tickets
        ]);
    }

    #[Route('/{id}/ticket', name: 'user_index_ticket', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function indexticket(Ticket $ticket,UserRepository $userRepository,TicketRepository $ticketRepository): Response
    {

        return $this->render('user/inde_ticket.html.twig', [
            'users' => $userRepository->findplayer($ticket),
            'tickets' => $ticket
        ]);
    }


    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'user_edit', methods: ['GET','POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('-profile/edit', name: 'user_edit_profile', methods: ['GET','POST'])]
    #[IsGranted('ROLE_USER')]
    public function editProfile(Request $request): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success','Vos informations ont bien été modifier');
            return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('-profile/', name: 'user_show_profile', methods: ['GET','POST'])]
    #[IsGranted('ROLE_USER')]
    public function showProfile(): Response
    {

        $user = $this->getUser();
        return $this->render('user/show_profile.html.twig', [
            'user' => $user,
        ]);
    }
    #[Route('/{id}', name: 'user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
    }

}
