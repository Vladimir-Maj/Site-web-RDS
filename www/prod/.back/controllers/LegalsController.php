<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Util;
use Twig\Environment;

/**
 * LegalsController
 * 
 * Renders legal pages:
 * - Privacy Policy
 * - Terms of Service
 * - Legal Notices
 * - Data Retention Policies
 * 
 * Routes:
 *   GET /legals or /mentions-legales or /privacy
 */
class LegalsController
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Render combined legals page
     * 
     * @return void
     */
    public function index(): void
    {
        // Prepare data for view
        $data = [
            'isLoggedIn' => Util::isLoggedIn(),
            'currentUser' => Util::getUser(),
            'currentRole' => Util::getRole()?->value,
        ];

        echo $this->twig->render('legals/index.html.twig', $data);
    }
}
