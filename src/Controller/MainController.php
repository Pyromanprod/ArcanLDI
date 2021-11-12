<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\News;
use App\Entity\Order;
use App\Entity\Presentation;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        $repos = $this->getDoctrine()->getRepository(Game::class);
        $reposnews = $this->getDoctrine()->getRepository(News::class);
        $presentationRepository = $this->getDoctrine()->getRepository(Presentation::class);
        $order = $this->getDoctrine()->getRepository(Order::class);
        $orders = $order->findRefundRequestedOrder();
        $allGames = $repos->findLastThree();
        $news = $reposnews->findLastThree();
        $presentation = $presentationRepository->findOneBy([], ['id'=>'DESC']);
        return $this->render('main/index.html.twig',
            [
                'requestedRefund' => $orders,
                'news' => $news,
                'allGames' => $allGames,
                'presentation'=>$presentation,
            ]
        );
    }


    #[Route('search', name: 'search')]
    public function search(GameRepository $gameRepository, Request $request): Response
    {
        $allGames = $gameRepository->search($request->query->get('q'));
        return $this->render("game/index.html.twig", [
            'allGames' => $allGames
        ]);
    }

    #[Route('clear', name: 'game_clear', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function clear(GameRepository $gameRepository, EntityManagerInterface $entityManager): Response
    {
        $games = $gameRepository->findOneOutdatedGame(new \DateTime());
        foreach ($games as $game) {
            foreach ($game->getRoleGroupes() as $role) {
                $entityManager->remove($role);
            }
        }
        $entityManager->flush();
        return $this->redirectToRoute('admin_jeu', [], Response::HTTP_SEE_OTHER);
    }
}
