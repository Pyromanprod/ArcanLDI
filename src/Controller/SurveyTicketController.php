<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Repository\SurveyTicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
#[Route('/survey-ticket/', name: 'survey_ticket_')]
class SurveyTicketController extends AbstractController
{
    #[Route('ordered/{id}', name: 'ordered')]
    public function index(Request $request,
                          SurveyTicketRepository $surveyTicketRepository,
                          EntityManagerInterface $em,
                          Ticket $ticket): Response
    {

        //if form is submitted
        if ($request->request->get('surveyTicket')){

            foreach ($request->request->get('surveyTicket') as $key => $reponse) {
                if (!is_numeric($reponse)){
                    $this->addFlash('error', 'L\'ordre doit etre un chiffre');
                    return $this->redirectToRoute('survey_ticket_ordered', ['id' =>$ticket->getId()]);
                }
                $surveyTicket = $surveyTicketRepository->findOneById($key);
                $surveyTicket->setOrderBy($reponse);
                $em->persist($surveyTicket);

            }
            $em->flush();
        }
        return $this->render('survey_ticket/orderSurveyForTicket.html.twig',['ticket'=>$ticket]);
    }
}
