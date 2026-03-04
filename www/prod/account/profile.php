<?php
/**
 * SF Prosit - User Profile
 * Path: /prod/account/profile.php
 */

require_once '../.back/util/config.php';
require_once '../.back/util/db_connect.php';
require_once '../.back/repository/UserRepository.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userRepo = new UserRepository($pdo);
$user = $userRepo->findById($_SESSION['user_id']);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$pageTitle = "Mon Profil — StageFlow";
$basePath = __DIR__ . '/../../cdn/assets/elements/';
$headerHtml = @file_get_contents($basePath . 'header_template.html') ?: "";
$footerHtml = @file_get_contents($basePath . 'footer_template.html') ?: "";
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
    <div class="page-header">
        <h1>Mon compte</h1>
        <a href="logout.php" class="btn btn-outline">Déconnexion</a>
    </div>

    <div class="profile-layout" style="display: flex; gap: 2rem;">
        <aside style="flex: 1;">
            <div class="card">
                <div class="avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
                <h3><?= htmlspecialchars($user['username']) ?></h3>
                <p><?= htmlspecialchars($user['email']) ?></p>
                <span class="tag"><?= ucfirst($user['role']) ?></span>
            </div>
        </aside>

        <section style="flex: 2;">
            <div class="card">
                <h2>Informations</h2>
                <p>Membre depuis : <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
                
                <hr>
                
                <h2>CV / Documents</h2>
                <?php if ($user['cv_path']): ?>
                    <a href="<?= htmlspecialchars($user['cv_path']) ?>" class="btn btn-primary" target="_blank">Voir mon CV</a>
                    <a href="upload_cv.php" class="btn btn-ghost">Changer</a>
                <?php else: ?>
                    <p>Aucun CV enregistré.</p>
                    <a href="upload_cv.php" class="btn btn-primary">Ajouter un CV</a>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>
<footer><?= $footerHtml ?></footer>
</body>
</html>