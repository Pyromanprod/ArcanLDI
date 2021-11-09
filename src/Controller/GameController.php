<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\GameComment;
use App\Entity\Picture;
use App\Entity\Video;
use App\Form\AlbumPhotoFormType;
use App\Form\AlbumVideoFormType;
use App\Form\GameCommentType;
use App\Form\GameType;
use App\Repository\GameCommentRepository;
use App\Repository\GameRepository;
use App\Service\uploadGamePhoto;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/nos-jeux')]
class GameController extends AbstractController
{
    #[Route('/', name: 'game_index', methods: ['GET'])]
    public function index(GameRepository $gameRepository): Response
    {
        return $this->render('game/index.html.twig', [
            'allGames' => $gameRepository->findByIsPublished(1),
        ]);
    }

    #[Route('/admin-jeu', name: 'admin_jeu', methods: ['GET'])]
    #[isGranted('ROLE_ADMIN')]
    public function adminJeu(GameRepository $gameRepository): Response
    {
        return $this->render('game/index.html.twig', [
            'allGames' => $gameRepository->findAll(),
        ]);
    }

    #[Route('/publier-jeu/{id}/', name: 'publish_game', methods: ['GET'])]
    #[isGranted('ROLE_ADMIN')]
    public function publishGame(Request $request, Game $game, GameRepository $gameRepository, EntityManagerInterface $em): Response
    {
        $csrf = $request->get('csrf_token');

        if ($this->isCsrfTokenValid('publish' . $game->getId(), $csrf)) {
            $game->setIsPublished(true);
            $em->flush();
            $this->addFlash('success', $game->getName() . ' publié avec succés');
        } else {
            $this->addFlash('error', $game->getName() . ' n\'as pas été publier (token invalide)');
        }

        return $this->render('game/index.html.twig', [
            'allGames' => $gameRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'game_new', methods: ['GET', 'POST'])]
    #[isGranted('ROLE_ADMIN')]
    public function new(Request $request, uploadGamePhoto $uploadGamePhoto): Response
    {
        $game = new Game();
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            //récupération de la photo si il y a
            $photo = $form->get('banner')->getData();
            if ($photo) {
                $game->setBanner($uploadGamePhoto->uploadBanner($photo, $game));
            }
            $game->setIsPublished(false);
            $entityManager->persist($game);
            $entityManager->flush();
            return $this->redirectToRoute('admin_jeu', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('game/new.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }

    #[Route('/{slug}', name: 'game_show', methods: ['GET', 'POST'])]
    public function show(Game $game, GameCommentRepository $commentRepository, Request $request, RateLimiterFactory $anonymousApiLimiter, GameRepository $gameRepository): Response
    {
        $gameComment = new GameComment();
        $form = $this->createForm(GameCommentType::class, $gameComment);
        $form->handleRequest($request);
        $limiter = $anonymousApiLimiter->create($request->getClientIp());
        $comment = $commentRepository->findByGame($game);

        if ($form->isSubmitted() && $form->isValid() ) {
            if (!$gameRepository->findPlayerGame($game, $this->getUser())){
                $this->addFlash('error','vous ne pouvez pas écrire un commentaire pour un jeu ou vous n\'avez pas participé');
                return $this->redirectToRoute('game_show', [
                    'slug'=> $game->getSlug(),
                    'form' => $form,
                    'comment' => $comment,
                    'game' => $game,
                ], Response::HTTP_SEE_OTHER);
            }
            if (false === $limiter->consume(1)->isAccepted()) {
                throw new TooManyRequestsHttpException();
            }
            $gameComment
                ->setAuthor($this->getUser())
                ->setGame($game);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($gameComment);
            $entityManager->flush();

            return $this->redirectToRoute('game_show', [
                'slug'=> $game->getSlug(),
                'form' => $form,
                'comment' => $comment,
                'game' => $game,
            ], Response::HTTP_SEE_OTHER);

        }
        return $this->renderForm('game/show.html.twig', [
            'form' => $form,
            'comment' => $comment,
            'game' => $game,
        ]);
    }

    #[Route('/{id}/edit', name: 'game_edit', methods: ['GET', 'POST'])]
    #[isGranted('ROLE_ADMIN')]
    public function edit(Request $request, Game $game, uploadGamePhoto $uploadGamePhoto): Response
    {

        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //récupération de la photo si il y a
            $photo = $form->get('banner')->getData();
            if ($photo) {
                //utilisation du service pour l'upload de bannière
                $game->setBanner($uploadGamePhoto->uploadBanner($photo, $game));
            }
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('game_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('game/edit.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/', name: 'game_delete', methods: ['POST'])]
    public function delete(Game $game, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete' . $game->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($game);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_jeu', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/ajouter-photo/{slug}', name: 'game_add_album_Photo', methods: ['POST', 'GET'])]
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

    #[Route('/ajouter-video/{slug}', name: 'game_add_album_video', methods: ['POST', 'GET'])]
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
