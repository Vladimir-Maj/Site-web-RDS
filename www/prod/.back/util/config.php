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
        $root . '/.back/util'
    ];

    foreach ($sources as $source) {
        $file = $source . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

class Router
{
    private array $routes = [];
    private $pdo;
    private $twig;

    public function __construct($pdo, $twig)
    {
        $this->pdo = $pdo;
        $this->twig = $twig;
    }

    /**
     * @param array|string|null $roles Rôles autorisés (ex: ['admin', 'pilote'])
     * @param callable|null $predicate Verification supplémentaire (ex: ownership)
     */
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
        $userRole = Util::getRole()?->value ?? 'guest';

        foreach ($this->routes as $route) {
            if ($requestMethod === $route['method'] && preg_match("#^" . $route['path'] . "$#", $path, $matches)) {
                array_shift($matches);

                // 1. Protection CSRF pour les méthodes d'écriture
                if (in_array($requestMethod, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                    // On récupère le token soit dans le POST, soit dans les Headers (pour l'AJAX)
                    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

                    if (!Util::validateCSRFToken($token)) {
                        $this->forbidden("Jeton CSRF invalide ou manquant.");
                    }
                }

                // 2. Vérification des Rôles
                if ($route['roles'] !== null && !in_array($userRole, $route['roles'])) {
                    $this->forbidden();
                }

                // 3. Vérification du Prédicat (Ownership)
                if ($route['predicate'] !== null && !$route['predicate']($matches, $this->pdo)) {
                    $this->forbidden();
                }

                $route['handler']($matches, $this->pdo, $this->twig);
                return;
            }
        }
        http_response_code(404);
        die("Page non trouvée.");
    }

    private function forbidden()
    {
        http_response_code(403);
        die("Accès refusé : Permissions insuffisantes.");
    }
}
