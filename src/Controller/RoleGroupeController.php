<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\RoleGroupe;
use App\Entity\User;
use App\Form\RoleAddFormType;
use App\Form\RoleGroupeType;
use App\Repository\OrderRepository;
use App\Repository\RoleGroupeRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/role')]
#[IsGranted('ROLE_ADMIN')]
class RoleGroupeController extends AbstractController
{
    #[Route('/', name: 'role_groupe_index', methods: ['GET'])]
    public function index(RoleGroupeRepository $roleGroupeRepository): Response
    {
        return $this->render('role_groupe/index.html.twig', [
            'role_groupes' => $roleGroupeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'role_groupe_new', methods: ['GET','POST'])]
    public function new(Request $request): Response
    {
        $roleGroupe = new RoleGroupe();
        $form = $this->createForm(RoleGroupeType::class, $roleGroupe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($roleGroupe);
            $entityManager->flush();

            return $this->redirectToRoute('role_groupe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('role_groupe/new.html.twig', [
            'role_groupe' => $roleGroupe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'role_groupe_show', methods: ['GET'])]
    public function show(RoleGroupe $roleGroupe): Response
    {
        return $this->render('role_groupe/show.html.twig', [
            'role_groupe' => $roleGroupe,
        ]);
    }

    #[Route('/{id}/edit', name: 'role_groupe_edit', methods: ['GET','POST'])]
    public function edit(Request $request, RoleGroupe $roleGroupe): Response
    {
        $form = $this->createForm(RoleGroupeType::class, $roleGroupe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('role_groupe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('role_groupe/edit.html.twig', [
            'role_groupe' => $roleGroupe,
            'form' => $form,
        ]);
    }
    //ajout role de groupe a un utilisateurdfg
    #[Route('/{id}/add', name: 'role_groupe_add', methods: ['GET','POST'])]
    public function add(Request $request, User $user, OrderRepository $orderRepository): Response
    {

        $form = $this->createForm(RoleAddFormType::class);
        $form->handleRequest($request);
        $id = $orderRepository->findOneByPlayer($user)->getTicket()->getId();
        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->get('name')->getData() as $role){
            $user->addRoleGroupe($role);
            }
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_index',[
                'id' => $id
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('role_groupe/_add.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'role_groupe_delete', methods: ['POST'])]
    public function delete(Request $request, RoleGroupe $roleGroupe): Response
    {
        if ($this->isCsrfTokenValid('delete'.$roleGroupe->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($roleGroupe);
            $entityManager->flush();
        }

        return $this->redirectToRoute('role_groupe_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/delete/{pseudo}-{id}', name: 'role_delete', methods: ['POST','GET'])]
    #[ParamConverter('user', options: ['mapping' => ['pseudo' => 'pseudo']])]
    #[ParamConverter('roleGroupe', options: ['mapping' => ['id' => 'id']])]
    public function deleteRole(User $user,RoleGroupe $roleGroupe, Request $request): Response
    {
        dump($user);
        $entityManager = $this->getDoctrine()->getRepository(Order::class);
        $id = $entityManager->findOneByPlayer($user)->getTicket()->getId();

        if ($this->isCsrfTokenValid('delete'.$roleGroupe->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $user->removeRoleGroupe($roleGroupe);
            $entityManager->flush();
        } else{
            $this->addFlash('error','role non retirer');
        }

        return $this->redirectToRoute('user_index_ticket', [
            'id' => $id

        ], Response::HTTP_SEE_OTHER);
    }
}
