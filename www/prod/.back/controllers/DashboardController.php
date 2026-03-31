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
        parent::__construct($twig);
    }

    public function index(): void
    {
        $this->abortIfNotPriv();

        echo $this->twig->render('dashboard/index.html.twig', [
            'sidebar_active' => 'dashboard'
        ]);
    }

    public function pilots(): void
    {
        if (Util::getRole() !== RoleEnum::Admin) {
            $this->abort(403, "Acces refuse.");
        }

        echo $this->twig->render('dashboard/pilots.html.twig', [
            'sidebar_active' => 'pilots'
        ]);
    }

    public function students(): void
    {
        $this->abortIfNotPriv();

        echo $this->twig->render('dashboard/students.html.twig', [
            'sidebar_active' => 'students'
        ]);
    }
}
