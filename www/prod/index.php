<?php
/**
 * SF Prosit - Dashboard (Refactored)
 * Path: /prod/index.php
 */

// 1. Load dependencies from the new directory structure
require_once '.back/util/config.php';
require_once '.back/util/db_connect.php';
require_once '.back/repository/OfferRepository.php';
require_once '.back/util/OfferHelper.php';
require_once '.back/util/OfferRenderer.php';

$pageTitle = "Catalogue des Offres - StageFlow";
$currentPage = basename(__FILE__);

// 2. Pagination Logic
$limit = 5; // Increased from 2 for better UX
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$repo = new OfferRepository($pdo);

try {
    $totalOffers = $repo->countAll();
    $totalPages = ceil($totalOffers / $limit);
    $offers = $repo->findPaginated($limit, $offset);
    
    $plural = $totalOffers > 1 ? 's' : '';
} catch (Exception $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// 3. Template Fetching (Header/Footer)
$basePath = __DIR__ . '/../cdn/assets/elements/';
$headerHtml = @file_get_contents($basePath . 'header_template.html') ?: "";
$footerHtml = @file_get_contents($basePath . 'footer_template.html') ?: "";

// Active link highlighting logic
$headerHtml = str_replace('data-page="' . $currentPage . '"', 'data-page="' . $currentPage . '" class="active"', $headerHtml);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= $pageTitle; ?></title>
    <script>window.APP_CONFIG = {cdnUrl: "<?= CDN_URL; ?>"};</script>
    <link rel="stylesheet" href="<?= CDN_URL; ?>/styles.css"/>
</head>
<body>

<header>
    <?= $headerHtml; ?>
</header>



<div class="page-wrapper">
    <main class="container">

        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-top: 2rem;">
            <div>
                <h1>Catalogue des Offres</h1>
                <p>Gestion centrale des opportunités de stage et d'emploi.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="offers/offer_search.php" class="btn btn-ghost">🔍 Rechercher</a>
                <a href="offers/offer_editor.php" class="btn btn-primary">+ Publier</a>
            </div>
        </div>

        <div class="notice notice-info">
            <p>Il y a actuellement <strong><?= $totalOffers; ?> offre<?= $plural; ?></strong> enregistrée<?= $plural; ?>.</p>
        </div>

        <div class="offer-list" style="display: grid; gap: 1.5rem; margin-top: 1rem;">
            <?php if (count($offers) > 0): ?>
                <?php foreach ($offers as $offer): ?>
                    <?= OfferRenderer::render($offer, 'std'); ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>Aucune offre disponible pour le moment.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination" style="display: flex; justify-content: center; gap: 15px; margin-top: 3rem; align-items: center;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="btn btn-ghost">← Précédent</a>
                <?php endif; ?>

                <span class="page-info">Page <strong><?= $page ?></strong> sur <?= $totalPages ?></span>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="btn btn-ghost">Suivant →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

<footer>
    <?= $footerHtml; ?>
</footer>

<script type="module" src="<?= CDN_URL; ?>/assets/scripts/load_head_foot.js"></script>
</body>
</html>