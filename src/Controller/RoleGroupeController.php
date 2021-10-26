<?php

namespace App\Controller;

use App\Entity\RoleGroupe;
use App\Form\RoleGroupeType;
use App\Repository\RoleGroupeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/role')]
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
}
