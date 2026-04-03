<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repository\UserRepository;
use App\Models\UserModel;
use App\Models\RoleEnum;
use PharIo\Manifest\Email;
use PDO;
use Twig\Environment;
use App\Util;

class AuthController extends BaseController
{
    private UserRepository $userRepository;
    private PDO $pdo;

    public function __construct(UserRepository $userRepository, Environment $twig, PDO $pdo)
    {
        parent::__construct($twig);
        $this->userRepository = $userRepository;
        $this->pdo = $pdo;
    }

    /**
     * Handles CV Uploads. 
     * Uses CVFast to support multiple CVs if applicable.
     */
    public function uploadCv(): void
    {
        if (!Util::isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        $cvFast = new CVFast($this->userRepository, $this->twig, $this->pdo);
        $error = null;
        $success = null;
        $userId = Util::getUserId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Util::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->abort(403, "Jeton CSRF invalide.");
            }

            $file = $_FILES['cv_file'] ?? null;
            $isPrimary = isset($_POST['is_primary']) && $_POST['is_primary'] === '1';
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $error = ($file && $file['error'] === UPLOAD_ERR_INI_SIZE)
                    ? "Le fichier dépasse la limite autorisée."
                    : "Erreur lors de l'envoi du fichier.";
            } elseif (mime_content_type($file['tmp_name']) !== 'application/pdf') {
                $error = "Seuls les fichiers PDF sont autorisés.";
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $error = "Le fichier est trop lourd (max 2 Mo).";
            } else {
                // Path configuration
                $baseDir = dirname(__DIR__, 2);
                $uploadDir = $baseDir . '/cdn/uploads/cvs/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $originalName = basename($file['name']);
                $fileName = 'cv_' . bin2hex(random_bytes(8)) . '.pdf';
                $destPath = $uploadDir . $fileName;

                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $publicPath = '/cdn/uploads/cvs/' . $fileName;

                    // Delegate to repository/service for DB persistence
                    if ($cvFast->store($userId, $originalName, $publicPath, $isPrimary)) {
                        $success = "Votre CV a été ajouté avec succès.";
                    } else {
                        unlink($destPath);
                        $error = "Erreur lors de l'enregistrement en base de données.";
                    }
                } else {
                    $error = "Erreur système lors du déplacement du fichier.";
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
        $this->abortIfNotPriv();

        $error = null;
        $success = null;
        $old = $_POST;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Util::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->abort(403, "Invalid CSRF token.");
            }

            $emailRaw = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            $firstName = trim($_POST['first_name'] ?? '');
            $roleRaw = $_POST['role'] ?? 'student';

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
                    $user = new UserModel();
                    $user->email = new Email($emailRaw);
                    $user->password = password_hash($password, PASSWORD_ARGON2ID);
                    $user->role = RoleEnum::tryFrom($roleRaw) ?? RoleEnum::Student;
                    $user->first_name = $firstName;
                    $user->is_active = true;
                    $user->created_at = date('Y-m-d H:i:s');

                    $this->userRepository->push($user);
                    $success = "Votre compte a été créé !";
                    $old = [];
                } catch (\Throwable $e) {
                    error_log("Registration Error: " . $e->getMessage());
                    $error = "Une erreur technique est survenue.";
                }
            }
        }

        echo $this->twig->render('auth/register.html.twig', [
            'error' => $error,
            'success' => $success,
            'old' => $old,
            'csrf_token' => Util::getCSRFToken()
        ]);
    }

    public function registerStudent(): void
    {
        if (!$this->isPrivileged()) {
            $this->abort(403, "Accès refusé. Seuls les administrateurs et pilotes peuvent inscrire des étudiants.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $userId = (int) (Util::getUserId() ?? 0);
            $allPromotions = [];

            if ($this->isSuperUser()) {
                $stmt = $this->pdo->query("\n                    SELECT\n                        id_promotion AS id,\n                        label_promotion AS label,\n                        academic_year_promotion AS academic_year\n                    FROM promotion\n                    ORDER BY academic_year_promotion DESC, label_promotion ASC\n                ");
                $allPromotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->pdo->prepare("\n                    SELECT\n                        p.id_promotion AS id,\n                        p.label_promotion AS label,\n                        p.academic_year_promotion AS academic_year\n                    FROM promotion p\n                    INNER JOIN promotion_assignment pa ON p.id_promotion = pa.promotion_assignment_id\n                    WHERE pa.pilot_assignment_id = :pilotId\n                    ORDER BY pa.assigned_at DESC\n                ");
                $stmt->execute(['pilotId' => $userId]);
                $allPromotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $currentPromo = $allPromotions[0] ?? null;
            $cancelUrl = $this->isSuperUser() ? '/admin/dashboard' : '/pilote/dashboard';

            echo $this->twig->render('auth/register_student.html.twig', [
                'current_promo' => $currentPromo,
                'all_promotions' => $allPromotions,
                'cancel_url' => $cancelUrl,
                'csrf_token' => Util::getCSRFToken()
            ]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Util::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->abort(403, "CSRF token invalid.");
            }

            $email = $_POST['email'] ?? '';
            $firstName = $_POST['first_name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $promoId = $_POST['promotion_id'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($email) || empty($firstName) || empty($lastName) || empty($promoId) || empty($password) || empty($confirmPassword)) {
                $this->abort(400, "Tous les champs sont obligatoires.");
            }

            if ($password !== $confirmPassword) {
                $this->abort(400, "Les mots de passe ne correspondent pas.");
            }

            if (strlen($password) < 8) {
                $this->abort(400, "Le mot de passe doit contenir au moins 8 caractères.");
            }

            $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);

            $user = new UserModel();
            $user->email = new Email($email);
            $user->password = $hashedPassword;
            $user->first_name = $firstName;
            $user->last_name = $lastName;
            $user->role = RoleEnum::Student;
            $user->is_active = true;

            $newUserId = $this->userRepository->push($user);

            if ($newUserId) {
                // makeStudent handles student-specific tables and enrollment
                $success = $this->userRepository->makeStudent($newUserId, $promoId, 'searching');

                if ($success) {
                    $_SESSION['flash_message'] = "L'étudiant $firstName $lastName a été créé.";
                    $_SESSION['flash_type'] = 'success';
                    header("Location: /dashboard/etudiants");
                    exit;
                }
            }
            $this->abort(500, "Une erreur est survenue lors de la création.");
        }
    }

    public function registerPilot(): void
    {
        if (!$this->isSuperUser()) {
            $this->abort(403, "Accès refusé. Seuls les administrateurs peuvent créer un pilote.");
        }

        $this->handleScopedRegistration(
            RoleEnum::Pilote,
            'Créer un compte Pilote',
            'Enregistrez un nouveau pilote pédagogique.',
            '/dashboard/pilotes/new',
            '/dashboard/pilotes'
        );
    }

    public function registerAdmin(): void
    {
        if (!$this->isSuperUser()) {
            $this->abort(403, "Accès refusé. Seuls les administrateurs peuvent créer un administrateur.");
        }

        $this->handleScopedRegistration(
            RoleEnum::Admin,
            'Créer un compte Administrateur',
            'Création réservée aux comptes administrateurs.',
            '/dashboard/admins/new',
            '/admin/dashboard'
        );
    }

    private function handleScopedRegistration(
        RoleEnum $targetRole,
        string $title,
        string $subtitle,
        string $action,
        string $successRedirect
    ): void {
        $error = null;
        $success = null;
        $old = $_POST;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Util::validateCSRFToken($_POST['csrf_token'] ?? '')) {
                $this->abort(403, "Invalid CSRF token.");
            }

            $emailRaw = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');

            if ($password !== $confirm) {
                $error = "Les mots de passe ne correspondent pas.";
            } elseif (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
                $error = "Format d'email invalide.";
            } elseif (empty($firstName) || empty($lastName)) {
                $error = "Le prénom et le nom sont requis.";
            } elseif ($this->userRepository->findByEmail($emailRaw)) {
                $error = "Cet email est déjà utilisé.";
            } else {
                try {
                    $user = new UserModel();
                    $user->email = new Email($emailRaw);
                    $user->password = password_hash($password, PASSWORD_ARGON2ID);
                    $user->role = $targetRole;
                    $user->first_name = $firstName;
                    $user->last_name = $lastName;
                    $user->is_active = true;
                    $user->created_at = date('Y-m-d H:i:s');

                    $this->userRepository->push($user);
                    $_SESSION['flash_message'] = "Compte créé avec succès.";
                    $_SESSION['flash_type'] = 'success';
                    header("Location: {$successRedirect}");
                    exit;
                } catch (\Throwable $e) {
                    error_log("Scoped registration error: " . $e->getMessage());
                    $error = "Une erreur technique est survenue.";
                }
            }
        }

        echo $this->twig->render('auth/register_scoped.html.twig', [
            'title' => $title,
            'subtitle' => $subtitle,
            'action' => $action,
            'role_label' => $targetRole->value,
            'error' => $error,
            'success' => $success,
            'old' => $old,
            'csrf_token' => Util::getCSRFToken(),
        ]);
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
                session_regenerate_id(true);
                
                $role = $user->role instanceof RoleEnum ? $user->role : RoleEnum::tryFrom((string)$user->role);
                
                Util::setCSRFToken(bin2hex(random_bytes(32)));
                Util::setUserId((string)$user->id);
                Util::setRole($role);
                Util::setUserData([
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email->asString(),
                    'role' => $role->value,
                ]);

                $this->handleRoleRedirection($role);
                return;
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
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
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
            RoleEnum::Student => '/profile',
            default => '/',
        };

        header("Location: $path");
        exit;
    }
}
