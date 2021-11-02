<?php

namespace App\Controller;

use App\Entity\Game;
use App\Repository\GameRepository;
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
        $allGames = $repos->findAll();
        return $this->render('main/index.html.twig',
            [
                'allGames' => $allGames,
            ]
        );
    }

    #[Route('/admin', name: 'admin_home')]
    #[isGranted('ROLE_ADMIN')]
    public function admin(): Response
    {
        return $this->render('admin/index.html.twig');
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
