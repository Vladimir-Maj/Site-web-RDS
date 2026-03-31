<?php

namespace App;

use App\Models\RoleEnum;

class Util
{
    // --- GETTERS ---
    // --- GETTERS ---
    public static function getCSRFToken(): string
    {
        // Ensure session is started (just in case)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // If no token exists, generate, set, and then return it
        if (empty($_SESSION["csrf_token"])) {
            $token = bin2hex(random_bytes(32));
            self::setCSRFToken($token);
        }

        return $_SESSION["csrf_token"];
    }

    public static function getUserId(): ?string
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function getUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Returns the RoleEnum or null if not logged in.
     */
    public static function getRole(): ?RoleEnum
    {
        $role = $_SESSION['user_role'] ?? null;
        return $role ? RoleEnum::tryFrom($role) : null;
    }

    public static function getUserName(): ?string
    {
        // Checks the nested user array we set in AuthController
        return $_SESSION['user']['first_name'] ?? null;
    }

    // --- SETTERS ---

    public static function setUserId(string $id): void
    {
        $_SESSION['user_id'] = $id;
    }

    public static function setCSRFToken(string $tok): void
    {
        $_SESSION["csrf_token"] = $tok;
    }

    public static function setRole(RoleEnum $role): void
    {
        $_SESSION['user_role'] = $role->value;
    }

    /**
     * Stores the full user display data (usually for Twig)
     */
    public static function setUserData(array $data): void
    {
        $_SESSION['user'] = $data;
    }

    // --- UTILITIES ---

    public static function isLoggedIn(): bool
    {
        return self::getUserId() !== null;
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}