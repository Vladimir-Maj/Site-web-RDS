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
        if (!Util::isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        $error = null;
        $success = null;
        $userId = Util::getUserId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
                $this->abort(403, "Jeton CSRF invalide.");
            }

            $file = $_FILES['cv_file'] ?? null;

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $error = ($file && $file['error'] === UPLOAD_ERR_INI_SIZE)
                    ? "Le fichier dépasse la limite autorisée par le serveur."
                    : "Erreur lors de l'envoi du fichier.";
            } else {
                $allowedTypes = ['application/pdf'];
                $maxSize = 2 * 1024 * 1024;

                $realMimeType = mime_content_type($file['tmp_name']);

                if (!in_array($realMimeType, $allowedTypes)) {
                    $error = "Seuls les fichiers PDF sont autorisés.";
                } elseif ($file['size'] > $maxSize) {
                    $error = "Le fichier est trop lourd (max 2 Mo).";
                } else {
                    $baseDir = dirname(__DIR__, 2);
                    $uploadDir = $baseDir . '/../cdn/uploads/cvs/';
                    error_log("BaseDir" . $uploadDir);

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0775, true);
                    }

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
        $old = $_POST;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
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
                    $user->last_name = null;
                    $user->is_active = true;
                    $user->created_at = date('Y-m-d H:i:s');

                    $this->userRepository->push($user);

                    $success = "Votre compte a été créé ! Vous pouvez maintenant vous connecter.";
                    $old = [];
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

    private function setSession(UserModel $user): void
    {
        session_regenerate_id(true);

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
                ? $user->email->asString()
                : (string) $user->email,
            'role' => $user->role instanceof RoleEnum ? $user->role->value : $user->role,
        ]);
    }

    public function registerStudent(): void
    {
        $repo = new UserRepository($this->pdo);

        if (!$this->isPrivileged()) {
            $this->abort(403, "Accès refusé. Seuls les pilotes peuvent inscrire des étudiants.");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $pilotId = Util::getUserId();
            $currentPromo = $repo->getPromoByPilote($pilotId);

            echo $this->twig->render('auth/register_student.html.twig', [
                'current_promo' => $currentPromo,
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

            if (empty($email) || empty($firstName) || empty($lastName) || empty($promoId)) {
                $this->abort(400, "Tous les champs sont obligatoires.");
            }

            $tempPassword = bin2hex(random_bytes(4));
            $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);

            $user = new UserModel();
            $user->email = $email;
            $user->password = $hashedPassword;
            $user->first_name = $firstName;
            $user->last_name = $lastName;
            $user->role = RoleEnum::Student;
            $user->is_active = true;

            $newUserId = $repo->push($user);

            if ($newUserId) {
                $success = $repo->makeStudent($newUserId, $promoId, 'searching');

                if ($success) {
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
                            ? $user->email->asString()
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
