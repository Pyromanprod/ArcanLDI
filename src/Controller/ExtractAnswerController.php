<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Game;
use App\Entity\Survey;
use App\Entity\User;
use App\Repository\AnswerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExtractAnswerController extends AbstractController
{
    #[Route('/extract/answer/{id}', name: 'extract_answer')]
    public function index(AnswerRepository $answerRepository, Game $game): Response
    {

//        dd($this->getDoctrine()->getRepository(Ticket::class)->findBySurveyTicket($game));
        $listeTicket = $game->getTickets();
        $myVariableCSV = "\n\n\n######IMPORTER DANS GOOGLE SHEET POUR UN VRAI RENDU CHOISIR LA DETECTION AUTOMATIQUE DU SEPARATEUR######\n\n\n";
        foreach ($listeTicket as $ticket) {
            //Nom des colonnes en première lignes
            // le \n à la fin permets de faire un saut de ligne, super important en CSV
            // le point virgule sépare les données en colonnes
            $myVariableCSV .= "\n".$game->getName() . ",\n";
            $myVariableCSV .= $ticket->getName() . ",\n";
            $myVariableCSV .= "Nom, Prénom, Mail,";
            $listeSurvey = $this->getDoctrine()->getRepository(Survey::class)->findBySurveyByTicket($ticket);
            foreach ($listeSurvey as $survey) {
                $listeQuestion = $survey->getQuestion();
                foreach ($listeQuestion as $key => $question) {


                    $myVariableCSV .= $question->getContent() . ",";
                }
                $players = $this->getDoctrine()->getRepository(User::class)->findplayer($ticket);
                foreach ($players as $player) {
                    $myVariableCSV .= "\n";
                    $myVariableCSV .= $player->getLastName() . "," .
                        $player->getFirstName() . "," .
                        $player->getEmail() . ",";
                    foreach ($listeQuestion as $key => $question) {
                        $answer = $this->getDoctrine()
                            ->getRepository(Answer::class)
                            ->findByQuestionPlayer($question, $player);
                        $myVariableCSV .= $answer->getContent() . ",";

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
                'Content-Encoding'=> 'UTF-8',
                //On indique que le fichier sera en attachment donc ouverture de boite de téléchargement ainsi que le nom du fichier
                "Content-disposition" => "attachment; filename=" . $game->getSlug() . "_Reponse.csv"
            ]
        );
//        dd($myVariableCSV);
    }
}
