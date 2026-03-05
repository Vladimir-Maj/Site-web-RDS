<?php
/**
 * Path: /prod/account/upload_cv.php
 */

require_once __DIR__ . '/../.back/util/config.php';
require_once __DIR__ . '/../.back/repository/UserRepository.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// --- 1. SÉCURITÉ : Doit être connecté ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = null;
$success = null;

// --- 2. LOGIQUE D'UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cv_file'])) {
    $file = $_FILES['cv_file'];

    // Configuration CDN
    $cdnUploadDir = '/var/www/html/cdn/uploads/cvs/';
    if (!is_dir($cdnUploadDir)) {
        mkdir($cdnUploadDir, 0775, true);
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
    $safeName = preg_replace('/[^a-zA-Z0-9]/', '_', $originalName);

    // Nommage : ID_TIMESTAMP_NOM.EXT
    $newFileName = $_SESSION['user_id'] . '_' . time() . '_' . $safeName . '.' . $extension;
    $targetPath = $cdnUploadDir . $newFileName;

    if ($extension !== 'pdf') {
        $error = "Seul le format PDF est autorisé.";
    } elseif ($file['size'] > 2 * 1024 * 1024) {
        $error = "Le fichier dépasse la limite de 2 Mo.";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Erreur lors du transfert du fichier.";
    } else {
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            try {
                $dbUrl = CDN_URL . "/uploads/cvs/" . $newFileName;
                
                // On met à jour via le PDO global défini dans config.php
                $stmt = $pdo->prepare("UPDATE users SET cv_path = ? WHERE id = ?");
                $stmt->execute([$dbUrl, $_SESSION['user_id']]);
                
                $success = "Votre CV a été mis à jour avec succès.";
            } catch (PDOException $e) {
                $error = "Erreur base de données : " . $e->getMessage();
            }
        } else {
            $error = "Échec du transfert vers le stockage CDN. Vérifiez les permissions du dossier.";
        }
    }
}

// --- 3. RENDU TWIG ---
try {
    echo TwigFactory::getTwig()->render('account/upload_cv.html.twig', [
        'page_title' => "Mettre à jour mon CV — StageFlow",
        'error'      => $error,
        'success'    => $success
    ]);
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Une erreur technique est survenue.");
}