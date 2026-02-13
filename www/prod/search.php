<?php
// 1. Core Configuration & Logic
require_once 'util/config.php';
require_once 'util/db_connect.php';
require_once 'util/OfferRepository.php';

// Assuming these classes exist in your project
// require_once 'OfferHelper.php';
// require_once 'OfferRenderer.php';

$repo = new OfferRepository($pdo);
$error = null;

// Filter handling logic
$filters = [
        'keyword'          => $_GET['keyword'] ?? '',
        'location'         => $_GET['location'] ?? '',
        'job_type'         => $_GET['job_type'] ?? '',
        'experience_level' => $_GET['experience_level'] ?? '',
        'remote_type'      => $_GET['remote_type'] ?? '',
        'min_salary'       => $_GET['min_salary'] ?? '',
        'only_active'      => isset($_GET['only_active']),
        'sort'             => $_GET['sort'] ?? 'recent'
];

try {
    // You would normally call your repo here:
    // $offers = $repo->search($filters);
    // $count = count($offers);

    // Placeholders for variables used in the HTML below
    $locations = $locations ?? [];
    $jobTypes = $jobTypes ?? [];
    $expLevels = ['junior', 'mid', 'senior', 'lead'];
    $offers = $offers ?? [];
    $count = $count ?? 0;

} catch (Exception $e) {
    $error = $e->getMessage();
}

// 2. Fetch Header from CDN (Server-Side)
$headerUrl = CDN_URL . "/assets/elements/header.html";
$localHeaderPath = '/var/www/html/cdn/assets/elements/header.html';

$context = stream_context_create([
        "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
        ],
]);

// Attempt to load locally first (fastest/most reliable in Docker)
if (file_exists($localHeaderPath)) {
    $headerHtml = file_get_contents($localHeaderPath);
} else {
    // Fallback to URL if local path isn't mapped
    $headerHtml = @file_get_contents($headerUrl, false, $context) ?: "";
}

// If both failed, we ensure $headerHtml is at least an empty string to avoid errors
if ($headerHtml === false) { $headerHtml = ""; }

// Highlight the current page in navigation
$headerHtml = str_replace('data-page="search.php"', 'data-page="search.php" class="active"', $headerHtml);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Trouver une offre — SF Prosit</title>

    <script>
        window.APP_CONFIG = { cdnUrl: "<?php echo CDN_URL; ?>" };
    </script>

    <link rel="icon" type="image/x-icon" href="<?php echo CDN_URL; ?>/favicon.ico">
    <link rel="stylesheet" href="<?php echo CDN_URL; ?>/styles.css"/>

    <style>
        .form-section { border-top: 1px solid #eee; margin-top: 20px; padding-top: 20px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .label-group { font-weight: bold; color: var(--clr-primary); font-size: 0.9rem; text-transform: uppercase; margin-bottom: 10px; display: block; }
    </style>
</head>

<body>

<header>
    <?php echo $headerHtml; ?>
</header>

<div class="page-wrapper">
    <main>
        <div class="page-header">
            <h1>Trouver une offre</h1>
            <a href="editor.php" class="btn btn-primary">+ Publier</a>
        </div>

        <section class="card" style="margin-bottom: 2rem; padding: 1.5rem;">
            <form method="GET" action="search.php" class="filter-form">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">

                    <div class="form-group">
                        <label>Mot-clé</label>
                        <input type="text" name="keyword" placeholder="Poste, techno..." value="<?= htmlspecialchars($filters['keyword']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Ville</label>
                        <select name="location">
                            <option value="">Toutes les villes</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= htmlspecialchars($loc['location']) ?>" <?= $filters['location'] === $loc['location'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($loc['location']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Type de contrat</label>
                        <select name="job_type">
                            <option value="">Tous types</option>
                            <?php foreach ($jobTypes as $jt): ?>
                                <option value="<?= htmlspecialchars($jt['job_type']) ?>" <?= $filters['job_type'] === $jt['job_type'] ? 'selected' : '' ?>>
                                    <?= class_exists('OfferHelper') ? OfferHelper::formatJobType($jt['job_type']) : $jt['job_type'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="only_active" value="1" <?= $filters['only_active'] ? 'checked' : '' ?>>
                        Offres actives uniquement
                    </label>

                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button type="submit" class="btn btn-primary">Filtrer</button>
                        <a href="search.php" class="btn btn-ghost">Réinitialiser</a>
                    </div>
                </div>
            </form>
        </section>

        <div class="results-meta" style="margin-bottom: 1rem;">
            <p><strong><?= $count ?></strong> offre<?= $count > 1 ? 's' : '' ?> trouvée<?= $count > 1 ? 's' : '' ?></p>
        </div>

        <div class="offer-list">
            <?php if ($count > 0): ?>
                <?php foreach ($offers as $offer): ?>
                    <?= class_exists('OfferRenderer') ? OfferRenderer::render($offer, 'rich') : '<div class="card">Offer Row</div>'; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card" style="padding: 4rem 2rem; text-align: center; border: 2px dashed #ddd; background: transparent;">
                    <p style="font-size: 1.2rem; color: #666;">Aucun résultat ne correspond à votre recherche.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<footer></footer>

<script type="module" src="<?php echo CDN_URL; ?>/assets/scripts/load_head_foot.js"></script>

</body>
</html>