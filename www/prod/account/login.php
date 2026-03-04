<?php
/**
 * Login - Modular Version
 * Path: /prod/account/login.php
 */

require_once '../.back/util/config.php';
require_once '../.back/util/db_connect.php';
require_once '../.back/repository/UserRepository.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$pageTitle = "Connexion — StageFlow";
$currentPage = "login.php";

// --- 1. ELEMENT FETCHING ---
$basePath = __DIR__ . '/../../cdn/assets/elements/';
$headerHtml = @file_get_contents($basePath . 'header_template.html') ?: "";
$footerHtml = @file_get_contents($basePath . 'footer_template.html') ?: "";
$headerHtml = str_replace('data-page="' . $currentPage . '"', 'class="active"', $headerHtml);

// --- 2. LOGIN LOGIC ---
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userRepo = new UserRepository($pdo);
    $user = $userRepo->authenticate($_POST['email'] ?? '', $_POST['password'] ?? '');

    if ($user) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        header("Location: ../index.php");
        exit();
    } else {
        $error = "Identifiants incorrects.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="<?= CDN_URL; ?>/styles.css">
</head>
<body>
    <header><?= $headerHtml ?></header>
    <main class="container">
        <div class="auth-container">
            <h1>Connexion</h1>
            <?php if($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            
            <section class="card">
                <form method="POST">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required autofocus>
                    </div>
                    <div class="form-group">
                        <label>Mot de passe</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Se connecter</button>
                </form>
                <div class="form-footer">Pas de compte ? <a href="register.php">S'inscrire</a></div>
            </section>
        </div>
    </main>
    <footer><?= $footerHtml ?></footer>
</body>
</html>