<?php
require_once 'db_connect.php';
require_once 'OfferRepository.php';

$repo = new OfferRepository($pdo);
$offerId = $_GET['offer_id'] ?? null;
$offer = null;

if ($offerId) {
    $offer = $repo->findById($offerId);
    if ($offer) {
        $repo->incrementViews($offerId);
    }
}

if (!$offer) {
    header("Location: home.php");
    exit();
}

// Prepare display values
$displayTitle = !empty($offer['position']) ? $offer['position'] : $offer['title'];
$salaryText = ($offer['salary_min'] || $offer['salary_max'])
    ? ($offer['salary_min'] ?? '?') . "€ - " . ($offer['salary_max'] ?? '?') . "€"
    : "Non spécifié";

$stateClasses = [
    'open'    => 'tag-green',
    'pending' => 'tag-amber',
    'draft'   => 'tag-slate'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= htmlspecialchars($displayTitle) ?> — StageFlow</title>
    <link rel="stylesheet" href="http://cdn.stageflow.fr/styles.css"/>
    <style>
        /* Maintain TUI grid alignment */
        .detail-layout { display: grid; grid-template-columns: 1fr 280px; gap: 1rem; margin-top: 1rem; }
        .detail-content { display: flex; flex-direction: column; gap: 1rem; }
        .section-title { font-size: 0.9rem; text-transform: uppercase; border-bottom: 1px solid var(--clr-border-light); margin-bottom: 0.5rem; padding-bottom: 0.2rem; }
        .meta-row { display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 0.4rem; }
        .meta-row span:first-child { color: var(--clr-text-muted); }

        @media (max-width: 768px) {
            .detail-layout { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<script type="module" src="http://cdn.stageflow.fr/assets/scripts/load_head_foot.js"></script>
<header></header>

<div class="page-wrapper">
    <main>
        <div class="mb-2">
            <a href="home.php" class="btn btn-ghost" style="text-decoration:none;">&lt; RETOUR</a>
        </div>

        <div class="page-header">
            <div>
                <span class="tag <?= $stateClasses[$offer['state']] ?? 'tag-slate' ?> mb-2">
                    <?= strtoupper($offer['state']) ?>
                </span>
                <h1><?= htmlspecialchars($displayTitle) ?></h1>
                <p><?= htmlspecialchars($offer['company_name']) ?> @ <?= htmlspecialchars($offer['location']) ?></p>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <?php if ($offer['application_url']): ?>
                    <a href="<?= htmlspecialchars($offer['application_url']) ?>" target="_blank" class="btn btn-primary">POSTULER</a>
                <?php else: ?>
                    <a href="mailto:<?= htmlspecialchars($offer['contact_email']) ?>" class="btn btn-primary">CONTACTER</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="detail-layout">
            <div class="detail-content">
                <article class="card">
                    <div class="card-header">
                        <h3>DESCRIPTION_DU_POSTE</h3>
                    </div>
                    <div class="card-body">
                        <div style="white-space: pre-line; color: var(--clr-text-muted);">
                            <?= nl2br(htmlspecialchars($offer['description'])) ?>
                        </div>
                    </div>
                </article>

                <?php if ($offer['required_skills']): ?>
                    <article class="card">
                        <div class="card-header">
                            <h3>COMPETENCES_REQUISES</h3>
                        </div>
                        <div class="card-body">
                            <p><?= htmlspecialchars($offer['required_skills']) ?></p>
                        </div>
                    </article>
                <?php endif; ?>

                <?php if ($offer['benefits']): ?>
                    <article class="card">
                        <div class="card-header">
                            <h3>AVANTAGES</h3>
                        </div>
                        <div class="card-body">
                            <p><?= htmlspecialchars($offer['benefits']) ?></p>
                        </div>
                    </article>
                <?php endif; ?>
            </div>

            <aside>
                <div class="card">
                    <div class="card-header">
                        <h3>INFOS_CLES</h3>
                    </div>
                    <div class="card-body">
                        <div class="meta-row"><span>SALAIRE</span><strong><?= $salaryText ?></strong></div>
                        <div class="meta-row"><span>CONTRAT</span><strong><?= ucfirst($offer['job_type']) ?></strong></div>
                        <div class="meta-row"><span>REMOTE</span><strong><?= ucfirst($offer['remote_type']) ?></strong></div>
                        <div class="meta-row"><span>NIVEAU</span><strong><?= strtoupper($offer['education_level']) ?></strong></div>
                        <div class="meta-row"><span>VUES</span><strong><?= (int)$offer['views_count'] ?></strong></div>

                        <?php if ($offer['application_deadline']): ?>
                            <div class="notice notice-warn mt-2">
                                <span class="notice-icon">!</span>
                                <p>Deadline: <?= date('d/m/Y', strtotime($offer['application_deadline'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mt-2">
                    <div class="card-header">
                        <h3>ENTREPRISE</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-sm"><?= htmlspecialchars($offer['company_name']) ?></p>
                        <p class="text-sm mt-1"><?= htmlspecialchars($offer['company_bio'] ?? 'Pas de description.') ?></p>
                    </div>
                </div>
            </aside>
        </div>
    </main>
</div>

<footer></footer>
</body>
</html>