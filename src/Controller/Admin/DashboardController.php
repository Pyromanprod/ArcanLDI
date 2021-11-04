<?php

namespace App\Controller\Admin;

use App\Entity\RoleGroupe;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Game;
use App\Entity\User;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/admins", name="admin")
     */
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('ArcanLDI');
    }

    public function configureMenuItems(): iterable
    {
        return
            [
                MenuItem::linktoDashboard('Dashboard', 'fa fa-home'),
                MenuItem::section('user'),
                MenuItem::linkToCrud('User', 'fas fa-list', User::class),
                MenuItem::section('game'),
                MenuItem::linkToCrud('Game', 'fas fa-list', Game::class),
                MenuItem::linkToCrud('role','fas fa-list',RoleGroupe::class)
            ];
    }
}
