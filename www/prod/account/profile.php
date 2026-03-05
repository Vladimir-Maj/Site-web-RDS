<?php
/**
 * Path: /prod/account/profile.php
 */

require_once __DIR__ . '/../.back/util/config.php';
require_once __DIR__ . '/../.back/repository/UserRepository.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Protection de la page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userRepo = new UserRepository($pdo);
$user = $userRepo->findById($_SESSION['user_id']);

// Si l'utilisateur n'existe plus en base (cas rare mais possible)
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Rendu Twig
try {
    echo TwigFactory::getTwig()->render('account/profile.html.twig', [
        'page_title'   => "Mon Profil — StageFlow",
        'current_page' => 'profile',
        'user'         => $user
    ]);
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Une erreur technique est survenue.");
}