<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\Picture;
use App\Entity\Ticket;
use phpDocumentor\Reflection\Types\Array_;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class uploadGamePhoto
{
    private ParameterBagInterface $controller;

    public function __construct(ParameterBagInterface $controller)
    {
        $this->controller = $controller;
    }

    public function uploadBanner(UploadedFile $photo, Game $game): string
    {
        // dossier du jeu dans le game.photo.directory
        $directory = $this->controller->get('game.photo.directory'); //fail phpStorm parametre dans service.yaml

        //si le dossier game.photo.directory n'existe pas
        if (!file_exists($directory)) {
            //on le créer
            mkdir($directory);
        }
        do {

            $newFileName = md5($game->getName() . random_bytes(100)) . '.' . $photo->guessExtension();
        } while (file_exists($directory.$newFileName));


            // Déplacement de la photo dans le dossier que l'on avait paramétré dans le fichier services.yaml,
            // avec le nouveau nom qu'on lui a généré
            $photo->move(
                $directory,     // Emplacement de sauvegarde du fichier
                $newFileName    // Nouveau nom du fichier
            );
        return $newFileName;
    }
    public function uploadAlbum(Array $photos, Game $game): array
    {
        // dossier du jeu dans le game.photo.directory
        $directory = $this->controller->get('game.album.directory');

        //si le dossier game.photo.directory n'existe pas
        if (!file_exists($directory)) {
            //on le créer
            mkdir($directory);
        }
        // dossier du jeu dans le game.photo.directory
        $directory .= $game->getSlug();

        //si le dossier game.photo.directory n'existe pas
        if (!file_exists($directory)) {
            //on le créer
            mkdir($directory);
        }
        

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
            $listePicture[]= $picture;

        }

        return $listePicture;
    }
    public function uploadCGVTicket(UploadedFile $cgv, Ticket $ticket): String
    {
        // dossier du jeu dans le game.photo.directory
        $directory = $this->controller->get('game.cgv.directory'); //fail phpStorm

        //si le dossier game.photo.directory n'existe pas
        if (!file_exists($directory)) {
            //on le créer
            mkdir($directory);
        }

        do {

            $newFileName  = md5($ticket->getName() . random_bytes(100)) . '.pdf';
        } while (file_exists($directory.$newFileName));

        $cgv->move(
            $directory,     // Emplacement de sauvegarde du fichier
            $newFileName,
        );
        return $newFileName;
    }

}
