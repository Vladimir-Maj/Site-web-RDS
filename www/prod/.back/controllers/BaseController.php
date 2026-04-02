<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\RoleEnum;
use Twig\Environment;
use PharIo\Manifest\Email;
use App\Util;

abstract class BaseController
{
    protected Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function jsonResponse(mixed $data, int $status = 200): void
    {
        header('Content-Type: application/json', true, $status);
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    protected function jsonError(string $message, int $status = 400): void
    {
        $this->jsonResponse(['error' => $message], $status);
    }

    /**
     * Common Utility: Ensure user is logged in
     */
    protected function checkAuth(): void
    {
        if (!Util::isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Checks if the provided ID matches the current session.
     * Note: IDs are compared as strings for consistency.
     */
    protected function isSessionUser(string|int $id): bool
    {
        $currentUserId = Util::getUserId();
        return $currentUserId !== null && (string) $currentUserId === (string) $id;
    }

    /**
     * Common Utility: Check if user has the required permission
     */
    protected function checkRole(array $allowedRoles): void
    {
        $this->checkAuth();

        $currentRole = Util::getRole()?->value;

        if (!in_array($currentRole, $allowedRoles, true)) {
            $this->abort(403, "Access Denied: You do not have the required permissions.");
        }
    }

    protected function abort(int $code, string $message): void
    {
        http_response_code($code);
        echo $this->twig->render('errors/error.html.twig', [
            'code' => $code,
            'message' => $message
        ]);
        exit;
    }

    protected function isSuperUser(): bool
    {
        return Util::getRole() === RoleEnum::Admin;
    }

    protected function isPilote(): bool
    {
        return Util::getRole() === RoleEnum::Pilote;
    }

    protected function isPrivileged(): bool
    {
        return $this->isSuperUser() || $this->isPilote();
    }

    /**
     * Checks if user is the owner OR has elevated permissions.
     */
    protected function isTargetOrPrivileged(string|int|Email $id): bool
    {
        if ($id instanceof Email) {
            $emailString = strtolower($id->asString());
            $sessionEmail = strtolower((string) ($_SESSION['email_user'] ?? $_SESSION['email'] ?? ''));

            return $this->isPrivileged() || ($sessionEmail === $emailString);
        }

        if (is_string($id) && filter_var($id, FILTER_VALIDATE_EMAIL) !== false) {
            throw new \InvalidArgumentException("ERROR! PASSED AN EMAIL STRING AS AN ID ARGUMENT!");
        }

        return $this->isPrivileged() || $this->isSessionUser($id);
    }

    /**
     * Helper privé pour uniformiser les erreurs JSON
     */
    protected function renderJsonError(string $message, int $code): void
    {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode(["error" => $message]);
        exit;
    }

    public function abortIfNotPriv(): bool
    {
        if ($this->isPrivileged() === false) {
            $this->abort(403, "Unauthorized access.");
        }
        return true;
    }
}