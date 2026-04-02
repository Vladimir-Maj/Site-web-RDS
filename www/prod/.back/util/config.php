<?php

use App\Util;
// Global Configuration
// Fix the typo: stageflow (not stagrflow) and use https
const CDN_URL = 'https://cdn.stageflow.fr';
const APP_ENV = 'development';

require_once __DIR__ . '/../../vendor/autoload.php';

// prod/.back/util/config.php
require_once 'db_connect.php';

spl_autoload_register(function ($class_name) {
    // On définit la racine du projet (www/prod/)
    $root = realpath(__DIR__ . '/../../');

    // Liste des dossiers où tes classes (Models, Repos, Utils) sont rangées
    $sources = [
        $root . '/.back/models/',
        $root . '/.back/repository/',
        $root . '/.back/controllers/',
        $root . '/.back/util/'
    ];

    // Support namespaced classes by resolving both fully-qualified path and short class name.
    $relativeClass = str_replace('\\', DIRECTORY_SEPARATOR, ltrim($class_name, '\\'));
    $shortClass = basename($relativeClass);

    foreach ($sources as $source) {
        $candidates = [
            $source . $relativeClass . '.php',
            $source . $shortClass . '.php'
        ];

        foreach ($candidates as $file) {
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

class Router
{
    private array $routes = [];
    private $pdo;
    private $twig;
    private bool $debug = true; // Enable based on config

    public function __construct($pdo, $twig)
    {
        $this->pdo = $pdo;
        $this->twig = $twig;
    }

    private function log(string $message, array $context = []): void
    {
        if ($this->debug) {
            // DO NOT USE ECHO HERE. It breaks redirects.
            // This sends the debug info to your PHP error log (e.g., /var/log/apache2/error.log)
            error_log("ROUTER_DEBUG: $message | " . json_encode($context));
        }
    }

    public function add(string $method, string $path, callable $handler, $roles = null, $predicate = null): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'roles' => $roles ? (array) $roles : null,
            'predicate' => $predicate
        ];
    }

    public function run(string $requestUri, string $requestMethod): void
    {
        $path = parse_url($requestUri, PHP_URL_PATH);
        $requestMethod = strtoupper($requestMethod);

        $roleObj = Util::getRole();
        $userRoleValue = $roleObj?->value ?? 'guest';

        $this->log("Incoming Request", ['path' => $path, 'method' => $requestMethod, 'user_role' => $userRoleValue]);

        foreach ($this->routes as $route) {
            // Check if Path and Method match
            if ($requestMethod === $route['method'] && preg_match("#^" . $route['path'] . "$#", $path, $matches)) {
                $this->log("Route Match Found", ['route' => $route['path']]);

                array_shift($matches);

                // 1. CSRF Check
                if (in_array($requestMethod, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
                    try {
                        Util::validateCSRFToken($token);
                    } catch (\Exception $e) {
                        $this->log("CSRF Failure", ['error' => $e->getMessage()]);
                        $this->forbidden("CSRF: " . $e->getMessage());
                    }
                }

                // 2. Role Check
                if ($route['roles'] !== null) {
                    if (!in_array($userRoleValue, $route['roles'])) {
                        $this->log("Role Mismatch", [
                            'required' => $route['roles'],
                            'user_has' => $userRoleValue
                        ]);
                        $this->forbidden("Role mismatch. Required: " . implode(',', $route['roles']));
                    }
                }

                // 3. Predicate Check
                if ($route['predicate'] !== null) {
                    if (!$route['predicate']($matches, $this->pdo)) {
                        $this->log("Predicate/Ownership Failure");
                        $this->forbidden("Predicate check failed.");
                    }
                }

                $this->log("Executing Handler");
                $route['handler']($matches, $this->pdo, $this->twig);
                return;
            }
        }

        $this->log("No Route Found");
        http_response_code(404);
        die("Page non trouvée.");
    }

    private function forbidden(string $reason = "")
    {
        http_response_code(403);
        $msg = "Accès refusé : Permissions insuffisantes.";
        if ($this->debug && $reason) {
            $msg .= " (Debug: $reason)";
        }
        die($msg);
    }
}