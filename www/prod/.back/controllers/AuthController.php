<?php

namespace App\Controllers;

use App\Repository\UserRepository;
use App\Models\UserModel;
use App\Models\RoleEnum;
use Twig\Environment;
use PharIo\Manifest\Email;
use App\Util;

class AuthController extends BaseController
{
    private UserRepository $userRepository;


    public function __construct(UserRepository $userRepository, Environment $twig)
    {
        $this->userRepository = $userRepository;
        $this->twig = $twig;
    }

    public function uploadCv(): void
    {
        // 1. Auth Check using our new Util
        if (!Util::isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        $error = null;
        $success = null;
        $userId = Util::getUserId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 2. CSRF Protection
            $token = $_POST['csrf_token'] ?? '';
            if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
                $this->abort(403, "Jeton CSRF invalide.");
            }

            $file = $_FILES['cv_file'] ?? null;

            // 3. File Validation
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $error = ($file && $file['error'] === UPLOAD_ERR_INI_SIZE)
                    ? "Le fichier dépasse la limite autorisée par le serveur."
                    : "Erreur lors de l'envoi du fichier.";
            } else {
                $allowedTypes = ['application/pdf'];
                $maxSize = 2 * 1024 * 1024; // 2MB

                // Use mime_content_type for better security than $file['type']
                $realMimeType = mime_content_type($file['tmp_name']);

                if (!in_array($realMimeType, $allowedTypes)) {
                    $error = "Seuls les fichiers PDF sont autorisés.";
                } elseif ($file['size'] > $maxSize) {
                    $error = "Le fichier est trop lourd (max 2 Mo).";
                } else {
                    // 4. Path Configuration
                    // Adjusting levels to reach /cdn/uploads/cvs/ from /prod/index.php context
                    $baseDir = dirname(__DIR__, 2);
                    $uploadDir = $baseDir . '/../cdn/uploads/cvs/';
                    error_log("BaseDir" . $uploadDir);

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0775, true);
                    }

                    // 5. File Execution & DB Update
                    $fileName = 'cv_' . bin2hex(random_bytes(8)) . '.pdf';
                    $destPath = $uploadDir . $fileName;

                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        $publicPath = '/cdn/uploads/cvs/' . $fileName;

                        try {
                            $this->userRepository->updateCvPath($userId, $publicPath);
                            $success = "Votre CV a été mis à jour avec succès.";
                        } catch (\Exception $e) {
                            error_log("DB Update Error: " . $e->getMessage());
                            $error = "Erreur lors de l'enregistrement en base de données.";
                        }
                    } else {
                        $error = "Erreur système lors du déplacement du fichier.";
                    }
                }
            }
        }

        echo $this->twig->render('auth/upload_cv.html.twig', [
            'error' => $error,
            'success' => $success,
            'csrf_token' => $_SESSION['csrf_token']
        ]);
    }

    public function register(): void
    {
        $error = null;
        $success = null;
        $old = $_POST; // Keep track of old inputs for the form

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. CSRF Protection
            $token = $_POST['csrf_token'] ?? '';
            if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
                $this->abort(403, "Invalid CSRF token.");
            }

            // 2. Data Sanitization
            $emailRaw = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            $firstName = trim($_POST['first_name'] ?? '');
            $roleRaw = $_POST['role'] ?? 'student';

            // 3. Validation Logic
            if ($password !== $confirm) {
                $error = "Les mots de passe ne correspondent pas.";
            } elseif (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
                $error = "Format d'email invalide.";
            } elseif (empty($firstName)) {
                $error = "Le prénom est requis.";
            } elseif ($this->userRepository->findByEmail($emailRaw)) {
                $error = "Cet email est déjà utilisé.";
            } else {
                try {
                    // 4. Model Preparation
                    $user = new UserModel();
                    $user->email = new Email($emailRaw);
                    $user->password = password_hash($password, PASSWORD_ARGON2ID);
                    $user->role = RoleEnum::tryFrom($roleRaw) ?? RoleEnum::Student;
                    $user->first_name = $firstName;
                    $user->last_name = null; // or $_POST['last_name']
                    $user->is_active = true;
                    $user->created_at = date('Y-m-d H:i:s');

                    // 5. Database Persistance
                    $this->userRepository->push($user);

                    $success = "Votre compte a été créé ! Vous pouvez maintenant vous connecter.";
                    $old = []; // Clear inputs on success

                } catch (\Exception $e) {
                    error_log("Registration Error: " . $e->getMessage());
                    $error = "Une erreur technique est survenue.";
                }
            }
        }

        echo $this->twig->render('auth/register.html.twig', [
            'error' => $error,
            'success' => $success,
            'old' => $old,
            'csrf_token' => $_SESSION['csrf_token']
        ]);
    }

    /**
     * Centralized Session Setter
     * Ensures index.php and all controllers see the same data.
     */
    private function setSession(UserModel $user): void
    {
        session_regenerate_id(true); // Deletes old session file

        // IMPORTANT: Generate a fresh CSRF token for the NEW session
        Util::setCSRFToken(
            bin2hex(random_bytes(32))
        );


        Util::setUserId($user->id);
        Util::setRole($user->role);
        Util::setUserData([
            'id' => $user->id,
            'role' => $user->role->value,
            'first_name' => $user->first_name,
            'email' => $user->email->asString()
        ]);
    }

    public function login(): void
    {
        $error = null;
        $lastEmail = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. Correct the CSRF Check
            $userToken = $_POST['csrf_token'] ?? '';
            $storedToken = Util::getCSRFToken(); // This ensures a token is in session

            // Standard comparison: if tokens don't match, kill the request
            if (empty($userToken) || !hash_equals($storedToken, $userToken)) {
                http_response_code(403);
                die("CSRF token mismatch");
            }

            $lastEmail = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            $user = $this->userRepository->findByEmail($lastEmail);

            if ($user && password_verify($password, $user->password)) {
                $this->setSession($user);
                $this->handleRoleRedirection($user->role);
                return;
            }

            $error = "Identifiants invalides.";
        }

        echo $this->twig->render('auth/login.html.twig', [
            'error' => $error,
            'last_email' => $lastEmail,
            'csrf_token' => Util::getCSRFToken()
        ]);
    }

    public function profile(): void
    {
        // Safety check (though index.php middleware usually handles this)
        if (empty(Util::getCSRFToken())) {
            header('Location: /login');
            exit;
        }

        $user = $this->userRepository->findById($_SESSION['user_id']);

        if (!$user) {
            $this->logout();
            return;
        }

        echo $this->twig->render('auth/profile.html.twig', [
            'user' => $user,
            'csrf_token' => Util::getCSRFToken()
        ]);
    }

    public function logout(): void
    {
        // 1. Clear the data
        $_SESSION = [];

        // 2. Kill the cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // 3. Destroy the physical session
        session_destroy();

        // 4. Start a BRAND NEW session immediately for the guest
        // This ensures the /login page has a valid csrf_token to show in the form
        session_start();
        Util::setCSRFToken(bin2hex(random_bytes(32)));

        header('Location: /login');
        exit;
    }

    private function handleRoleRedirection(RoleEnum $role): void
    {
        $path = match ($role) {
            RoleEnum::Admin => '/admin/dashboard',
            RoleEnum::Pilote => '/pilote/dashboard',
            RoleEnum::Student => '/profile', // Redirect students to their profile
            default => '/',
        };

        header("Location: $path");
        exit;
    }
}