<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Choice;
use App\Entity\Order;
use App\Entity\Question;
use App\Entity\Survey;
use App\Form\AnswerMultipleFormType;
use App\Form\ChoiceFormType;
use App\Form\IsCgvFormType;
use App\Form\QuestionFormType;
use App\Form\SurveyFormType;
use App\Repository\AnswerRepository;
use App\Repository\ChoiceRepository;
use App\Repository\QuestionRepository;
use App\Repository\SurveyRepository;
use App\Repository\SurveyTicketRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    public function index(Request $request,EntityManagerInterface $entityManager,SurveyRepository $surveyRepository): Response
    {
        //repository des survey pour aller les chercher en BDD et les afficher
        $listeSurvey = $surveyRepository->findAll();

        //Instenciation d'un nouveau survey
        $newSurvey = new Survey();
        $form = $this->createForm(SurveyFormType::class, $newSurvey); //nouveau formulaire qui remplira les $newsurvey
        $form->handleRequest($request); //avec la "request"


        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($newSurvey); //on noutri l'entity manager
            $entityManager->flush(); // on envoie en BDD
            $this->addFlash('success', 'Questionnaire ajouté');
            //redirection vers a création des question
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
    public function addQuestion(Request $request, Survey $survey,EntityManagerInterface $entityManager): Response
    {

        $newQuestion = new Question();
        $newQuestion->setSurvey($survey);
        $form = $this->createForm(QuestionFormType::class, $newQuestion);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($newQuestion);
            $entityManager->flush();
            $this->addFlash('success', 'Question ajoutée');
            //si liste déroulante coché
            if ($form->get('select')->getData()) {

                //redirection vers cration de choix
                return $this->redirectToRoute('survey_add_choice', [
                    'id' => $newQuestion->getId(),
                ]);

            } else { //sinon

                //on vide le formulaire
                unset($newQuestion);
                unset($form);
                //on ré instancie le tous
                $newQuestion = new Question();
                $form = $this->createForm(QuestionFormType::class, $newQuestion);

            }
        }
        return $this->renderForm('survey/add_question.html.twig', [
            'form' => $form,
            'survey' => $survey
        ]);

    }

    #[Route('/ajouter-choix/{id}', name: 'add_choice')]
    #[isGranted('ROLE_ADMIN')]
    public function addChoice(Request $request, Question $question,EntityManagerInterface $entityManager): Response
    {
        $newChoice = new Choice();
        $form = $this->createForm(ChoiceFormType::class, $newChoice);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $newChoice->setQuestion($question);
            $entityManager->persist($newChoice);
            $entityManager->flush();
            $this->addFlash('success',
                $newChoice->getContent() . " ajouté à la question : " . $question->getContent());

            //pour vider un formulaire on peut aussi utilisé l'option de "redirecttoroute"
            return $this->redirectToRoute('survey_add_choice', ['id' => $question->getId()]);

        }
        return $this->renderForm('survey/add_choice_question.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route('/supprimer-choix/{id}/', name: 'delete_choice')]
    #[isGranted('ROLE_ADMIN')]
    public function deleteChoice(Request $request, Choice $choice,EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid($choice->getId() . "delete", $request->get('csrf_token'))) {
            $this->addFlash('success', 'Suppression réussie');
            $entityManager->remove($choice);
            $entityManager->flush();
        } else {

            $this->addFlash('error', 'Echec de la suppression.');
        }

        return $this->redirectToRoute('survey_add_choice', ['id' => $choice->getQuestion()->getId()]);


    }

    #[Route('/voir-questionnaire/{id}', name: 'view')]
    #[isGranted('ROLE_ADMIN')]
    public function viewSurvey(Request $request, Survey $survey): Response
    {
        return $this->render('survey/view_survey.html.twig', [
            'survey' => $survey,
        ]);
    }

    #[Route('/accepter-les-cgv/{id}', name: 'is_cgv', requirements: ['id'=>'\d+'])]
    #[isGranted('ROLE_USER')]
    public function accepte_cgv(Request $request, EntityManagerInterface $em, Order $order): Response
    {

        if($order->getPlayer() !== $this->getUser()){
            throw new AccessDeniedHttpException();
        }

        $form = $this->createForm(IsCgvFormType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $form->get('is_cgv')->getData()) {
            $order->setIsCgv(true);
            $em->flush();
            return $this->redirectToRoute('survey_suvey_for_ticket', ['id' => $order->getId()]);
        }
        return $this->renderForm('survey/accepte_cgv.html.twig', [
            'form' => $form,
            'order' => $order,

        ]);
    }

    #[Route('/checksurvey/{id}', name: 'suvey_for_ticket')]
    #[isGranted('ROLE_USER')]
    public function surveyForTicket(Request                $request,
                                    Order                  $order,
                                    SurveyTicketRepository $surveyTicketRepository,
                                    AnswerRepository       $answerRepository,
                                    QuestionRepository     $questionRepository,
    ): Response
    {

        //L'order passer dans l'url correspond bien au user connecté
        if($order->getPlayer() !== $this->getUser()){
            throw new AccessDeniedHttpException();
        }
        //si les CGV ne sont pas accépter on accéde pas au formulaire

        if (!$order->getIsCgv()) {

            return $this->redirectToRoute('survey_is_cgv', ['id' => $order->getId()]);
        }

        // Si c'est accepter on lance l'algo de vérif formulaire
        $ticket = $order->getTicket();
        $user = $this->getUser();
        $listeSurveyTicket = $surveyTicketRepository->findOrdered($ticket);

        foreach ($listeSurveyTicket as $surveyTicket) {

            //un findBYPersonalisé qui emméne les question du survey dans l'ordre décidé par l'admin
            $listeQuestion = $questionRepository->findOrderBy($surveyTicket->getSurvey());

            foreach ($listeQuestion as $question) {
                //si le joueur n'a pas encore répondu a cette question
                if (!$answerRepository->findByUserQuestion($user, $question)) {
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

        if ($order->getTicket()->getGame()->getIsPublished()) {

            return $this->redirectToRoute('checkout', ['id' => $order->getId()]);
        } else {
            $this->addFlash('success', 'Merci d\'avoir testé le questionnaire.');
            return $this->redirectToRoute('home');
        }
    }

    #[Route('/question/{id}/{idOrder}/{hash}', name: 'answer')]
    #[ParamConverter('order', options: ['mapping' => ['idOrder' => 'id']])]
    public function answer(AnswerRepository $answerRepository, Request $request, Question $question, Order $order, $hash): Response
    {

        if($order->getPlayer() !== $this->getUser()){
            throw new AccessDeniedHttpException();
        }

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

        // si l'utilisateur a déjà répondu a cette question
        //(peut arriver si il a cliqué sur précédent)
        if ($answerExistant = $answerRepository->findByQuestionPlayer($question, $this->getUser())) { //bug php storm
            $answer = $answerExistant;
        } else {
            $answer = new Answer();
        }

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager = $this->getDoctrine()->getManager();

            //si réponse facultative et pas de réponse on rempli le champs par une chaine vide
            if ($question->getOptional() && !$form->get('content')->getData()) {
                $answer->setQuestion($question)
                    ->setContent('')
                    ->setPlayer($this->getUser()); //bug phpstorm
            } else {
                //si la reponse est obligatoire et que
                // la réponse vide
                // on renvoie sur la même question avec un message d'erreur
                if (!$form->get('content')->getData()) {
                    $this->addFlash('error', 'Réponse obligatoire');
                    return $this->redirectToRoute('survey_suvey_for_ticket', [
                        'id' => $order->getId(),
                    ], Response::HTTP_SEE_OTHER);

                }
                //Si tout est ok on rempli avec la réponse de l'utilisateur
                $answer->setQuestion($question)
                    ->setContent($form->get('content')->getData())
                    ->setPlayer($this->getUser()); //bug phpstorm
            }

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


    #[Route('/orderedquestion/{id}', name: 'ordered')]
    public function orderedQuestion(Request                $request,
                                    QuestionRepository     $questionRepository,
                                    EntityManagerInterface $em,
                                    Survey                 $survey): Response
    {


        foreach ($request->request->get('question') as $key => $reponse) {
            if (!is_numeric($reponse)) {
                $this->addFlash('error', 'L\'ordre doit être un chiffre.');
                return $this->redirectToRoute('survey_view', ['id' => $survey->getId()]);
            }
            $question = $questionRepository->findOneById($key);
            $question->setOrderBy($reponse);
            $em->persist($question);

        }
        $em->flush();
        return $this->redirectToRoute('survey_view', ['id' => $survey->getId()]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST', 'GET'])]
    public function delete(Request $request, Survey $survey, EntityManagerInterface $entityManager): Response
    {

        if ($this->isCsrfTokenValid('delete' . $survey->getId(), $request->request->get('csrf_token'))) {
            $entityManager->remove($survey);
            $entityManager->flush();
        }

        return $this->redirectToRoute('survey_index', [], Response::HTTP_SEE_OTHER);
    }

}
