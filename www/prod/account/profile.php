<?php
/**
 * SF Prosit - User Profile
 * Path: /prod/profile.php
 */

require_once '../util/config.php';
require_once '../util/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sécurité : Redirection si non authentifié
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// --- 1. CONFIGURATION ---
$pageTitle = "Mon Profil — StageFlow";
$currentPage = "profile.php";

// --- 2. ELEMENT FETCHING ---
$basePath = __DIR__ . '/../cdn/assets/elements/';
$headerHtml = file_exists($basePath . 'header_template.html') ? file_get_contents($basePath . 'header_template.html') : "";
$footerHtml = file_exists($basePath . 'footer_template.html') ? file_get_contents($basePath . 'footer_template.html') : "";

// Set Active Link
$headerHtml = str_replace('data-page="' . $currentPage . '"', 'class="active"', $headerHtml);

// --- 3. DATA FETCH ---
try {
    $stmt = $pdo->prepare("SELECT username, email, role, cv_path, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
} catch (PDOException $e) {
    die("Erreur critique : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo $pageTitle; ?></title>

    <script>window.APP_CONFIG = { cdnUrl: "<?php echo CDN_URL; ?>" };</script>
    <link rel="icon" type="image/x-icon" href="<?php echo CDN_URL; ?>/favicon.ico">
    <link rel="stylesheet" href="<?php echo CDN_URL; ?>/styles.css">

    <style>

    </style>
</head>
<body>

<header><?php echo $headerHtml; ?></header>

<div class="page-wrapper">
    <main class="container">

        <div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-end; padding-top: 2rem;">
            <div>
                <h1>Paramètres du compte</h1>
                <p>Gérez vos informations personnelles et vos documents.</p>
            </div>
            <a href="logout.php" class="btn btn-outline" style="color: #dc2626; border-color: #fca5a5;">Déconnexion</a>
        </div>

        <div class="profile-layout">
            <aside>
                <div class="card user-card">
                    <div class="avatar-circle">
                        <?= strtoupper(substr($user['username'], 0, 1)) ?>
                    </div>
                    <h3 style="margin-bottom: 0.5rem;"><?= htmlspecialchars($user['username']) ?></h3>
                    <p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 1rem;"><?= htmlspecialchars($user['email']) ?></p>
                    <span class="tag <?= $user['role'] === 'recruiter' ? 'tag-amber' : 'tag-green' ?>">
                        <?= $user['role'] === 'recruiter' ? 'Recruteur' : 'Candidat' ?>
                    </span>
                </div>
            </aside>

            <section>
                <div class="card" style="padding: 2rem;">

                    <div class="info-section">
                        <h2>Informations générales</h2>
                        <div class="data-grid">
                            <div class="data-item">
                                <span class="data-label">Nom d'utilisateur</span>
                                <span class="data-value"><?= htmlspecialchars($user['username']) ?></span>
                            </div>
                            <div class="data-item">
                                <span class="data-label">Adresse e-mail</span>
                                <span class="data-value"><?= htmlspecialchars($user['email']) ?></span>
                            </div>
                            <div class="data-item">
                                <span class="data-label">Membre depuis le</span>
                                <span class="data-value"><?= date('d F Y', strtotime($user['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="info-section" style="margin-bottom: 0;">
                        <h2>Documents enregistrés</h2>
                        <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px; border: 1px dashed #d1d5db;">
                            <?php if ($user['cv_path']): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <p style="font-weight: 500; margin-bottom: 0.25rem;">Curriculum Vitae</p>
                                        <p style="font-size: 0.85rem; color: #6b7280;">Votre CV est visible par les recruteurs.</p>
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <a href="<?= htmlspecialchars($user['cv_path']) ?>" class="btn btn-outline" target="_blank">Consulter</a>
                                        <a href="upload_cv.php" class="btn btn-ghost">Remplacer</a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div style="text-align: center;">
                                    <p style="color: #6b7280; margin-bottom: 1rem;">Vous n'avez pas encore ajouté de CV.</p>
                                    <a href="upload_cv.php" class="btn btn-primary">Ajouter mon CV</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </section>
        </div>

    </main>
</div>

<footer><?php echo $footerHtml; ?></footer>

<script type="module" src="<?php echo CDN_URL; ?>/assets/scripts/load_head_foot.js"></script>
</body>
</html>