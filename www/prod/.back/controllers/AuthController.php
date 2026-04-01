<?php

namespace App\Controllers;

use App\Repository\UserRepository;
use App\Models\UserModel;
use App\Models\RoleEnum;
use PDO;
use Twig\Environment;
use PharIo\Manifest\Email;
use App\Util;

class AuthController extends BaseController
{
    private UserRepository $userRepository;
    private PDO $pdo;



    public function __construct(UserRepository $userRepository, Environment $twig, PDO $pdo)
    {
        $this->userRepository = $userRepository;
        $this->twig = $twig;
        $this->pdo = $pdo;
    }



    public function uploadCv(): void
    {
        $cvFast = new CVFast($this->userRepository, $this->pdo, $this->twig);

        // 1. Auth check
        if (!Util::isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        $error = null;
        $success = null;
        $userId = Util::getUserId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // 2. CSRF protection
            $submittedToken = $_POST['csrf_token'] ?? '';
            if (!hash_equals(Util::getCSRFToken(), $submittedToken)) {
                $this->jsonResponse(['error' => 'Jeton CSRF invalide.'], 403);
            }

            $file = $_FILES['cv_file'] ?? null;
            $isPrimary = isset($_POST['is_primary']) && $_POST['is_primary'] === '1';

            // 3. File validation
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $error = ($file && $file['error'] === UPLOAD_ERR_INI_SIZE)
                    ? "Le fichier dépasse la limite autorisée."
                    : "Erreur lors de l'envoi du fichier.";
            } elseif (mime_content_type($file['tmp_name']) !== 'application/pdf') {
                $error = "Seuls les fichiers PDF sont autorisés.";
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $error = "Le fichier est trop lourd (max 2 Mo).";
            } else {

                // 4. Path configuration
                $uploadDir = '/var/www/html/cdn/uploads/cvs/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                // 5. Move file
                $originalName = basename($file['name']);
                $fileName = 'cv_' . bin2hex(random_bytes(8)) . '.pdf';
                $destPath = $uploadDir . $fileName;

                if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                    $error = "Erreur système lors du déplacement du fichier.";
                } else {
                    $publicPath = '/cdn/uploads/cvs/' . $fileName;

                    // 6. Delegate DB work to CVFast
                    if ($cvFast->store($userId, $originalName, $publicPath, $isPrimary)) {
                        $success = "Votre CV a été ajouté avec succès.";
                    } else {
                        unlink($destPath); // clean up orphaned file on DB failure
                        $error = "Erreur lors de l'enregistrement en base de données.";
                    }
                }
            }
        }

        echo $this->twig->render('auth/upload_cv.html.twig', [
            'error' => $error,
            'success' => $success,
            'csrf_token' => Util::getCSRFToken(),
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
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email instanceof Email
                ? $user->email->asString()   // you already use this in the Twig templates
                : (string) $user->email,
            'role' => $user->role instanceof RoleEnum ? $user->role->value : $user->role,
        ]);
    }

    /**
     * GET: Renders the student registration form.
     * POST: Processes the registration and generates a temporary password.
     */
    public function registerStudent(): void
    {

        $repo = new UserRepository($this->pdo);
        // 1. Security Check: Only privileged users (Pilots/Admins)
        if (!$this->isPrivileged()) {
            $this->abort(403, "Accès refusé. Seuls les pilotes peuvent inscrire des étudiants.");
        }

        // --- HANDLE GET REQUEST ---
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Fetch the pilot's current promotion to pre-fill the form
            // Assuming the logged-in user's ID is stored in the session
            $pilotId = $_SESSION['user_id'] ?? '';
            $currentPromo = $repo->getPromoByPilote($pilotId);

            $this->twig->render('auth/register_student.html.twig', [
                'current_promo' => $currentPromo,
                'csrf_token' => Util::getCSRFToken()
            ]);
            echo "page rendered";
            return;
        }

        // --- HANDLE POST REQUEST ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF Validation (Crucial for security)
            if (!Util::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->abort(403, "CSRF token invalid.");
            }

            // 1. Data Collection
            $email = $_POST['email'] ?? '';
            $firstName = $_POST['first_name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $promoId = $_POST['promotion_id'] ?? '';

            if (empty($email) || empty($firstName) || empty($lastName) || empty($promoId)) {
                $this->abort(400, "Tous les champs sont obligatoires.");
            }

            // 2. Generate Temporary Password
            $tempPassword = bin2hex(random_bytes(4)); // e.g., 'a1b2c3d4'
            $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);

            // 3. Create User Model
            $user = new UserModel();
            $user->email = $email;
            $user->password = $hashedPassword;
            $user->first_name = $firstName;
            $user->last_name = $lastName;
            $user->role = RoleEnum::Student;
            $user->is_active = true;

            // 4. Persistence Logic
            // push() handles the 'user' table
            $newHexId = $repo->push($user);

            if ($newHexId) {
                // makeStudent() handles 'student' and 'student_enrollment' tables
                $success = $repo->makeStudent($newHexId, $promoId, 'Searching');

                if ($success) {
                    // Store temp password in session to show it ONCE on the next page
                    $_SESSION['temp_password_display'] = $tempPassword;
                    $_SESSION['flash_success'] = "L'étudiant $firstName $lastName a été créé avec succès.";

                    header("Location: /pilot/students");
                    exit;
                }
            }

            $this->abort(500, "Une erreur est survenue lors de la création de l'étudiant.");
        }
    }


    public function login(): void
    {
        $error = null;
        $lastEmail = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $lastEmail = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $user = $this->userRepository->findByEmail($lastEmail);

            if ($user && password_verify($password, $user->password)) {
                // $user->role is already a RoleEnum — no tryFrom() needed
                $role = $user->role instanceof RoleEnum
                    ? $user->role
                    : RoleEnum::tryFrom($user->role);

                if (!$role) {
                    $error = "Rôle utilisateur invalide.";
                } else {
                    session_regenerate_id(true);
                    Util::setCSRFToken(bin2hex(random_bytes(32)));
                    Util::setUserId((string) $user->id);
                    Util::setRole($role);
                    Util::setUserData([
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email instanceof Email
                            ? $user->email->asString()   // you already use this in the Twig templates
                            : (string) $user->email,
                        'role' => $user->role instanceof RoleEnum ? $user->role->value : $user->role,
                    ]);
                    $this->handleRoleRedirection($role);
                    return;
                }
            } else {
                $error = "Identifiants invalides.";
            }
        }

        echo $this->twig->render('auth/login.html.twig', [
            'error' => $error,
            'last_email' => $lastEmail,
            'csrf_token' => Util::getCSRFToken(),
        ]);
    }


    public function profile(): void
    {
        if (!Util::isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        $user = $this->userRepository->findById(Util::getUserId());

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
            RoleEnum::Admin => '/dashboard',
            RoleEnum::Pilote => '/dashboard',
            RoleEnum::Student => '/profile',
            default => '/',
        };

        header("Location: $path");
        exit;
    }
}