<?php

namespace App\Controllers;

use App\Controller\UserController;
use App\Repository\UserRepository;
use App\Models\UserModel;
use Twig\Environment;
use App\Models\RoleEnum;

class AuthController extends BaseController
{
    private UserRepository $userRepository;

    // FIX: Match the visibility of the parent class (BaseController)
    protected Environment $twig;

    /**
     * TASK-22: Dependency Injection via Constructor
     */
    public function __construct(UserRepository $userRepository, Environment $twig)
    {
        // If BaseController has its own constructor, you might need:
        // parent::__construct($twig); 

        $this->userRepository = $userRepository;
        $this->twig = $twig;
    }

    public function login(): void
    {
        $error = null;
        $lastEmail = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // TASK-28: Protection CSRF
            $token = $_POST['csrf_token'] ?? '';
            if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
                $this->abort(403, "CSRF token mismatch");
            }

            $lastEmail = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            // TASK-27: Validation Back (PHP)
            if (!filter_var($lastEmail, FILTER_VALIDATE_EMAIL)) {
                $error = "Format d'email invalide.";
            } else {
                // TASK-24: Récupération et vérification password_verify()
                $user = $this->userRepository->findByEmail($lastEmail);

                if ($user && password_verify($password, $user->password)) {

                    // TASK-25: Stocker la session dans un cookie sécurisé
                    // (Configuration gérée dans index.php, ici on régénère l'ID)
                    session_regenerate_id(true);

                    $_SESSION['user'] = [
                        'id' => $user->id,
                        'role' => $user->role, // e.g., 'admin', 'student'
                        'email' => $user->email->asString()
                    ];

                    // TASK-29: Redirection post-login selon le rôle
                    $this->handleRoleRedirection($user->role);
                    return;
                } else {
                    $error = "Identifiants invalides.";
                }
            }
        }

        // TASK-23: Rendu de la vue avec les données nécessaires
        echo $this->twig->render('auth/login.html.twig', [
            'error' => $error,
            'last_email' => $lastEmail,
            'csrf_token' => $_SESSION['csrf_token']
        ]);
    }

    /**
     * TASK-22: Logout Logic
     */
    public function logout(): void
    {
        $_SESSION = [];
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
        session_destroy();
        header('Location: /login');
        exit;
    }

    public function register(): void
    {
        $error = null;
        $success = null;
        $old = $_POST;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // TASK-28: CSRF Check
            if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
                $this->abort(403, "CSRF mismatch");
            }

            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            $emailRaw = $_POST['email'] ?? '';
            $roleRaw = $_POST['role'] ?? 'student';

            if ($password !== $confirm) {
                $error = "Les mots de passe ne correspondent pas.";
            } elseif (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
                $error = "Email invalide.";
            } else {
                $existing = $this->userRepository->findByEmail($emailRaw);
                if ($existing) {
                    $error = "Cet email est déjà utilisé.";
                } else {
                    try {
                        // TASK-24: Password Hashing
                        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);

                        // Map the role from the form to the Enum
                        $role = RoleEnum::tryFrom($roleRaw) ?? RoleEnum::Student;


                        /** * We pass null/empty string for ID because your 
                         * unit test confirms the DB trigger handles UUIDv7 generation.
                         */
                        $userModel = new UserModel();
                        $userModel->email = new \PharIo\Manifest\Email($emailRaw);
                        $userModel->password = $hashedPassword;
                        $userModel->role = $role;

                        // Explicitly initialize these even if they are null to satisfy PHP's type checker
                        $userModel->first_name = $_POST['username'] ?? null;
                        $userModel->last_name = null;
                        $userModel->is_active = true;
                        $userModel->created_at = date('Y-m-d H:i:s');
                        // TASK-22: Push to DB
                        $this->userRepository->push($userModel);

                        $success = "Votre compte a été créé avec succès !";
                        $old = []; // Clear inputs on success
                    } catch (\Exception $e) {
                        error_log("Registration Error: " . $e->getMessage());
                        $error = "Une erreur est survenue lors de la création du compte.";
                    }
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
     * Render the user profile page
     * TASK-22 & TASK-26 (Auth Check)
     */
    // In AuthController.php -> profile() method

    public function profile(): void
    {
        // 1. Authentication Check
        if (!isset($_SESSION['user']['id'])) {
            header('Location: /login');
            exit;
        }

        // 2. Fetch full user data
        $user = $this->userRepository->findById($_SESSION['user']['id']);

        // 3. Validation: Ensure user still exists in DB
        if (!$user) {
            $this->logout(); // Force logout if record is missing
            return;
        }

        // 4. Render with both the User object and the CSRF token
        echo $this->twig->render('auth/profile.html.twig', [
            'user' => $user,
            'csrf_token' => $_SESSION['csrf_token'] ?? null // Fallback to null if not set
        ]);
    }
    /**
     * TASK-29: Redirection helper
     */
    private function handleRoleRedirection(RoleEnum $role): void
    {
        switch ($role) {
            case RoleEnum::Admin:
                header('Location: /admin/dashboard');
                break;
            case RoleEnum::Student:
                header('Location: /app/dashboard');
                break;
            case RoleEnum::Pilote:
                header('Location: /pilote/dashboard');
                break;
            default:
                header('Location: /');
                break;
        }
        exit;
    }

    public function uploadCv(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }

        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF Check
            if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
                $this->abort(403, "CSRF Token Invalid");
            }

            $file = $_FILES['cv_file'] ?? null;

            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['application/pdf'];
                $maxSize = 2 * 1024 * 1024; // 2MB

                if (!in_array($file['type'], $allowedTypes)) {
                    $error = "Seuls les fichiers PDF sont autorisés.";
                } elseif ($file['size'] > $maxSize) {
                    $error = "Le fichier est trop lourd (max 2 Mo).";
                } else {
                    $userId = $_SESSION['user']['id'];

                    /**
                     * PATH FIX:
                     * From: /var/www/html/prod/.back/controllers/
                     * To reach: /var/www/html/cdn/uploads/cvs/
                     * We go up 3 levels: controllers -> .back -> prod -> html
                     */
                    $baseDir = dirname(__DIR__, 3);
                    $uploadDir = $baseDir . '/cdn/uploads/cvs/';

                    // Ensure the directory exists with the correct permissions
                    if (!is_dir($uploadDir)) {
                        // Using 0777 inside Docker dev environments is often necessary 
                        // if the host user and container user (www-data) don't match UIDs.
                        mkdir($uploadDir, 0777, true);
                    }

                    $fileName = 'cv_' . bin2hex(random_bytes(8)) . '.pdf';
                    $destPath = $uploadDir . $fileName;

                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        // This is the path used for the <img src="..."> or <a href="..."> in HTML
                        $publicPath = '/cdn/uploads/cvs/' . $fileName;

                        $this->userRepository->updateCvPath($userId, $publicPath);
                        $success = "Votre CV a été mis à jour avec succès.";
                    } else {
                        // Check if the directory is actually writable if it fails here
                        $error = "Erreur lors de l'enregistrement du fichier. Vérifiez les permissions du dossier.";
                    }
                }
            } else {
                $error = "Erreur lors de l'envoi du fichier.";
            }
        }

        echo $this->twig->render('auth/upload_cv.html.twig', [
            'error' => $error,
            'success' => $success,
            'csrf_token' => $_SESSION['csrf_token']
        ]);
    }
}