<?php

declare(strict_types=1);

use App\Util;
use Twig\Environment;

class Router
{
    /**
     * @var array<int, array{
     * method: string,
     * path: string,
     * handler: callable,
     * roles: ?array,
     * predicate: mixed
     * }>
     */
    private array $routes = [];

    private mixed $pdo;
    private Environment $twig;
    private bool $debug;

    public function __construct(mixed $pdo, Environment $twig)
    {
        $this->pdo   = $pdo;
        $this->twig  = $twig;
        $this->debug = APP_ENV !== 'production';
    }

    /**
     * Centralise les logs pour le debugging du routage
     */
    private function log(string $message, array $context = []): void
    {
        if ($this->debug) {
            error_log(
                'ROUTER_DEBUG: ' . $message . ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE)
            );
        }
    }

    /**
     * D�clare une nouvelle route
     */
    public function add(
        string $method,
        string $path,
        callable $handler,
        array|string|null $roles = null,
        mixed $predicate = null
    ): void {
        $this->routes[] = [
            'method'    => strtoupper($method),
            'path'      => $path,
            'handler'   => $handler,
            'roles'     => $roles !== null ? (array) $roles : null,
            'predicate' => $predicate,
        ];
    }

    /**
     * Ex�cute le routage en fonction de l'URL et de la m�thode HTTP
     */
    public function run(string $requestUri, string $requestMethod): void
    {
        $path          = (string) parse_url($requestUri, PHP_URL_PATH);
        $requestMethod = strtoupper($requestMethod);

        $roleObj       = Util::getRole();
        $userRoleValue = $roleObj?->value ?? 'guest';

        $this->log('Incoming Request', [
            'path'      => $path,
            'method'    => $requestMethod,
            'user_role' => $userRoleValue,
        ]);

        foreach ($this->routes as $route) {
            if (
                $requestMethod === $route['method']
                && preg_match('#^' . $route['path'] . '$#', $path, $matches)
            ) {
                $this->log('Route Match Found', ['route' => $route['path']]);
                array_shift($matches);

                // 1. V�rification CSRF pour les requ�tes de modification d'�tat
                if (in_array($requestMethod, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

                    if (!Util::validateCSRFToken((string) $token)) {
                        $this->log('CSRF Failure', [
                            'path'   => $path,
                            'method' => $requestMethod,
                        ]);
                        $this->forbidden('Jeton CSRF invalide.');
                    }
                }

                // 2. V�rification des r�les (ACL)
                if ($route['roles'] !== null) {
                    if (!in_array($userRoleValue, $route['roles'], true)) {
                        $this->log('Role Mismatch', [
                            'required' => $route['roles'],
                            'user_has' => $userRoleValue,
                        ]);

                        $this->forbidden(
                            'Permissions insuffisantes. R�les autoris�s : ' . implode(', ', $route['roles'])
                        );
                    }
                }

                // 3. V�rification du pr�dicat (ex: v�rification de propri�t�)
                if ($route['predicate'] !== null) {
                    $predicate = $route['predicate'];

                    if (!$predicate($matches, $this->pdo)) {
                        $this->log('Predicate/Ownership Failure', [
                            'route'  => $route['path'],
                            'params' => $matches,
                        ]);

                        $this->forbidden('V�rification d?acc�s �chou�e.');
                    }
                }

                $this->log('Executing Handler', ['route' => $route['path']]);
                $route['handler']($matches, $this->pdo, $this->twig);
                return;
            }
        }

        $this->log('No Route Found', [
            'path'   => $path,
            'method' => $requestMethod,
        ]);

        http_response_code(404);
        die('Page non trouv�e.');
    }

    /**
     * Arr�te l'ex�cution avec un code 403
     */
    private function forbidden(string $reason = ''): never
    {
        http_response_code(403);

        $message = 'Acc�s refus� : permissions insuffisantes.';
        if ($this->debug && $reason !== '') {
            $message .= ' (Debug: ' . $reason . ')';
        }

        die($message);
    }
}
