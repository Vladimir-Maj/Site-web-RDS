<?php
/**
 * SF Prosit - Page Template
 */

require_once 'util/config.php';
// require_once 'util/db_connect.php';

$pageTitle = "Nom de la Page - SF Prosit";
$currentPage = basename(__FILE__);

$basePath = __DIR__ . '/../cdn/assets/elements/';
$headerPath = $basePath . 'header_template.html';
$footerPath = $basePath . 'footer_template.html';

$headerHtml = file_exists($headerPath) ? file_get_contents($headerPath) : "";
$footerHtml = file_exists($footerPath) ? file_get_contents($footerPath) : "";

$headerHtml = str_replace('data-page="' . $currentPage . '"', 'data-page="' . $currentPage . '" class="active"', $headerHtml);
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
</head>
<body>

<header>
    <?php echo $headerHtml; ?>
</header>

<div class="page-wrapper">
    <main class="container page-content">
        <h1><?php echo $pageTitle; ?></h1>
        <section class="card">
            <p>Contenu ici...</p>
        </section>
    </main>
</div>

<footer>
    <?php echo $footerHtml; ?>
</footer>

<script type="module" src="<?php echo CDN_URL; ?>/assets/scripts/load_head_foot.js"></script>
</body>
</html>