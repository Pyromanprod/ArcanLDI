<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Choice;
use App\Entity\Order;
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
    #[Route('/', name: 'index')]
    #[isGranted('ROLE_ADMIN')]
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

    #[Route('/ajouter-question/{id}', name: 'add_question')]
    #[isGranted('ROLE_ADMIN')]
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

    #[Route('/ajouter-choix/{id}', name: 'add_choice')]
    #[isGranted('ROLE_ADMIN')]
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

    #[Route('/voir-questionnaire/{id}', name: 'view')]
    #[isGranted('ROLE_ADMIN')]
    public function viewSurvey(Request $request, Survey $survey): Response
    {
        return $this->render('survey/view_survey.html.twig', [
            'survey' => $survey,
        ]);
    }

    #[Route('/checksurvey/{id}', name: 'suvey_for_ticket')]
    public function surveyForTicket(Request $request, Order $order): Response
    {
        $ticket = $order->getTicket();

        $user = $this->getUser();
        $listeSurvey = $ticket->getSurveys();
        $reposAnswer = $this->getDoctrine()->getRepository(Answer::class);
        foreach ($listeSurvey as $survey) {
            $listeQuestion = $survey->getQuestion();
            foreach ($listeQuestion as $question) {
                if (!$reposAnswer->findByUserQuestion($user, $question)) {
                // envoie d'un hash composer de
                    // l'id de la question
                    //l'id du user
                    //le contenue de la question
                    //et l'id du ticket
                    // pour pouvoir vérifier par la suite si le user a trafiqué l'url
                    return $this->redirectToRoute('survey_answer',
                        [
                            'id' => $question->getId(),
                            'hash' => hash('md5',
                                $question->getContent() .
                                $question->getId() .
                                $this->getUser()->getId() .
                                $ticket->getId()
                            ),
                            'idOrder' => $order->getId(),
                        ]);
                }
            }

        }
    //si toutes les questions sont répondu on redirige vers le paiement
        return $this->redirectToRoute('checkout', ['id'=>$order->getId()]);
        //TODO: pensé a faire une vérif des orders sans paiement de plus de 7 jours (delete order + answer etc...)
    }

    #[Route('/question/{id}/{idOrder}/{hash}', name: 'answer')]
    #[ParamConverter('order', options: ['mapping' => ['idOrder' => 'id']])]
    public function answer(Request $request, Question $question, Order $order, $hash): Response
    {
        
        $ticket = $order->getTicket();
        //Vérification du hash envoyé et comparaison pour savoir si l'url a était trafiqué
        //si oui on envoie sur access denied
        if (!hash_equals($hash, hash('md5',
                $question->getContent() .
                $question->getId() .
                $this->getUser()->getId() .
                $ticket->getId()

            )
        )) {
            throw new AccessDeniedHttpException();
        }

        $answer = new Answer();
        if ($question->getChoices()->getValues()) {

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
        } else {
            $form = $this->createForm(AnswerMultipleFormType::class);
        }


        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager = $this->getDoctrine()->getManager();
            $answer->setQuestion($question)
                ->setContent($form->get('content')->getData())
                ->setPlayer($this->getUser()); //bug phpstorm

            $entityManager->persist($answer);
            $entityManager->flush();
            return $this->redirectToRoute('survey_suvey_for_ticket', [
                'id' => $order->getId(),
            ], Response::HTTP_SEE_OTHER);
        }
        return $this->renderForm('question/repondre.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }


}
