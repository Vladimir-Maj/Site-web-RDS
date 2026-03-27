<?php
/**
 * Login - Modular Version
 * Path: /prod/account/login.php
 */

require_once __DIR__ . '/../.back/util/config.php';
// Note: db_connect.php et les Repositories sont généralement inclus via config.php ou l'autoloader
require_once __DIR__ . '/../.back/repository/UserRepository.php';

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// Redirection si déjà connecté
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$error = null;

// --- 1. LOGIQUE DE CONNEXION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $userRepo = new UserRepository($pdo);
        // On suppose que authenticate() renvoie un objet User ou un tableau
        $user = $userRepo->authenticate($email, $password);

        if ($user) {
            // Adaptation selon que $user est un objet ou un array
            $_SESSION['user_id']   = is_array($user) ? $user['id'] : $user->id;
            $_SESSION['username']  = is_array($user) ? $user['username'] : $user->username;
            $_SESSION['user_role'] = is_array($user) ? $user['role'] : $user->role;
            
            // Régénération de l'ID de session pour prévenir la fixation de session
            session_regenerate_id(true);

            header("Location: ../index.php");
            exit();
        } else {
            $error = "Identifiants incorrects ou compte inexistant.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}

// --- 2. RENDU AVEC TWIG ---
// On utilise la même logique que ton search.php pour la cohérence
try {
    echo TwigFactory::getTwig()->render('account/login.html.twig', [
        'page_title'   => "Connexion — StageFlow",
        'current_page' => 'login',
        'error'        => $error,
        'last_email'   => $_POST['email'] ?? '' // Pour ne pas retaper l'email en cas d'erreur
    ]);
} catch (Exception $e) {
    error_log($e->getMessage());
    // Fallback minimaliste en cas d'erreur Twig
    die("Une erreur technique est survenue lors de l'affichage de la page.");
}