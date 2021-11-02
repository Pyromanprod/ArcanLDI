<?php

namespace App\Controller;

use App\Entity\Game;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
