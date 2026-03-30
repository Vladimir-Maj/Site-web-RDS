<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RoleEnum;
use App\Util;
use Twig\Environment;

class DashboardController extends BaseController
{
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    private function RolePermission(): void
    {
        if (!Util::isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        $role = Util::getRole();
        $isAdmin = ($role === RoleEnum::Admin);
        $isPilote = ($role === RoleEnum::Pilote);

        if (!$isAdmin && !$isPilote) {
            http_response_code(403);
            die('Acces refuse.');
        }
    }

    public function index(): void
    {
        $this->RolePermission();

        echo $this->twig->render('dashboard/index.html.twig', [
            'currentPage' => 'dashboard',
            'user' => Util::getUser(),
        ]);
    }

    public function pilots(): void
    {
        $this->RolePermission();

        echo $this->twig->render('dashboard/pilots.html.twig', [
            'currentPage' => 'dashboard',
            'user' => Util::getUser(),
        ]);
    }

    public function students(): void
    {
        $this->RolePermission();

        echo $this->twig->render('dashboard/students.html.twig', [
            'currentPage' => 'dashboard',
            'user' => Util::getUser(),
        ]);
    }
}