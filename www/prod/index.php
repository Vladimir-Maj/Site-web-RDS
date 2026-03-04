<?php
/**
 * SF Prosit - Dashboard
 * Path: /prod/index.php
 */

require_once 'util/config.php';
require_once 'util/db_connect.php';
require_once 'util/OfferRepository.php';

$pageTitle = "Catalogue des Offres - StageFlow";
$currentPage = basename(__FILE__);

$limit = 2;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$repo = new OfferRepository($pdo);

try {
    $totalOffers = $repo->countAll();
    $count = $totalOffers;
    $totalPages = ceil($totalOffers / $limit);

    // Récupération des offres pour la page actuelle
    $offers = $repo->findPaginated($limit, $offset);

    $countOnPage = count($offers);
    $plural = $totalOffers > 1 ? 's' : '';
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// --- 3. ELEMENT FETCHING ---
$basePath = __DIR__ . '/../cdn/assets/elements/';
$headerHtml = file_exists($basePath . 'header_template.html') ? file_get_contents($basePath . 'header_template.html') : "";
$footerHtml = file_exists($basePath . 'footer_template.html') ? file_get_contents($basePath . 'footer_template.html') : "";
$cardTemplatePath = $basePath . 'card_template.html';

$headerHtml = str_replace('data-page="' . $currentPage . '"', 'data-page="' . $currentPage . '" class="active"', $headerHtml);

$cardTemplate = file_exists($cardTemplatePath) ? file_get_contents($cardTemplatePath) : "
<div class='card'>
    <h3>{{STAGE_NAME}}</h3>
    <p><strong>{{STAGE_COMPANY}}</strong> - {{STAGE_POSITION}}</p>
    <p>{{STAGE_DESC}}</p>
    <span class='tag {{STAGE_TAG_CLASS}}'>{{STAGE_STATUS}}</span>
</div>";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo $pageTitle; ?></title>
    <script>window.APP_CONFIG = {cdnUrl: "<?php echo CDN_URL; ?>"};</script>
    <link rel="stylesheet" href="<?php echo CDN_URL; ?>/styles.css"/>
</head>
<body>

<header>
    <?php echo $headerHtml; ?>
</header>

<div class="page-wrapper">
    <main class="container">

        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-top: 2rem;">
            <div>
                <h1>Catalogue des Offres</h1>
                <p>Gestion centrale des opportunités.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="search.php" class="btn btn-ghost">🔍 Rechercher</a>
                <a href="offers/offer_editor.php" class="btn btn-primary">+ Publier</a>
            </div>
        </div>

        <div class="notice notice-info">
            <p>Il y a actuellement <strong><?= $count; ?> offre<?= $plural; ?></strong> enregistrée<?= $plural; ?>.</p>
        </div>

        <div class="offer-list" style="display: grid; gap: 1.5rem; margin-top: 1rem;">
            <?php
            $stateClasses = ['open' => 'tag-green', 'pending' => 'tag-amber', 'draft' => 'tag-slate'];

            if ($countOnPage > 0) {
                foreach ($offers as $offer) {
                    $replacements = [
                            '{{STAGE_ID}}'        => $offer['id'],
                            '{{STAGE_NAME}}'      => htmlspecialchars($offer['position'] ?? $offer['title'] ?? 'Sans titre'),
                            '{{STAGE_POSITION}}'  => htmlspecialchars($offer['location'] ?? 'N/C'),
                            '{{STAGE_COMPANY}}'   => htmlspecialchars($offer['company_name'] ?? 'Inconnue'),
                            '{{STAGE_DATE}}'      => isset($offer['created_at']) ? date('d/m/Y', strtotime($offer['created_at'])) : '--/--/----',
                            '{{STAGE_DESC}}'      => mb_strimwidth(htmlspecialchars($offer['description'] ?? ''), 0, 150, "..."),
                            '{{STAGE_STATUS}}'    => strtoupper($offer['state'] ?? 'UNKNOWN'),
                            '{{STAGE_TAG_CLASS}}' => $stateClasses[$offer['state'] ?? ''] ?? 'tag-slate'
                    ];
                    echo strtr($cardTemplate, $replacements);
                }
            } else {
                echo "<p>Aucune offre disponible sur cette page.</p>";
            }
            ?>
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
    <?php echo $footerHtml; ?>
</footer>

<script type="module" src="<?php echo CDN_URL; ?>/assets/scripts/load_head_foot.js"></script>
</body>
</html>