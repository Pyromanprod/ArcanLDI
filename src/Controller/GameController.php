<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\GameComment;
use App\Entity\RoleGroupe;
use App\Form\GameCommentType;
use App\Form\GameType;
use App\Repository\AnswerRepository;
use App\Repository\GameCommentRepository;
use App\Repository\GameRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Service\uploadGamePhoto;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/nos-jeux')]
class GameController extends AbstractController
{
    #[Route('/', name: 'game_index', methods: ['GET'])]
    public function index(GameRepository $gameRepository): Response
    {
        return $this->render('game/index.html.twig', [
            'allGames' => $gameRepository->findBy(['isPublished' => 1], ['dateStart' => 'DESC']),
        ]);
    }

    #[Route('/admin-jeu', name: 'admin_jeu', methods: ['GET'])]
    #[isGranted('ROLE_ADMIN')]
    public function adminJeu(GameRepository $gameRepository): Response
    {
        return $this->render('game/index.html.twig', [
            'allGames' => $gameRepository->findBy([], ['dateStart' => 'DESC']),
        ]);
    }

    #[Route('/publier-jeu/{id}/', name: 'publish_game', methods: ['GET'])]
    #[isGranted('ROLE_ADMIN')]
    public function publishGame(Request                $request,
                                Game                   $game,
                                AnswerRepository       $answerRepository,
                                GameRepository         $gameRepository,
                                OrderRepository        $orderRepository,
                                EntityManagerInterface $em): Response
    {
        $csrf = $request->get('csrf_token');

        if ($this->isCsrfTokenValid('publish' . $game->getId(), $csrf)) {

            foreach ($orderRepository->findByGame($game) as $order){
                $em->remove($order);
            }


            foreach ($answerRepository->findByGame($game) as $answer) {

                $em->remove($answer);
            }
//            dd($lesAnswer);
            $game->setIsPublished(true);
            $em->flush();
            $this->addFlash('success', $game->getName() . ' publié avec succès');
        } else {
            $this->addFlash('error', $game->getName() . ' n\'a pas été publié (token invalide)');
        }

        return $this->render('game/index.html.twig', [
            'allGames' => $gameRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'game_new', methods: ['GET', 'POST'])]
    #[isGranted('ROLE_ADMIN')]
    public function new(Request $request, uploadGamePhoto $uploadGamePhoto): Response
    {
        $game = new Game();
        $role = new RoleGroupe();
        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $request->get('dateStart') <= $request->get('dateEnd')) {
            if ($form->get('dateStart')->getData() <= $form->get('dateEnd')->getData()) {

                $entityManager = $this->getDoctrine()->getManager();
                //récupération de la photo si il y a
                $photo = $form->get('banner')->getData();
                if ($photo) {
                    $game->setBanner($uploadGamePhoto->uploadBanner($photo, $game));
                }
                $game->setIsPublished(false);
                $entityManager->persist($game);
                $role->setGame($game)
                    ->setName('public');
                $entityManager->persist($role);
                $entityManager->flush();
                return $this->redirectToRoute('survey_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('error', 'La date de fin ne peut pas être antérieur à la date de début de l\'évènement.');
            }
        }

        return $this->renderForm('game/new.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }

    #[Route('/{slug}', name: 'game_show', methods: ['GET', 'POST'])]
    public function show(Game $game, GameCommentRepository $commentRepository, Request $request, RateLimiterFactory $commentsLimiter, GameRepository $gameRepository, UserRepository $userRepository, PaginatorInterface $paginator): Response
    {
        $form = null;
        $gameComment = new GameComment();
        //déclaration du limiteur de commentaire a 3/h voir ratelimiter.yaml pour modifier
        $limiter = $commentsLimiter->create($request->getClientIp());
        $requestedPage = $request->query->getInt('page', 1);
        if ($requestedPage < 1) {
            throw new NotFoundHttpException();
        }
        $comment = $paginator->paginate(
            $commentRepository->findByGame($game, ['createdAt' => 'DESC']),
            $requestedPage,
            50
        );
        if ($userRepository->findPlayerByGame($game, $this->getUser())) {
            $form = $this->createForm(GameCommentType::class, $gameComment);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                if (!$gameRepository->findPlayerGame($game, $this->getUser())) {
                    $this->addFlash('error', 'Vous ne pouvez pas commenter un jeu auquel vous n\'avez pas participé.');
                    return $this->redirectToRoute('game_show', [
                        'slug' => $game->getSlug(),
                        'form' => $form,
                        'comment' => $comment,
                        'game' => $game,
                    ], Response::HTTP_SEE_OTHER);
                }
                // 1 token consumer par action si plus de token (3/h) throw exception
                if (false === $limiter->consume(1)->isAccepted()) {
                    throw new TooManyRequestsHttpException();
                }
                $gameComment
                    ->setAuthor($this->getUser())
                    ->setGame($game);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($gameComment);
                $entityManager->flush();

                return $this->redirectToRoute('game_show', [
                    'slug' => $game->getSlug(),
                ], Response::HTTP_SEE_OTHER);

            }
        }

        return $this->renderForm('game/show.html.twig', [
            'form' => $form,
            'comments' => $comment,
            'game' => $game,
        ]);
    }

    #[Route('/{id}/edit', name: 'game_edit', methods: ['GET', 'POST'])]
    #[isGranted('ROLE_ADMIN')]
    public function edit(Request $request, Game $game, uploadGamePhoto $uploadGamePhoto): Response
    {

        $form = $this->createForm(GameType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //récupération de la photo si il y a
            $photo = $form->get('banner')->getData();
            if ($photo) {
                //utilisation du service pour l'upload de bannière
                $game->setBanner($uploadGamePhoto->uploadBanner($photo, $game));
            }
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('game_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('game/edit.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/', name: 'game_delete', methods: ['POST'])]
    #[isGranted('ROLE_ADMIN')]
    public function delete(Game $game, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete' . $game->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($game);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_jeu', [], Response::HTTP_SEE_OTHER);
    }

}
