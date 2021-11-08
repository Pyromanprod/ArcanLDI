<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\Ticket;
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
        $directory = $this->controller->get('game.photo.directory'); //fail phpStorm

        //si le dossier game.photo.directory n'existe pas
        if (!file_exists($directory)) {
            //on le créer
            mkdir($directory);
        }
        //si le dossier game.photo.directory/slug-Du-Jeu n'existe pas
        $directory .= $game->getSlug() . '/';
        if (!file_exists($directory)) {
            mkdir($directory);
        }

        $newFileName = 'banner' . '.' . $photo->guessExtension();


        // Déplacement de la photo dans le dossier que l'on avait paramétré dans le fichier services.yaml,
        // avec le nouveau nom qu'on lui a généré
        $photo->move(
            $directory,     // Emplacement de sauvegarde du fichier
            $newFileName    // Nouveau nom du fichier
        );
        return $newFileName;
    }

    public function uploadCGVTicket(UploadedFile $cgv, Ticket $ticket)
    {
        // dossier du jeu dans le game.photo.directory
        $directory = $this->controller->get('game.cgv.directory'); //fail phpStorm

        //si le dossier game.photo.directory n'existe pas
        if (!file_exists($directory)) {
            //on le créer
            mkdir($directory);
        }
        //si le dossier game.photo.directory/slug-Du-Jeu n'existe pas
        $directory .= $ticket->getId() . '/';
        if (!file_exists($directory)) {
            mkdir($directory);
        }


        $cgv->move(
            $directory,     // Emplacement de sauvegarde du fichier
            $ticket->getCgv()
        );
    }
}