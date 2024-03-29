<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\GameRepository;
use App\Repository\RoleGroupeRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use Doctrine\Migrations\Configuration\EntityManager\ManagerRegistryEntityManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/joueur')]
class UserController extends AbstractController
{
    //crud User///
    #[Route('/', name: 'user_index', methods: ['GET'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function index(UserRepository $userRepository,TicketRepository $ticketRepository,PaginatorInterface $paginator,Request $request): Response
    {
        //paginator pour la pagination
        $requestedPage = $request->query->getInt('page', 1);
        if($requestedPage < 1){
            throw new NotFoundHttpException();
        }
        $user = $paginator->paginate(
            $userRepository->findAll(),
            $requestedPage,
            48
        );
        $tickets = $ticketRepository->findOneBydate(new \DateTime());

        return $this->render('user/index.html.twig', [
            'users' => $user,
            'tickets' => $tickets
        ]);
    }

    //moderation>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>

    //liste des moderateur
    #[Route('/moderation', name: 'moderator_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function indexModerator(UserRepository $userRepository): Response
    {


        return $this->render('user/index_moderator.html.twig', [
            'users' => $userRepository->findPlayerByRole(),
        ]);
    }

    //recherche d'un compte pour lui ajouter le role
    #[Route('/moderation/ajouter', name: 'moderator_Add_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function indexModeratorAdd(UserRepository $userRepository,Request $request): Response
    {
        $email = $request->get('moderation');

        return $this->render('user/index_moderator_add.html.twig', [
            'users' => $userRepository->findPlayerByEmail($email),
        ]);
    }
    //ajouter le role a un compte precedement rechercher
    #[Route('/moderation/ajouter/{id}', name: 'moderator_Add', methods: ['GET','POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function ModeratorAdd(User $user,UserRepository $userRepository,Request $request,EntityManagerInterface $managerRegistry): Response
    {
        if ($this->isCsrfTokenValid('add' . $user->getId(), $request->request->get('_token'))) {
            $user->setRoles(["ROLE_MODERATOR"]);
            $managerRegistry->flush();
        }
        return $this->redirectToRoute('moderator_index', [
            'users' => $userRepository->findPlayerByRole(),
        ]);
    }
    //retrait le role moderateur a un compte
    #[Route('/moderation/retirer/{id}', name: 'moderator_Delete', methods: ['GET','POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function ModeratorDelete(User $user,UserRepository $userRepository,Request $request,EntityManagerInterface $managerRegistry): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $user->setRoles([]);
            $managerRegistry->flush();
        }
        return $this->redirectToRoute('moderator_index', [
            'users' => $userRepository->findPlayerByRole(),
        ]);
    }

    //moderation <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

    //liste des joueur en fonction d'un ticket
    #[Route('/{id}/ticket', name: 'user_index_ticket', methods: ['GET'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function indexticket(Ticket $ticket,UserRepository $userRepository,PaginatorInterface $paginator, Request $request): Response
    {
        $requestedPage = $request->query->getInt('page', 1);
        if($requestedPage < 1){
            throw new NotFoundHttpException();
        }
        $user = $paginator->paginate(
            $userRepository->findplayer($ticket),
            $requestedPage,
            48
        );

        return $this->render('user/inde_ticket.html.twig', [
            'users' => $user,
            'tickets' => $ticket
        ]);
    }

    //détail du profile d'un joueur
    #[Route('/{id}', name: 'user_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    //profile de l'utilisateur
    #[Route('-profil/', name: 'user_show_profile', methods: ['GET','POST'])]
    #[IsGranted('ROLE_USER')]
    public function showProfile(): Response
    {
        return $this->render('user/show_profile.html.twig');
    }

    //modification du profile
    #[Route('-profil/modifier', name: 'user_edit_profile', methods: ['GET','POST'])]
    #[IsGranted('ROLE_USER')]
    public function editProfile(Request $request,EntityManagerInterface $managerRegistry): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photo = $form->get('photo')->getData();
            if (!$photo){
                $managerRegistry->flush();
                $this->addFlash('success','Vos informations ont bien été modifiées.');
                return $this->redirectToRoute('user_show_profile', [
                    'id' => $this->getUser()->getId()
                ], Response::HTTP_SEE_OTHER);
            }

            //changement de nom de la photo et verification de l'unicité de  celui-ci
            if ($this->getUser()->getPhoto() != null && file_exists($this->getParameter('user.photo.directory'). $this->getUser()->getPhoto())){

                unlink( $this->getParameter('user.photo.directory') . $this->getUser()->getPhoto() );
            }

            do{
                $newFileName = md5(random_bytes(100)).'.'. $photo->guessExtension();

            }while(file_exists($this->getParameter('user.photo.directory'). $newFileName));


            $this->getUser()->setPhoto($newFileName);

            $this->getDoctrine()->getManager()->flush();
            //move de la photo dans le dossier prévu
            $photo->move(
                $this->getParameter('user.photo.directory'),
                $newFileName
            );
            $this->addFlash('success','Vos informations ont bien été modifiées.');
            return $this->redirectToRoute('user_show_profile', [
                'id' => $this->getUser()->getId()
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    //supression d'un compte
    #[Route('/{id}/supprimer', name: 'user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, User $user, EntityManagerInterface $managerRegistry): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $managerRegistry->remove($user);
            $managerRegistry->flush();
        }

        return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
    }

}
