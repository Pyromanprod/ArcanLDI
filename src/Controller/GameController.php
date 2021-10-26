<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Picture;
use App\Entity\Video;
use App\Form\AlbumPhotoFormType;
use App\Form\AlbumVideoFormType;
use App\Form\GameType;
use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game')]
class GameController extends AbstractController
{
    #[Route('/', name: 'game_index', methods: ['GET'])]
    public function index(GameRepository $gameRepository): Response
    {
        return $this->render('game/index.html.twig', [
            'games' => $gameRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'game_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $game = new Game();
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

//            TODO: Factoriser envoie de photo
            // dossier du jeu dans le game.photo.directory
            $directory = $this->getParameter('game.photo.directory') . $game->getName() . '/';

            //récupération de la photo si il y a
            $photo = $form->get('banner')->getData();
            if ($photo) {
                //si le dossier n'exist pas
                if (!file_exists($directory)) {
                    //on le créer
                    mkdir($directory);
//                    die($directory);
                }

                $newFileName = 'banner' . '.' . $photo->guessExtension();


                // Déplacement de la photo dans le dossier que l'on avait paramétré dans le fichier services.yaml,
                // avec le nouveau nom qu'on lui a généré
                $photo->move(
                    $directory,     // Emplacement de sauvegarde du fichier
                    $newFileName    // Nouveau nom du fichier
                );
                $game->setBanner($newFileName);
            }

            $entityManager->persist($game);
            $entityManager->flush();

            return $this->redirectToRoute('game_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('game/new.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'game_show', methods: ['GET'])]
    public function show(Game $game): Response
    {
        return $this->render('game/show.html.twig', [
            'game' => $game,
        ]);
    }

    #[Route('/{id}/edit', name: 'game_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Game $game): Response
    {
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            //            TODO: Factoriser envoie de photo

            // dossier du jeu dans le game.photo.directory
            $directory = $this->getParameter('game.photo.directory') . $game->getName() . '/';

            //récupération de la photo si il y a
            $photo = $form->get('banner')->getData();
            if ($photo) {
                //si le dossier n'exist pas
                if (!file_exists($directory)) {
                    //on le créer
                    mkdir($directory);
//                    die($directory);
                }

                $newFileName = 'banner' . '.' . $photo->guessExtension();


                // Déplacement de la photo dans le dossier que l'on avait paramétré dans le fichier services.yaml,
                // avec le nouveau nom qu'on lui a généré
                $photo->move(
                    $directory,     // Emplacement de sauvegarde du fichier
                    $newFileName    // Nouveau nom du fichier
                );
                $game->setBanner($newFileName);
            }
            return $this->redirectToRoute('game_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('game/edit.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'game_delete', methods: ['POST'])]
    public function delete(Request $request, Game $game): Response
    {
        if ($this->isCsrfTokenValid('delete' . $game->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($game);
            $entityManager->flush();
        }

        return $this->redirectToRoute('game_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/ajouter-photo/{slug}', name: 'game_add_album_Photo',methods: ['POST', 'GET']) ]
    public function addAlbumPhoto(Request $request, Game $game): Response
    {
        $form = $this->createForm(AlbumPhotoFormType::class);
        $form->handleRequest($request);
        // dossier du jeu dans le game.photo.directory
        $directory = $this->getParameter('game.photo.directory') . $game->getName() . '/album_photo/';
        if ($form->isSubmitted()) {

            //            TODO: Factoriser envoie de photo

            // dossier du jeu dans le game.photo.directory
            $directory = $this->getParameter('game.photo.directory') . $game->getName() . '/album_photo/';
            //si le dossier n'exist pas
            if (!file_exists($directory)) {
                //on le créer
                mkdir($directory);
            }
            //récupération de la photo si il y a
            $photos = $form->get('photos')->getData();

            foreach ($photos as $photo) {
                //on assure l'unicité du nom
                do {
                    $nameFile = md5(uniqid()) . '.' . $photo->guessExtension();
                } while (file_exists($directory . $nameFile));
                //envoie des photos
                $photo->move($directory,
                    $nameFile
                );
                $picture = new Picture();
                $picture->setName($nameFile)
                ->setGame($game);

            }
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('game_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->renderForm('game/add_album_photo.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/ajouter-video/{slug}', name: 'game_add_album_video',methods: ['POST', 'GET']) ]
    public function addAlbumVideo(Request $request, Game $game): Response
    {
        $form = $this->createForm(AlbumVideoFormType::class);
        $form->handleRequest($request);
        // dossier du jeu dans le game.photo.directory
        $directory = $this->getParameter('game.photo.directory') . $game->getName() . '/album_photo/';
        if ($form->isSubmitted()) {

            // dossier du jeu dans le game.photo.directory
            $directory = $this->getParameter('game.photo.directory') . $game->getName() . '/album_video/';
            //si le dossier n'exist pas
            if (!file_exists($directory)) {
                //on le créer
                mkdir($directory);
            }
            //récupération de la photo si il y a
            $videos = $form->get('video')->getData();

            foreach ($videos as $video) {
                //on assure l'unicité du nom
                do {
                    $nameFile = md5(uniqid()) . '.' . $video->guessExtension();
                } while (file_exists($directory . $nameFile));
                //envoie des photos
                $video->move($directory,
                    $nameFile
                );
                $video = new Video();
                $video->setName($nameFile)
                    ->setGame($game);

            }
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('game_index', [], Response::HTTP_SEE_OTHER);
        }
        return $this->renderForm('game/add_album_video.html.twig', [
            'form' => $form,
        ]);
    }
}
