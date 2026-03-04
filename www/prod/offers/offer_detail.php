<?php
/**
 * SF Prosit - Offer Details
 * Path: /prod/offers/offer_detail.php
 */

require_once '../.back/util/config.php';
require_once '../.back/util/db_connect.php';
require_once '../.back/repository/OfferRepository.php';
require_once '../.back/util/OfferHelper.php';

$repo = new OfferRepository($pdo);
$offerId = $_GET['id'] ?? $_GET['offer_id'] ?? null; // Support both naming conventions
$offer = null;

if ($offerId) {
    // findById should return the join with 'companies' to get company_name and bio
    $offer = $repo->findById((int)$offerId);
    if ($offer) {
        $repo->incrementViews((int)$offerId);
    }
}

// Redirect if not found
if (!$offer) {
    header("Location: ../index.php");
    exit();
}

// Prepare display values using our Helper
$displayTitle = !empty($offer['position']) ? $offer['position'] : ($offer['title'] ?? 'Sans titre');
$salaryText = OfferHelper::formatSalary($offer['salary_min'], $offer['salary_max'], $offer['salary_currency'] ?? 'EUR');
$skills = OfferHelper::parseSkills($offer['required_skills'] ?? '');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= htmlspecialchars($displayTitle) ?> — StageFlow</title>
    <link rel="stylesheet" href="<?= CDN_URL; ?>/styles.css"/>
    <style>
        .detail-layout { display: grid; grid-template-columns: 1fr 300px; gap: 1.5rem; margin-top: 1rem; }
        .meta-row { display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.6rem; padding-bottom: 0.4rem; border-bottom: 1px solid #f1f5f9; }
        .meta-row span:first-child { color: #64748b; font-weight: 500; }
        .skill-list { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        @media (max-width: 900px) { .detail-layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<header>
    </header>

<div class="page-wrapper">
    <main class="container">
        <div style="margin: 1.5rem 0;">
            <a href="offer_search.php" class="btn btn-ghost">← Retour aux offres</a>
        </div>

        <div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 2rem;">
            <div>
                <span class="tag <?= OfferHelper::getStateClass($offer['state']) ?> mb-2">
                    <?= OfferHelper::formatState($offer['state']) ?>
                </span>
                <h1 style="margin-top: 0.5rem;"><?= htmlspecialchars($displayTitle) ?></h1>
                <p style="font-size: 1.1rem; color: #64748b;">
                    <strong><?= htmlspecialchars($offer['company_name'] ?? 'Entreprise') ?></strong> 
                    • <?= htmlspecialchars($offer['location'] ?? 'N/C') ?>
                </p>
            </div>
            
            <div class="actions">
                <?php if (!empty($offer['application_url'])): ?>
                    <a href="<?= htmlspecialchars($offer['application_url']) ?>" target="_blank" class="btn btn-primary btn-lg">Postuler maintenant</a>
                <?php else: ?>
                    <a href="mailto:<?= htmlspecialchars($offer['contact_email'] ?? '') ?>?subject=Candidature: <?= urlencode($displayTitle) ?>" class="btn btn-primary">Contacter le recruteur</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="detail-layout">
            <div class="detail-content">
                <article class="card" style="padding: 2rem;">
                    <h3 class="section-title">Description du poste</h3>
                    <div class="rich-text" style="line-height: 1.6; color: #334155;">
                        <?= nl2br(htmlspecialchars($offer['description'] ?? '')) ?>
                    </div>
                </article>

                <?php if (!empty($skills)): ?>
                <article class="card" style="padding: 2rem; margin-top: 1.5rem;">
                    <h3 class="section-title">Compétences recherchées</h3>
                    <div class="skill-list">
                        <?php foreach ($skills as $skill): ?>
                            <span class="skill-tag"><?= htmlspecialchars($skill) ?></span>
                        <?php endforeach; ?>
                    </div>
                </article>
                <?php endif; ?>
            </div>

            <aside>
                <div class="card" style="padding: 1.5rem;">
                    <h3 style="font-size: 1rem; margin-bottom: 1.2rem; border-bottom: 2px solid var(--primary-color); display: inline-block;">Infos clés</h3>
                    
                    <div class="meta-row"><span>Salaire</span><strong><?= $salaryText ?></strong></div>
                    <div class="meta-row"><span>Contrat</span><strong><?= OfferHelper::formatJobType($offer['job_type']) ?></strong></div>
                    <div class="meta-row"><span>Télétravail</span><strong><?= ucfirst($offer['remote_type'] ?? 'On-site') ?></strong></div>
                    <div class="meta-row"><span>Expérience</span><strong><?= ucfirst($offer['experience_level'] ?? 'N/C') ?></strong></div>
                    <div class="meta-row"><span>Vues</span><strong><?= (int)$offer['views_count'] ?></strong></div>

                    <?php if (!empty($offer['application_deadline'])): ?>
                        <div class="notice notice-warn" style="margin-top: 1rem; font-size: 0.85rem;">
                            <strong>Deadline :</strong> <?= date('d/m/Y', strtotime($offer['application_deadline'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card" style="padding: 1.5rem; margin-top: 1.5rem;">
                    <h3 style="font-size: 1rem; margin-bottom: 0.8rem;">À propos de l'entreprise</h3>
                    <p style="font-weight: 600; margin-bottom: 0.5rem;"><?= htmlspecialchars($offer['company_name'] ?? '') ?></p>
                    <p style="font-size: 0.85rem; color: #64748b; line-height: 1.4;">
                        <?= htmlspecialchars($offer['company_bio'] ?? 'Aucune description disponible pour cette entreprise.') ?>
                    </p>
                </div>
            </aside>
        </div>
    </main>
</div>

<footer style="margin-top: 4rem;"></footer>

<script type="module" src="<?= CDN_URL; ?>/assets/scripts/load_head_foot.js"></script>
</body>
</html>