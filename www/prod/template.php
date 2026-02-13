<?php
/**
 * SF Prosit - Page Template
 * Use this as a starting point for all new pages.
 */

require_once 'config.php';
// require_once 'db_connect.php'; // Uncomment when DB is needed

// --- 1. CONFIGURATION ---
$pageTitle = "Page Title — SF Prosit";
$currentPage = basename(__FILE__); // Automatically detects filename (e.g., 'search.php')

// --- 2. HEADER FETCH LOGIC ---
// We try the local filesystem first (Docker speed), then fallback to URL
$localHeaderPath = '/var/www/html/cdn/assets/elements/header.html';
$headerUrl = CDN_URL . "/assets/elements/header.html";

$headerHtml = "";
if (file_exists($localHeaderPath)) {
    $headerHtml = file_get_contents($localHeaderPath);
} else {
    $context = stream_context_create(["ssl" => ["verify_peer"=>false, "verify_peer_name"=>false]]);
    $headerHtml = @file_get_contents($headerUrl, false, $context) ?: "";
}

// Set Active Navigation Link
$headerHtml = str_replace('data-page="' . $currentPage . '"', 'data-page="' . $currentPage . '" class="active"', $headerHtml);

// --- 3. PAGE LOGIC ---
// Your PHP logic/queries go here
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo $pageTitle; ?></title>

    <script>
        window.APP_CONFIG = { cdnUrl: "<?php echo CDN_URL; ?>" };
    </script>

    <link rel="icon" type="image/x-icon" href="<?php echo CDN_URL; ?>/favicon.ico">
    <link rel="stylesheet" href="<?php echo CDN_URL; ?>/styles.css">

    <style>
        /* Page-specific styles */
        .page-content { padding: 2rem 0; }
    </style>
</head>
<body>

<header>
    <?php echo $headerHtml; ?>
</header>

<div class="page-wrapper">
    <main class="container page-content">
        <h1><?php echo $pageTitle; ?></h1>

        <section class="card">
            <p>Votre contenu commence ici...</p>
        </section>
    </main>
</div>

<footer></footer>

<script type="module" src="<?php echo CDN_URL; ?>/assets/scripts/load_head_foot.js"></script>
</body>
</html>