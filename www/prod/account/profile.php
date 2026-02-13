<?php
/**
 * SF Prosit - User Profile
 */

require_once '../util/config.php';
require_once '../util/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// --- 1. CONFIGURATION ---
$pageTitle = "Mon Profil — SF Prosit";
$currentPage = "profile.php";

// --- 2. HEADER FETCH ---
$localHeaderPath = '/var/www/html/cdn/assets/elements/header_template.html';
$headerUrl = CDN_URL . "/assets/elements/header.html";
$headerHtml = "";
if (file_exists($localHeaderPath)) {
    $headerHtml = file_get_contents($localHeaderPath);
} else {
    $context = stream_context_create(["ssl" => ["verify_peer"=>false, "verify_peer_name"=>false]]);
    $headerHtml = @file_get_contents($headerUrl, false, $context) ?: "";
}

$localFooterPath = '/var/www/html/cdn/assets/elements/footer_template.html';
$footerUrl = CDN_URL . "/assets/elements/footer_template.html";
$footerHtml = "";
$headerHtml = str_replace('data-page="' . $currentPage . '"', 'data-page="' . $currentPage . '" class="active"', $headerHtml);
$footerHtml = str_replace('data-page="' . $currentPage . '"', '', $footerHtml);

// --- 3. DATA FETCH ---
try {
    $stmt = $pdo->prepare("SELECT username, email, role, cv_path, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        // Should not happen if session is valid, but safe to check
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
    <link rel="stylesheet" href="<?php echo CDN_URL; ?>/styles.css">
    <style>
        .profile-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; }
        .user-badge { text-align: center; padding: 2rem; }
        .user-badge .avatar {
            font-size: 3rem;
            background: var(--clr-bg);
            display: inline-block;
            width: 80px; height: 80px;
            line-height: 80px;
            border-radius: 50%;
            border: 2px solid var(--clr-border);
            margin-bottom: 1rem;
        }
        .data-row { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px dashed var(--clr-border-light); }
        .data-row:last-child { border-bottom: none; }
        .label { font-weight: bold; color: var(--clr-text); font-size: 0.8rem; }
        .value { color: var(--clr-text-muted); font-family: var(--font); }
    </style>
</head>
<body>

<header><?php echo $headerHtml; ?></header>

<div class="page-wrapper">
    <main class="container">
        <div class="page-header">
            <h1>> USER_PROFILE: <?= htmlspecialchars($user['username']) ?></h1>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>

        <div class="profile-grid">
            <aside>
                <div class="card user-badge">
                    <div class="avatar">👤</div>
                    <h3><?= htmlspecialchars($user['username']) ?></h3>
                    <span class="tag <?= $user['role'] === 'recruiter' ? 'tag-amber' : 'tag-green' ?>">
                        <?= strtoupper($user['role']) ?>
                    </span>
                </div>
            </aside>

            <section>
                <div class="legal-section">
                    <div class="legal-section-header">
                        <h2>Legals</h2>
                    </div>
                    <div class="legal-section-body">
                        <div class="data-row">
                            <span class="label">EMAIL</span>
                            <span class="value"><?= htmlspecialchars($user['email']) ?></span>
                        </div>
                        <div class="data-row">
                            <span class="label">DATE_INSCRIPTION</span>
                            <span class="value"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                        </div>
                        <div class="data-row">
                            <span class="label">UUID_INTERNAL</span>
                            <span class="value">#<?= $_SESSION['user_id'] ?></span>
                        </div>
                    </div>
                </div>

                <div class="legal-section mt-2">
                    <div class="legal-section-header">
                        <h2>DOCUMENTS_JOINT</h2>
                    </div>
                    <div class="legal-section-body">
                        <?php if ($user['cv_path']): ?>
                            <p>Un CV est actuellement enregistré dans votre profil.</p>
                            <div class="form-actions" style="justify-content: flex-start; margin-top: 1rem;">
                                <a href="<?= htmlspecialchars($user['cv_path']) ?>" class="btn btn-outline" target="_blank">VOIR_MON_CV</a>
                                <a href="upload_cv.php" class="btn btn-ghost">METTRE_A_JOUR</a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Aucun CV n'a été détecté pour cet utilisateur.</p>
                            <a href="upload_cv.php" class="btn btn-primary mt-1">TELEVERSER_CV</a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>
<script type="module" src="<?php echo CDN_URL; ?>/assets/scripts/load_head_foot.js"></script>

<footer> <?php echo $footerHtml?></footer>
</body>
</html>