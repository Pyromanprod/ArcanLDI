<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\GameRepository;
use App\Repository\RoleGroupeRepository;
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
    public function indexticket(Ticket $ticket,UserRepository $userRepository,TicketRepository $ticketRepository,RoleGroupeRepository $groupeRepository): Response
    {

        return $this->render('user/inde_ticket.html.twig', [
            'users' => $userRepository->findplayer($ticket),
            'roles' => $groupeRepository->findAllPlayerRoleButPublic($userRepository->findplayer($ticket)),
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

    #[Route('-profile/edit', name: 'user_edit_profile', methods: ['GET','POST'])]
    #[IsGranted('ROLE_USER')]
    public function editProfile(Request $request): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //TODO:Attendre que renaud deigne me donner le service des photo
            $photo = $form->get('photo')->getData();
            if (!$photo){
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success','Vos informations ont bien été modifier');
                return $this->redirectToRoute('user_show_profile', [
                    'id' => $this->getUser()->getId()
                ], Response::HTTP_SEE_OTHER);
            }


            if ($this->getUser()->getPhoto() != null && file_exists($this->getParameter('user.photo.directory'). $this->getUser()->getPhoto())){

                unlink( $this->getParameter('user.photo.directory') . $this->getUser()->getPhoto() );
            }

            do{
                $newFileName = md5(random_bytes(100)).'.'. $photo->guessExtension();

            }while(file_exists($this->getParameter('user.photo.directory'). $newFileName));


            $this->getUser()->setPhoto($newFileName);

            $this->getDoctrine()->getManager()->flush();
            $photo->move(
                $this->getParameter('user.photo.directory'),
                $newFileName
            );
            $this->addFlash('success','Vos informations ont bien été modifier');
            return $this->redirectToRoute('user_show_profile', [
                'id' => $this->getUser()->getId()
            ], Response::HTTP_SEE_OTHER);
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
        return $this->render('user/show_profile.html.twig');
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
