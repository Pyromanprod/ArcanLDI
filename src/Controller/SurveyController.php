<?php

namespace App\Controller;

use App\Entity\Choice;
use App\Entity\Question;
use App\Entity\Survey;
use App\Form\ChoiceFormType;
use App\Form\QuestionFormType;
use App\Form\SurveyFormType;
use App\Repository\SurveyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/questionnaire', name: 'survey_')]
class SurveyController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $reposSurvey = $this->getDoctrine()->getRepository(Survey::class);
        $listeSurvey = $reposSurvey->findAll();

        $newSurvey = new Survey();
        $form = $this->createForm(SurveyFormType::class, $newSurvey);
        $form->handleRequest($request);
        $em = $this->getDoctrine()->getManager();
        if ($form->isSubmitted()) {
            $em->persist($newSurvey);
            $em->flush();


            return $this->redirectToRoute('survey_add_question', [
                'id' => $newSurvey->getId()
            ]);

        }

        return $this->renderForm('survey/index.html.twig', [
            'listeSurvey' => $listeSurvey,
            'form' => $form
        ]);
    }

    #[Route('/ajouter-question/{id}', name: 'add_question')]
    public function addQuestion(Request $request, Survey $survey): Response
    {

        $newQuestion = new Question();
        $newQuestion->setSurvey($survey);
        $form = $this->createForm(QuestionFormType::class, $newQuestion);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($newQuestion);
            $em->flush();
            //si liste déroulante coché
            if ($form->get('select')->getData()) {

                return $this->redirectToRoute('survey_add_choice', [
                    'id' => $newQuestion->getId(),
                ]);

            } else { //sinon

                unset($newQuestion);
                unset($form);
                $newQuestion = new Question();
                $form = $this->createForm(QuestionFormType::class, $newQuestion);
                return $this->renderForm('survey/add_question.html.twig', [
                    'form' => $form,
                    'survey' => $survey
                ]);
            }
        }
        return $this->renderForm('survey/add_question.html.twig', [
            'form' => $form,
            'survey' => $survey
        ]);

    }

    #[Route('/ajouter-choix/{id}', name: 'add_choice')]
    public function addChoice(Request $request, Question $question): Response
    {
        $newChoice = new Choice();
        $form = $this->createForm(ChoiceFormType::class, $newChoice);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            dump($question);
            $newChoice->setQuestion($question);
            $em->persist($newChoice);
            $em->flush();
        }
        return $this->renderForm('survey/add_choice_question.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route('/voir-questionnaire/{id}', name: 'view')]
    public function viewSurvey(Request $request, Survey $survey): Response
    {
        return $this->render('survey/view_survey.html.twig',[
            'survey'=>$survey,
            ]);
    }


}
