<?php
require_once 'util/config.php';
require_once 'util/db_connect.php';
require_once 'util/OfferRepository.php';

// Assuming these classes exist in your project
require_once 'util/OfferHelper.php';
require_once 'util/OfferRenderer.php';

$repo = new OfferRepository($pdo);

// 1. Logique des filtres
$filters = [
        'keyword'          => $_GET['keyword'] ?? '',
        'location'         => $_GET['location'] ?? '',
        'job_type'         => $_GET['job_type'] ?? '',
        'remote_type'      => $_GET['remote_type'] ?? '',
        'min_salary'       => $_GET['min_salary'] ?? '',
        'only_active'      => isset($_GET['only_active']),
        'sort'             => $_GET['sort'] ?? 'recent'
];

try {
    // On récupère les données pour les menus déroulants
    $locations = $repo->getAllLocations();
    $jobTypes = $repo->getAllJobTypes();

    // On exécute la recherche réelle
    $offers = $repo->search($filters);
    $count = count($offers);
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// 2. Chargement des templates (Comme sur ton index.php)
$basePath = __DIR__ . '/../cdn/assets/elements/';
$headerHtml = file_exists($basePath . 'header_template.html') ? file_get_contents($basePath . 'header_template.html') : "";
$cardTemplatePath = $basePath . 'card_template.html';



$cardTemplate = file_exists($cardTemplatePath) ? file_get_contents($cardTemplatePath) : "
<div class='card'>
    <h3>{{STAGE_NAME}}</h3>
    <p><strong>{{STAGE_COMPANY}}</strong> - {{STAGE_POSITION}}</p>
    <span class='tag {{STAGE_TAG_CLASS}}'>{{STAGE_STATUS}}</span>
</div>";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <title>Recherche d'offres</title>
    <link rel="stylesheet" href="<?php echo CDN_URL; ?>/styles.css"/>
</head>
<body>

<header><?php echo $headerHtml; ?></header>

<div class="page-wrapper">
    <main class="container">

        <section class="card" style="margin: 2rem 0; padding: 1.5rem;">
            <form method="GET" action="search.php">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <input type="text" name="keyword" placeholder="Mot-clé..." value="<?= htmlspecialchars($filters['keyword']) ?>">

                    <select name="location">
                        <option value="">Toutes les villes</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= $loc['location'] ?>" <?= $filters['location'] == $loc['location'] ? 'selected' : '' ?>><?= $loc['location'] ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
        </section>

        <div class="notice notice-info">
            <p><strong><?= $count ?></strong> offre(s) trouvée(s)</p>
        </div>

        <div class="offer-list" style="display: grid; gap: 1.5rem; margin-top: 1rem;">
            <?php
            $stateClasses = ['open' => 'tag-green', 'pending' => 'tag-amber', 'draft' => 'tag-slate'];

            if ($count > 0) {
                foreach ($offers as $offer) {
                    // On utilise EXACTEMENT la même logique que ton index.php
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
                echo "<p>Aucun résultat pour cette recherche.</p>";
            }
            ?>
        </div>
    </main>
</div>

</body>
</html>