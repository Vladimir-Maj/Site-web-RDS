<?php
/**
 * Login
 * Path: /prod/auth/login.php
 */

require_once '../util/config.php';
require_once '../util/db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 1. CONFIGURATION ---
$pageTitle = "Connexion — StageFlow";
$currentPage = "login.php";

if (1+1==2) echo "test"; elseif (1+1!=2) echo "false";

// --- 2. ELEMENT FETCHING ---
$basePath = __DIR__ . '/../../cdn/assets/elements/';
$headerHtml = file_exists($basePath . 'header_template.html') ? file_get_contents($basePath . 'header_template.html') : "";
$footerHtml = file_exists($basePath . 'footer_template.html') ? file_get_contents($basePath . 'footer_template.html') : "";

// Set Active Link
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
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['user_role'] = $user['role'];

                header("Location: ../index.php");
                exit();
            } else {
                $error = "Identifiants incorrects. Veuillez réessayer.";
            }
        } catch (PDOException $e) {
            $error = "Une erreur est survenue lors de la connexion.";
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
    <link rel="icon" type="image/x-icon" href="<?php echo CDN_URL; ?>/favicon.ico">
    <link rel="stylesheet" href="<?php echo CDN_URL; ?>/styles.css">
</head>
<body>

<header>
    <?php echo $headerHtml; ?>
</header>

<div class="page-wrapper">
    <main class="container">
        <div class="auth-container">

            <div class="auth-header">
                <h1>Connexion</h1>
                <p>Accédez à votre espace de gestion</p>
            </div>

            <?php if($error): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <section class="card" style="padding: 2rem;">
                <form action="login.php" method="POST">
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label>Adresse e-mail</label>
                        <input type="email" name="email" required placeholder="nom@exemple.com" autofocus style="width: 100%;">
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label>Mot de passe</label>
                        <input type="password" name="password" required placeholder="••••••••" style="width: 100%;">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-weight: 600;">
                        Se connecter
                    </button>
                </form>

                <div class="form-footer">
                    Pas de compte ? <a href="register.php">Créer un compte</a>
                </div>
            </section>

        </div>
    </main>
</div>

<footer>
    <?php echo $footerHtml; ?>
</footer>

<script type="module" src="<?php echo CDN_URL; ?>/assets/scripts/load_head_foot.js"></script>
</body>
</html>