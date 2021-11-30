<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Picture;
use App\Form\AlbumPhotoFormType;
use App\Repository\GameRepository;
use App\Repository\PictureRepository;
use App\Service\uploadGamePhoto;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
    public function seeAlbum(Request $request, GameRepository $gameRepository, Game $game, PaginatorInterface $paginator, PictureRepository $pictureRepository): Response
    {
        $requestedPage = $request->query->getInt('page', 1);
        if ($requestedPage < 1) {
            throw new NotFoundHttpException();
        }
        $picture = $paginator->paginate(
            $pictureRepository->findByGame($game, ['createdAt' => 'DESC']),
            $requestedPage,
            16
        );

        $participated = false;
        if ($gameRepository->findPlayerGame($game, $this->getUser())) {
            $participated = true;
        }
        return $this->render('picture/seeAlbum.html.twig', [
            'pictures' => $picture,
            'game' => $game,
            'participated' => $participated,
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

    #[Route('/telecharger/{id}', name: 'download_album', methods: ['POST', 'GET'])]
    #[isGranted('ROLE_USER')]
    public function download(Request $request, GameRepository $gameRepository, Game $game): Response
    {

        // Redirect avec message d'erreur si le joueur n'a pas participé a ce jeu
        if (!$gameRepository->findPlayerGame($game, $this->getUser())) {
            $this->addFlash('error', 'Vous n\'avez pas participé à ce jeu.');
            return $this->redirectToRoute('see_album', ['slug' => $game->getSlug()]);
        }
        //nouvelle instence de la classe "ziparchive
        $zip = new \ZipArchive();
        //on nomme l'archive avec le slug du jeu
        $zipName = $game->getSlug() . '.zip';
        //récup du dossier contenant toute les photos
        $dossier = $this->getParameter('game.album.directory') . $game->getSlug();
        //on ouvre l'archive pour la remplir
        if ($zip->open($zipName, \ZipArchive::CREATE)) {
            $photos = $game->getPictures();
            //foreach sur toutes les photos de l'album pour les ajouter
            foreach ($photos as $photo) {
                $zip->addFile($dossier . '/' . $photo->getName(), $game->getSlug() . "/" . $photo->getName());
            }
            //on ferme le zip
            $zip->close();
            try { //try catch permettant de redirigé si il y a un probleme de création de la réponse (pas de photo par exemple)
                $content = file_get_contents($zipName);
                //on prépare la réponse qui créé phisiquement le zip
                $response = new Response();

                //set headers
                $response->headers->set('Content-Type', 'application/zip');
                $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipName);
                $response->setContent($content);
                //une fois le zip bien dans la réponse on le supprime pour ne pas le stocker sur le serveur a chaque téléchargement
                unlink($zipName);
                return $response;
            } catch (Exception $exception) {
                $this->addFlash('error', 'Erreur lors de la création de l\'archive');
                return $this->redirectToRoute('game_show', ['slug' => $game->getSlug()]);
            }

        }


        return $this->redirectToRoute('see_album', ['slug' => $game->getSlug()]);
    }
}
