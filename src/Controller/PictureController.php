<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Picture;
use App\Form\AlbumPhotoFormType;
use App\Service\uploadGamePhoto;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/album-photo')]
class PictureController extends AbstractController
{


    #[Route('/ajouter-photo/{id}', name: 'game_add_album_Photo', methods: ['POST', 'GET'])]
    #[isGranted('ROLE_ADMIN')]
    public function addAlbumPhoto(uploadGamePhoto $uploadGamePhoto, Request $request, Game $game): Response
    {
        $form = $this->createForm(AlbumPhotoFormType::class);
        $form->handleRequest($request);
        // dossier du jeu dans le game.photo.directory
        $directory = $this->getParameter('game.photo.directory') . $game->getName() . '/album_photo/';
        if ($form->isSubmitted()) {


            $listePhoto = $uploadGamePhoto->uploadAlbum($form->get('photos')->getData(), $game);
            foreach ($listePhoto as $photo) {
                $this->getDoctrine()->getManager()->persist($photo);
            }
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('game_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->renderForm('game/add_album_photo.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{slug}', name: 'see_album', methods: ['POST', 'GET'])]
    public function seeAlbum(Request $request, Game $game): Response
    {

        return $this->render('picture/seeAlbum.html.twig', [
            'game'=>$game,
        ]);

    }

    #[Route('/{id}', name: 'picture_delete', methods: ['POST'])]
    public function delete(Request $request, Picture $picture): Response
    {
        if ($this->isCsrfTokenValid('delete' . $picture->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($picture);
            $entityManager->flush();
        }

        return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
    }
}
