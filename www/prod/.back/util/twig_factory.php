<?php
// .back/util/TwigFactory.php

declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Extension\DebugExtension;

class TwigFactory
{
    /** @var Environment|null */
    private static ?Environment $twig = null;

    /**
     * Initializes and returns the Twig Environment
     */
    public static function getTwig(): Environment
    {
        if (self::$twig === null) {
            // 1. Set the path to your private templates folder
            // __DIR__ is .back/util, so we go up one level to .back/templates
            $templateDir = __DIR__ . '/../templates';

            if (!is_dir($templateDir)) {
                // This will show a clear error in your browser if the folder is missing
                die("FATAL: Twig templates directory not found at: " . realpath(__DIR__ . '/..') . "/templates. Please create it.");
            }

            $loader = new FilesystemLoader($templateDir);

            // 2. Initialize the Environment
            self::$twig = new Environment($loader, [
                'cache' => false, // Change to __DIR__ . '/../cache' for production speed
                'debug' => true,  // Allows {{ dump() }} for troubleshooting
                'auto_reload' => true,
                'strict_variables' => true,
            ]);

            // 3. Add Extensions
            self::$twig->addExtension(new DebugExtension());

            // 4. Add Global Variables
            // This makes these available in every .twig file without passing them in the render() array
            self::$twig->addGlobal('site_url', defined('SITE_URL') ? SITE_URL : '');

            // Share session data (useful for checking if user is logged in)
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            self::$twig->addGlobal('session', $_SESSION);

            // Helpful for absolute paths to assets
            self::$twig->addGlobal('cdn_url', defined('CDN_URL') ? CDN_URL : '/cdn');
            // .back/util/twig_factory.php

            self::$twig->addGlobal('cdn_url', CDN_URL);   // From your config.php
            self::$twig->addGlobal('site_url', "https://prod.stageflow.fr"); // From your config.php


        }

        return self::$twig;
    }

    /**
     * Helper method to render a template directly
     */
    public static function render(string $template, array $data = []): void
    {
        echo self::getTwig()->render($template, $data);
    }
}