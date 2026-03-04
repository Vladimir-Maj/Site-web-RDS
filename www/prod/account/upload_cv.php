<?php
/**
 * SF Prosit - Upload CV (CDN Integrated)
 * Path: /prod/account/upload_cv.php
 */

require_once '../util/config.php';
require_once '../util/db_connect.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Sécurité
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// --- 1. CONFIGURATION ---
$pageTitle = "Mettre à jour mon CV — StageFlow";
$currentPage = "profile.php";

// --- 2. ELEMENT FETCHING (Fix pour l'erreur Undefined variable) ---
$basePath = __DIR__ . '/../../cdn/assets/elements/';
$headerPath = $basePath . 'header_template.html';
$footerPath = $basePath . 'footer_template.html';

$headerHtml = file_exists($headerPath) ? file_get_contents($headerPath) : "";
$footerHtml = file_exists($footerPath) ? file_get_contents($footerPath) : "";

// Set Active Link
$headerHtml = str_replace('data-page="' . $currentPage . '"', 'class="active"', $headerHtml);

// --- 3. UPLOAD LOGIC ---
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cv_file'])) {
    $file = $_FILES['cv_file'];

    // Configuration CDN
    $cdnUploadDir = '/var/www/html/cdn/uploads/cvs/';
    if (!is_dir($cdnUploadDir)) {
        mkdir($cdnUploadDir, 0775, true);
    }

    $originalName = pathinfo($file['name'], PATHINFO_FILENAME);

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safeName = preg_replace('/[^a-zA-Z0-9]/', '_', $originalName);

    // Nommage : UUID_TIMESTAMP_NOM.EXT
    $newFileName = $_SESSION['user_id'] . '_' . time() . '_' . $safeName . '.' . $extension;
    $targetPath = $cdnUploadDir . $newFileName;

    if ($extension !== 'pdf') {
        $error = "Seul le format PDF est autorisé.";
    } elseif ($file['size'] > 2 * 1024 * 1024) {
        $error = "Le fichier dépasse la limite de 2 Mo.";
    } else {
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            try {
                $dbUrl = CDN_URL . "/uploads/cvs/" . $newFileName;
                $stmt = $pdo->prepare("UPDATE users SET cv_path = ? WHERE id = ?");
                $stmt->execute([$dbUrl, $_SESSION['user_id']]);
                $message = "Votre CV a été mis à jour avec succès.";
            } catch (PDOException $e) {
                $error = "Erreur base de données : " . $e->getMessage();
            }
        } else {
            $error = "Échec du transfert vers le stockage CDN.";
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
    <link rel="stylesheet" href="<?php echo CDN_URL; ?>/styles.css">
    <style>
        .upload-container { max-width: 600px; margin: 4rem auto; }
        .drop-zone {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 3rem 2rem;
            text-align: center;
            background: #f9fafb;
            cursor: pointer;
            display: block;
            transition: all 0.2s;
        }
        .drop-zone:hover { border-color: var(--primary-color, #2563eb); background: #f0f7ff; }
        .status-alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid; }
        .status-success { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
        .status-error { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
    </style>
    <script>
        // Applique le mode sombre immédiatement pour éviter le flash blanc
        if (localStorage.getItem('theme') === 'dark' ||
            (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body>

<header><?php echo $headerHtml; ?></header>

<div class="page-wrapper">
    <main class="container">
        <div class="upload-container">
            <div style="margin-bottom: 2rem;">
                <a href="profile.php" style="text-decoration: none; font-size: 0.9rem; color: #6b7280;">← Retour au profil</a>
                <h1 style="margin-top: 1rem;">Mettre à jour mon CV</h1>
            </div>

            <?php if($message): ?>
                <div class="status-alert status-success"><strong>Succès :</strong> <?= $message ?></div>
                <a href="profile.php" class="btn btn-primary" style="display: block; text-align: center;">Retour au profil</a>
            <?php else: ?>
                <?php if($error): ?>
                    <div class="status-alert status-error"><strong>Erreur :</strong> <?= $error ?></div>
                <?php endif; ?>

                <form action="upload_cv.php" method="POST" enctype="multipart/form-data">
                    <label class="drop-zone">
                        <span style="font-size: 2rem; display: block; margin-bottom: 1rem;">📄</span>
                        <strong>Cliquez pour choisir un PDF</strong>
                        <p style="font-size: 0.85rem; color: #6b7280; margin-top: 0.5rem;">Maximum 2 Mo</p>
                        <input type="file" name="cv_file" accept=".pdf" required style="display: none;" onchange="this.form.submit()">
                    </label>
                </form>
            <?php endif; ?>
        </div>
    </main>
</div>

<footer><?php echo $footerHtml; ?></footer>

<script>
    const dropZone = document.querySelector('.drop-zone');
    const fileInput = document.querySelector('input[name="cv_file"]');
    const form = document.querySelector('form');

    // Empêcher le comportement par défaut du navigateur (ouvrir le PDF)
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, e => {
            e.preventDefault();
            e.stopPropagation();
        }, false);
    });

    // Ajouter/Retirer la classe visuelle lors du survol
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.add('drag-over');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.remove('drag-over');
        }, false);
    });

    // Gérer le dépôt du fichier
    dropZone.addEventListener('drop', e => {
        const dt = e.dataTransfer;
        const files = dt.files;

        if (files.length > 0) {
            // On vérifie si c'est bien un PDF
            if (files[0].type === 'application/pdf') {
                fileInput.files = files; // On injecte le fichier dans l'input
                form.submit();           // Soumission automatique
            } else {
                alert("Seuls les fichiers PDF sont acceptés.");
            }
        }
    }, false);
</script>

</body>
</html>