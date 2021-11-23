<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Video;
use App\Form\AlbumVideoFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
#[Route('/video', name: 'video_')]
class VideoController extends AbstractController
{
    #[Route('/{slug}', name: 'see_video')]
    public function index(Game $game): Response
    {

        return $this->render('video/seeAlbum.html.twig',
        [
            'game'=>$game,
        ]
        );
    }
    #[Route('/ajouter-video/{id}', name: 'game_add_album_video', methods: ['POST', 'GET'])]
    public function addAlbumVideo(Request $request, Game $game): Response
    {
        $form = $this->createForm(AlbumVideoFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted()){
            $url = $form->get('name')->getData();
            if (!filter_var($url, FILTER_VALIDATE_URL)){
                $form->addError(new FormError('Url invalide'));
            }
            if ($form->isValid()){
                $url = explode("=", $url)[1];
                $url = explode("&", $url)[0];
                $video = new Video();
                $video->setGame($game)
                    ->setName($url);

                $this->getDoctrine()->getManager()->persist($video);
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', 'Vidéo ajoutée avec succès.');
                return $this->redirectToRoute('video_game_add_album_video', ['id'=>$game->getId()]);
            }


        }
        return $this->renderForm('game/add_album_video.html.twig', [
            'form' => $form,
        ]);
    }
}
