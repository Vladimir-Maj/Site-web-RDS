<?php
/**
 * Path: /prod/account/register.php
 */

require_once __DIR__ . '/../.back/util/config.php';
require_once __DIR__ . '/../.back/repository/UserRepository.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rediriger si déjà connecté
if (isset($_SESSION['id_user'])) {
    header("Location: ../index.php");
    exit();
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userRepo = new UserRepository($pdo);

    $email      = trim($_POST['email'] ?? '');
    $firstName  = trim($_POST['first_name'] ?? '');
    $lastName   = trim($_POST['last_name'] ?? '');
    $pass       = $_POST['password'] ?? '';
    $confPass   = $_POST['confirm_password'] ?? '';
    $role       = $_POST['role'] ?? 'student';

    if ($pass !== $confPass) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($pass) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif ($userRepo->exists($email)) {
        $error = "Cet email est déjà utilisé.";
    } else {
        $created = $userRepo->create([
            'email_user'      => $email,
            'first_name_user' => $firstName,
            'last_name_user'  => $lastName,
            'password'        => $pass,
            'role'            => $role
        ]);

        if ($created) {
            $success = "Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.";
        } else {
            $error = "Une erreur est survenue lors de l'inscription.";
        }
    }
}

try {
    echo TwigFactory::getTwig()->render('account/register.html.twig', [
        'page_title' => "Créer un compte — StageFlow",
        'error'      => $error,
        'success'    => $success,
        'old'        => $_POST
    ]);
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Erreur technique.");
}
