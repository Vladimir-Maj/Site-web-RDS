<?php
/**
 * SF Prosit - Recherche d'offres
 * Path: /prod/offers/offer_search.php
 */

require_once '../.back/util/config.php';
require_once '../.back/util/db_connect.php';
require_once '../.back/repository/OfferRepository.php';
require_once '../.back/repository/CompanyRepository.php';
require_once '../.back/util/OfferHelper.php';
require_once '../.back/util/OfferRenderer.php';

$offerRepo = new OfferRepository($pdo);
$companyRepo = new CompanyRepository($pdo);

// 1. Collecte des filtres depuis l'URL (GET)
$filters = [
    'keyword'     => $_GET['keyword'] ?? '',
    'location'    => $_GET['location'] ?? '',
    'job_type'    => $_GET['job_type'] ?? '',
    'company_id'  => $_GET['company_id'] ?? '', // Nouveau filtre
    'remote_type' => $_GET['remote_type'] ?? '',
    'sort'        => $_GET['sort'] ?? 'recent'
];

try {
    // Récupération des données pour alimenter les filtres
    $locations = $offerRepo->getAllLocations();
    $jobTypes  = $offerRepo->getAllJobTypes();
    $companies = $companyRepo->getAllCompanies(); // Liste ID/Nom pour le select

    // Exécution de la recherche dynamique
    $offers = $offerRepo->search($filters);
    $count = count($offers);
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Une erreur technique est survenue. Veuillez réessayer plus tard.");
}

// 2. Éléments d'interface
$basePath = __DIR__ . '/../../cdn/assets/elements/';
$headerHtml = @file_get_contents($basePath . 'header_template.html') ?: "";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Rechercher une offre — StageFlow</title>
    <link rel="stylesheet" href="<?= CDN_URL; ?>/styles.css"/>
</head>
<body>

<header><?= $headerHtml; ?></header>

<div class="page-wrapper">
    <main class="container">

        <div class="page-header" style="padding-top: 2rem;">
            <h1>Trouver votre opportunité</h1>
            <p>Utilisez les filtres ci-dessous pour affiner votre recherche.</p>
        </div>

        <section class="card" style="margin: 2rem 0; padding: 1.5rem; background: #f8fafc;">
            <form method="GET" action="offer_search.php">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.2rem;">
                    
                    <div class="form-group">
                        <label>Recherche libre</label>
                        <input type="text" name="keyword" placeholder="Poste, mots-clés..." value="<?= htmlspecialchars($filters['keyword']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Localisation</label>
                        <select name="location">
                            <option value="">Toutes les villes</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= htmlspecialchars($loc) ?>" <?= $filters['location'] === $loc ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($loc) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Entreprise</label>
                        <select name="company_id">
                            <option value="">Toutes les entreprises</option>
                            <?php foreach ($companies as $comp): ?>
                                <option value="<?= $comp['id'] ?>" <?= $filters['company_id'] == $comp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($comp['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Type de contrat</label>
                        <select name="job_type">
                            <option value="">Tous les types</option>
                            <?php foreach ($jobTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>" <?= $filters['job_type'] === $type ? 'selected' : '' ?>>
                                    <?= OfferHelper::formatJobType($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary" style="width: 100%; height: 42px;">
                            🔍 Filtrer
                        </button>
                    </div>
                </div>
                
                <div style="margin-top: 1rem; font-size: 0.85rem;">
                    <a href="offer_search.php" style="color: #64748b; text-decoration: none;">✕ Réinitialiser les filtres</a>
                </div>
            </form>
        </section>

        <div class="results-meta" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
            <p><strong><?= $count ?></strong> offre<?= $count > 1 ? 's' : '' ?> correspond<?= $count > 1 ? 'ent' : '' ?> à vos critères.</p>
            
            <div class="sort-select">
                <form method="GET" id="sortForm">
                    <input type="hidden" name="keyword" value="<?= htmlspecialchars($filters['keyword']) ?>">
                    <input type="hidden" name="location" value="<?= htmlspecialchars($filters['location']) ?>">
                    <input type="hidden" name="company_id" value="<?= htmlspecialchars($filters['company_id']) ?>">
                    
                    <select name="sort" onchange="this.form.submit()">
                        <option value="recent" <?= $filters['sort'] === 'recent' ? 'selected' : '' ?>>Plus récentes</option>
                        <option value="views" <?= $filters['sort'] === 'views' ? 'selected' : '' ?>>Plus consultées</option>
                        <option value="salary" <?= $filters['sort'] === 'salary' ? 'selected' : '' ?>>Meilleur salaire</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="offer-list" style="display: grid; gap: 1.5rem;">
            <?php if ($count > 0): ?>
                <?php foreach ($offers as $offer): ?>
                    <?= OfferRenderer::render($offer, 'std'); ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card" style="text-align: center; padding: 4rem; color: #64748b;">
                    <p style="font-size: 1.2rem;">Aucun résultat trouvé.</p>
                    <p>Essayez d'élargir vos critères de recherche.</p>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<script type="module" src="<?= CDN_URL; ?>/assets/scripts/load_head_foot.js"></script>
</body>
</html>