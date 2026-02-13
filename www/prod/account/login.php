<?php
/**
 * SF Prosit - User Login
 */

require_once '../util/config.php';
require_once '../util/db_connect.php';

// Ensure session is started for all pages via config or here
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 1. CONFIGURATION ---
$pageTitle = "Connexion — SF Prosit";
$currentPage = "login.php";

// --- 2. HEADER FETCH ---
$localHeaderPath = '/var/www/html/cdn/assets/elements/header.html';
$headerUrl = CDN_URL . "/assets/elements/header.html";
$headerHtml = "";
if (file_exists($localHeaderPath)) {
    $headerHtml = file_get_contents($localHeaderPath);
} else {
    $context = stream_context_create(["ssl" => ["verify_peer"=>false, "verify_peer_name"=>false]]);
    $headerHtml = @file_get_contents($headerUrl, false, $context) ?: "";
}
$headerHtml = str_replace('data-page="' . $currentPage . '"', 'data-page="' . $currentPage . '" class="active"', $headerHtml);

// --- 3. LOGIN LOGIC ---
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Password is correct!
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['user_role'] = $user['role'];

                // Redirect to dashboard or search
                header("Location: ../search.php");
                exit();
            } else {
                $error = "Identifiants invalides.";
            }
        } catch (PDOException $e) {
            $error = "Erreur système : " . $e->getMessage();
        }
    }
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
        .page-content { padding: 4rem 0; max-width: 400px; margin: 0 auto; }
        .auth-card { padding: 2rem; border-radius: var(--border-radius); }
        .alert-error {
            background: var(--clr-bg);
            border: 1px solid var(--clr-danger);
            color: var(--clr-danger);
            padding: 0.8rem;
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<header><?php echo $headerHtml; ?></header>

<div class="page-wrapper">
    <main class="container page-content">
        <h1 style="margin-bottom: 1.5rem; text-align: center;">> AUTH_REQUIRED</h1>

        <?php if($error): ?>
            <div class="alert-error">
                [ERROR]: <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <section class="card auth-card">
            <form action="login.php" method="POST">
                <div class="form-group mb-2">
                    <label>EMAIL_ADDRESS</label>
                    <input type="email" name="email" required placeholder="user@domain.com" autofocus>
                </div>

                <div class="form-group mb-2">
                    <label>PASSWORD</label>
                    <input type="password" name="password" required placeholder="********">
                </div>

                <button type="submit" class="btn btn-primary mt-2" style="width: 100%;">
                    EXECUTE_LOGIN
                </button>
            </form>

            <div style="margin-top: 1.5rem; text-align: center; font-size: 0.8rem;">
                <p>Nouveau ici ? <a href="register.php">CRÉER_COMPTE</a></p>
            </div>
        </section>
    </main>
</div>

<footer>
    <div class="footer-inner">
        <span>© 2026 StageFlow.TUI</span>
        <a href="index.php">RET_HOME</a>
    </div>
</footer>

<script type="module" src="<?php echo CDN_URL; ?>/assets/scripts/load_head_foot.js"></script>
</body>
</html>