<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\News;
use App\Repository\GameRepository;
use App\Repository\OrderRepository;
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
        $allGames = $repos->findLastThree();
        $news = $reposnews->findLastThree();
        return $this->render('main/index.html.twig',
            [
                'news'=> $news,
                'allGames' => $allGames,
            ]
        );
    }

    #[Route('/admin', name: 'admin_home')]
    #[isGranted('ROLE_ADMIN')]
    public function admin(OrderRepository $orderRepository): Response
    {
       $order =  $orderRepository->findRefundRequestedOrder();

        return $this->render('admin/index.html.twig',[
            'requestedRefund' => $order,
        ]);
    }

    #[Route('search', name: 'search')]
    public function search(GameRepository $gameRepository, Request $request): Response
    {
        $allGames = $gameRepository->search($request->query->get('q'));
        return $this->render("game/index.html.twig", [
            'allGames' => $allGames
        ]);
    }
}
