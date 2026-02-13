<?php
/**
 * SF Prosit - User Registration
 */

require_once 'config.php';
require_once 'db_connect.php';

// --- 1. CONFIGURATION ---
$pageTitle = "Créer un compte — SF Prosit";
$currentPage = "register.php";

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

// --- 3. REGISTRATION LOGIC ---
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm_pw = $_POST['confirm_password'] ?? '';
    $role       = $_POST['role'] ?? 'candidate';

    // Basic Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = "Tous les champs obligatoires doivent être remplis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format d'email invalide.";
    } elseif ($password !== $confirm_pw) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 8) {
        $error = "Le mot de passe doit faire au moins 8 caractères.";
    } else {
        try {
            // Check if user already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);

            if ($stmt->fetch()) {
                $error = "Cet email ou ce nom d'utilisateur est déjà utilisé.";
            } else {
                // Hash the password securely
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
                $insert = $pdo->prepare($sql);

                if ($insert->execute([$username, $email, $hashedPassword, $role])) {
                    $success = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
                }
            }
        } catch (PDOException $e) {
            $error = "Erreur de base de données : " . $e->getMessage();
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
        .page-content { padding: 2rem 0; max-width: 500px; margin: 0 auto; }
        .alert { padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; border: 1px solid transparent; }
        .alert-error { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border-color: #c3e6cb; }
        .form-footer { margin-top: 1.5rem; text-align: center; font-size: 0.9rem; }
    </style>
</head>
<body>

<header><?php echo $headerHtml; ?></header>

<div class="page-wrapper">
    <main class="container page-content">
        <h1 style="text-align: center;"><?php echo $pageTitle; ?></h1>

        <?php if($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <section class="card" style="padding: 2rem;">
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label>Nom d'utilisateur</label>
                    <input type="text" name="username" required placeholder="ex: jdoe54">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="ex: jean.dupont@email.com">
                </div>

                <div class="form-group">
                    <label>Je suis un :</label>
                    <select name="role">
                        <option value="candidate">Candidat (recherche d'emploi)</option>
                        <option value="recruiter">Recruteur (publication d'offres)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" required minlength="8">
                </div>

                <div class="form-group">
                    <label>Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" required minlength="8">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                    S'inscrire
                </button>
            </form>

            <div class="form-footer">
                Déjà un compte ? <a href="login.php">Connectez-vous ici</a>.
            </div>
        </section>
    </main>
</div>

<footer></footer>
<script type="module" src="<?php echo CDN_URL; ?>/assets/scripts/load_head_foot.js"></script>
</body>
</html>