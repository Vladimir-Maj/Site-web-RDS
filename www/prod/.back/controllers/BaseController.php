<?php
declare(strict_types=1);

namespace App\Controllers;

use Twig\Environment;

abstract class BaseController {
    protected Environment $twig;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Common Utility: Ensure user is logged in
     */
    protected function checkAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login.php');
            exit;
        }
    }

    /**
     * Checks if the provided ID matches the current session.
     * Note: UUIDs are strings.
     */
    protected function isSessionUser(string $id): bool {
        // We use a loose check or strict string check. 
        // UUIDs should be compared case-insensitively if coming from different sources,
        // but typically lower-case hex is standard.
        return isset($_SESSION['user_id']) && strtolower((string)$_SESSION['user_id']) === strtolower($id);
    }

    /**
     * Common Utility: Check if user has the required permission
     */
    protected function checkRole(array $allowedRoles): void {
        $this->checkAuth();
        if (!in_array($_SESSION['role'] ?? '', $allowedRoles)) {
            $this->abort(403, "Access Denied: You do not have the required permissions.");
        }
    }

    protected function abort(int $code, string $message): void {
        http_response_code($code);
        // Make sure your twig path matches your actual file structure
        echo $this->twig->render('errors/error.html.twig', [
            'code' => $code,
            'message' => $message
        ]);
        exit;
    }

    protected function isSuperUser(): bool {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    protected function isPilote(): bool {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'pilote';
    }

    protected function isPrivileged(): bool {
        return $this->isSuperUser() || $this->isPilote();
    }

    /**
     * Refactored: Checks if user is the owner OR has elevated permissions.
     */
    protected function isTargetOrPrivileged(string $id): bool {
        return $this->isPrivileged() || $this->isSessionUser($id);
    }
}