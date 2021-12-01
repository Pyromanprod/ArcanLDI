<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Game;
use App\Entity\Survey;
use App\Entity\User;
use App\Repository\AnswerRepository;
use App\Repository\OrderRepository;
use App\Repository\SurveyRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExtractAnswerController extends AbstractController
{
    #[Route('/extract/answer/{id}', name: 'extract_answer')]
    public function index(SurveyRepository $surveyRepository,
                          UserRepository $userRepository,
                          Game $game,
    AnswerRepository $answerRepository): Response
    {

        $listeTicket = $game->getTickets();
        $myVariableCSV = "\n\n\n######IMPORTER DANS GOOGLE SHEET POUR UN VRAI RENDU CHOISIR LA DETECTION PERSONNALISE ET ECRIRE   POINT VIRGULE    ######\n\n\n";
        foreach ($listeTicket as $ticket) {
            //Nom des colonnes en première lignes
            // le \n à la fin permets de faire un saut de ligne, super important en CSV
            // le point virgule sépare les données en colonnes
            $myVariableCSV .= "\n" . str_replace(';', ',', $game->getName()) . ";\n"; //nom du jeu
            $myVariableCSV .= str_replace(';', ',', $ticket->getName()) . ";\n"; //nom du ticket en cours d'extraction
            $myVariableCSV .= "Nom; Prénom; Mail;";
            $listeSurvey = $surveyRepository->findBySurveyByTicket($ticket); //liste des questionnaire associé au ticket
            foreach ($listeSurvey as $survey) {
                $listeQuestion = $survey->getQuestion();
                foreach ($listeQuestion as $key => $question) { //liste des question

                    $myVariableCSV .= $question->getContent() . ";"; //affichage de la question en cours d'extraction
                }
                $players = $userRepository->findplayer($ticket); //joueur ayant acheté le ticket
                foreach ($players as $player) {
                    $myVariableCSV .= "\n";
                    // un STR_REPLACE au cas ou les gens ponctu leur pseudo ou réponse avec un ; qui poserais problème lors de l'extraction
                    $myVariableCSV .= str_replace(';', ',', $player->getLastName()) . ";" .
                        str_replace(';', ',', $player->getFirstName()) . ";" .
                        str_replace(';', ',', $player->getEmail()) . ";";
                    foreach ($listeQuestion as $key => $question) {
                        $answer = $answerRepository
                            ->findByQuestionPlayer($question, $player);
                        $myVariableCSV .= str_replace(';', ',', $answer->getContent()) . ";";

                    }
                }
            }
        }
        return new Response(
            $myVariableCSV,
            200,
            [
                //Définit le contenu de la requête en tant que fichier Excel
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Encoding' => 'UTF-8',
                //On indique que le fichier sera en attachment donc ouverture de boite de téléchargement ainsi que le nom du fichier
                "Content-disposition" => "attachment; filename=" . $game->getSlug() . "_Reponse.csv"
            ]
        );
    }

    #[Route('/extract/player/{id}', name: 'extract_player')]
    public function extractPlayer(Game $game, UserRepository $userRepository, OrderRepository $orderRepository): Response
    {
        $players = $userRepository->findPlayersByGame($game);

        $myVariableCSV = "\n\n\n######IMPORTER DANS GOOGLE SHEET POUR UN VRAI RENDU CHOISIR LA DETECTION PERSONNALISE ET ECRIRE   POINT VIRGULE    ######\n\n\n";
        $myVariableCSV .= "Nom; Prénom; Mail; Numéro de ticket;";
        foreach ($players as $player) {
            $order = $orderRepository->findOneorder($game, $player);
            $myVariableCSV .= ",\n" . str_replace(';', ',', $player->getLastname()) . ',' . str_replace(';', ',', $player->getFirstName()) . ',' . str_replace(';', ',', $player->getEmail()) . ',' . $order->getTicket()->getId() . $game->getId() . $player->getId();
        }
        return new Response(
            $myVariableCSV,
            200,
            [
                //Définit le contenu de la requête en tant que fichier Excel
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Encoding' => 'UTF-8',
                //On indique que le fichier sera en attachment donc ouverture de boite de téléchargement ainsi que le nom du fichier
                "Content-disposition" => "attachment; filename=" . $game->getSlug() . "_Joueurs.csv"
            ]
        );
    }
}
