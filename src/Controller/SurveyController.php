<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Choice;
use App\Entity\Question;
use App\Entity\Survey;
use App\Entity\Ticket;
use App\Form\AnswerMultipleFormType;
use App\Form\ChoiceFormType;
use App\Form\QuestionFormType;
use App\Form\SurveyFormType;
use App\Repository\ChoiceRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/questionnaire', name: 'survey_')]
class SurveyController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
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
            $this->addFlash('success', 'Questionnaire Ajouté');
            return $this->redirectToRoute('survey_add_question', [
                'id' => $newSurvey->getId()
            ]);


        }

        return $this->renderForm('survey/index.html.twig', [
            'listeSurvey' => $listeSurvey,
            'form' => $form
        ]);
    }
    #[IsGranted('ROLE_ADMIN')]
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
            $this->addFlash('success', 'Question Ajoutée');
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
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/ajouter-choix/{id}', name: 'add_choice')]
    public function addChoice(Request $request, Question $question): Response
    {
        $newChoice = new Choice();
        $form = $this->createForm(ChoiceFormType::class, $newChoice);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $newChoice->setQuestion($question);
            $em->persist($newChoice);
            $em->flush();
            $this->addFlash('success',
                $newChoice->getContent() . " ajouté à la question : " . $question->getContent());
        }
        return $this->renderForm('survey/add_choice_question.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/voir-questionnaire/{id}', name: 'view')]
    public function viewSurvey(Request $request, Survey $survey): Response
    {
        return $this->render('survey/view_survey.html.twig', [
            'survey' => $survey,
        ]);
    }
    #[IsGranted('ROLE_USER')]
    #[Route('/questionnaire-pour-le-ticket/{id}', name: 'suvey_for_ticket')]
    public function surveyForTicket(Request $request, Ticket $ticket): Response
    {
        $user = $this->getUser();
        $listeSurvey = $ticket->getSurveys();
        $reposAnswer = $this->getDoctrine()->getRepository(Answer::class);
        foreach ($listeSurvey as $survey) {
            $listeQuestion = $survey->getQuestion();
            foreach ($listeQuestion as $question) {
                if (!$reposAnswer->findByUserQuestion($user, $question)) {

                    if ($question->getChoices()->getValues()) {

                        return $this->redirectToRoute('survey_answer_multiple_choice',
                            [
                                'id' => $question->getId(),
                                'hash' => hash('md5',
                                    $question->getContent() .
                                    $question->getId() .
                                    $this->getUser()->getId() .
                                    $ticket->getId()
                                ),
                                'idTicket' => $ticket->getId(),
                            ]);
                    }

                    //si question a champs input
                    return $this->redirectToRoute('survey_answer',
                        [
                            'id' => $question->getId(),
                            'hash' => hash('md5',
                                $question->getContent() .
                                $question->getId() .
                                $this->getUser()->getId() .
                                $ticket->getId()
                            ),
                            'idTicket' => $ticket->getId(),
                        ]);
                }
            }

        }
        //TODO : rediriger vers paiement
        return $this->redirectToRoute('home');
    }

//question choix multiple
    #[IsGranted('ROLE_USER')]
    #[Route('/question/{id}/{idTicket}/{hash}/multiple', name: 'answer_multiple_choice')]
    #[ParamConverter('ticket', options: ['mapping' => ['idTicket' => 'id']])]
    public function answer(Request $request, Question $question, Ticket $ticket, $hash): Response
    {
        //Si tu arrive sur la page sans y avoir était invité
        if (!hash_equals($hash, hash('md5',
            $question->getContent() .
            $question->getId() .
            $this->getUser()->getId() .
            $ticket->getId()

        ))) {
            throw new AccessDeniedHttpException();
        }

        $answer = new Answer();
        $form = $this->createFormBuilder()
            ->add('content', EntityType::class, [
                'class' => 'App\Entity\Choice',
                'choice_label' => 'content',
                'query_builder' => function (ChoiceRepository $tr) use ($question) {
                    return $tr->createQueryBuilder('u')
                        ->where('u.question = :question')
                        ->setParameter('question', $question);
                },
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $answer->setQuestion($question)
                ->setContent($form->get('content')->getData()->getContent())
                ->setPlayer($this->getUser()); //bug phpstorm
            $entityManager->persist($answer);
            $entityManager->flush();
            return $this->redirectToRoute('survey_suvey_for_ticket', [
                'id' => $ticket->getId(),
            ], Response::HTTP_SEE_OTHER);

        }
        return $this->renderForm('question/repondre.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

//Question a choix libre
    #[IsGranted('ROLE_USER')]
    #[Route('/question/{id}/{idTicket}/{hash}', name: 'answer')]
    #[ParamConverter('ticket', options: ['mapping' => ['idTicket' => 'id']])]
    public function answerSingle(Request $request, Question $question, Ticket $ticket, $hash): Response
    {

        //Si tu arrive sur la page sans y avoir était invité
        if (!hash_equals($hash, hash('md5',
            $question->getContent() .
            $question->getId() .
            $this->getUser()->getId() .
            $ticket->getId()

        ))) {
            throw new AccessDeniedHttpException();
        }

        $answer = new Answer();
        $form = $this->createForm(AnswerMultipleFormType::class, $answer);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $answer->setQuestion($question)
                ->setPlayer($this->getUser()); //bug phpstorm

            $entityManager->persist($answer);
            $entityManager->flush();
            return $this->redirectToRoute('survey_suvey_for_ticket', [
                'id' => $ticket->getId(),
            ], Response::HTTP_SEE_OTHER);
        }
        return $this->renderForm('question/repondre.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }
    //TODO: au moment de l'envoie de réponse vérifier si la question répondu et bien la bonne


}
