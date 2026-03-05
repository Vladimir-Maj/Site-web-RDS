<?php
/**
 * Path: /prod/account/register.php
 */

require_once __DIR__ . '/../.back/util/config.php';
require_once __DIR__ . '/../.back/repository/UserRepository.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Rediriger si déjà connecté
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userRepo = new UserRepository($pdo);
    
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $confPass = $_POST['confirm_password'] ?? '';
    $role     = $_POST['role'] ?? 'candidate';

    // Validation simple
    if ($pass !== $confPass) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($pass) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif ($userRepo->exists($email, $username)) {
        $error = "Cet email ou ce pseudonyme est déjà utilisé.";
    } else {
        // Tentative de création
        $created = $userRepo->create([
            'username' => $username,
            'email'    => $email,
            'password' => $pass, // Le repository doit hasher le mot de passe !
            'role'     => $role
        ]);

        if ($created) {
            $success = "Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.";
        } else {
            $error = "Une erreur est survenue lors de l'inscription.";
        }
    }
}

// Rendu Twig
try {
    echo TwigFactory::getTwig()->render('account/register.html.twig', [
        'page_title' => "Créer un compte — StageFlow",
        'error'      => $error,
        'success'    => $success,
        'old'        => $_POST // Permet de pré-remplir les champs en cas d'erreur
    ]);
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Erreur technique.");
}