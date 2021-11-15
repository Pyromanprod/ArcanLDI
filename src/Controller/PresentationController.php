<?php

namespace App\Controller;

use App\Entity\Presentation;
use App\Form\PresentationType;
use App\Repository\PresentationRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/presentation')]
class PresentationController extends AbstractController
{
    #[Route('/', name: 'presentation_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(PresentationRepository $presentationRepository): Response
    {
        //si il n'y a pas de présentation on envoie vers la création
        if (!$presentationRepository->findAll()){

            return $this->redirectToRoute('presentation_new');
        }else{
            //si il y a je doit chopper la prés et renvoyer vers l'edit
            $presentation = $presentationRepository->findOneBy([], ['id'=>'DESC']);
            return $this->redirectToRoute('presentation_edit', ['id'=>$presentation->getId()]);
        }
    }

    #[Route('/new', name: 'presentation_new', methods: ['GET','POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): Response
    {
        $presentation = new Presentation();
        $form = $this->createForm(PresentationType::class, $presentation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($presentation);
            $entityManager->flush();

            return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('presentation/new.html.twig', [
            'presentation' => $presentation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'presentation_show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(Presentation $presentation): Response
    {
        return $this->render('presentation/show.html.twig', [
            'presentation' => $presentation,
        ]);
    }

    #[Route('/{id}/edit', name: 'presentation_edit', methods: ['GET','POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Presentation $presentation): Response
    {
        $form = $this->createForm(PresentationType::class, $presentation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('presentation/edit.html.twig', [
            'presentation' => $presentation,
            'form' => $form,
        ]);
    }


}
