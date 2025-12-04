<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Entity\House;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Override;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    #[Route('/admin', name: 'admin')]
    #[Override]
    public function index(): Response
    {
        $url = $this->adminUrlGenerator
            ->setController(HouseCrudController::class)
            ->generateUrl();

        return $this->redirect($url);
    }

    #[Override]
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Бронирование Домов')
            ->setFaviconPath('favicon.ico');
    }

    #[Override]
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Главная', 'fa fa-home');

        yield MenuItem::section('Справочники');
        yield MenuItem::linkToCrud('Дома', 'fas fa-house-user', House::class);
        yield MenuItem::linkToCrud('Пользователи', 'fas fa-users', User::class);

        yield MenuItem::section('Операции');
        yield MenuItem::linkToCrud('Бронирования', 'fas fa-calendar-check', Booking::class);

        yield MenuItem::section();
        yield MenuItem::linkToLogout('Выход', 'fa fa-sign-out');
    }
}
