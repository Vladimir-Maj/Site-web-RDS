<?php
/**
 * 1. REPOSITORIES & DATA FETCHING
 */
require_once __DIR__ . '/util/db_connect.php';
require_once __DIR__ . '/util/OfferRepository.php';

$repo = new OfferRepository($pdo);

try {
    // Fetch all offers using the updated repository
    $offers = $repo->findAll();
    $count = count($offers);
    $plural = $count > 1 ? 's' : '';

} catch (Exception $e) {
    die("Erreur lors de la récupération des offres : " . $e->getMessage());
}

/**
 * 2. TEMPLATE LOADING
 * Adjusting path to match your CDN structure
 */
$templatePath = __DIR__ . '/../cdn/assets/elements/card_template.html';
// If file_get_contents fails, we provide a basic fallback string to avoid a blank page
$cardTemplate = file_exists($templatePath) ? file_get_contents($templatePath) : "
<div class='card'>
    <h3>{{STAGE_NAME}}</h3>
    <p><strong>{{STAGE_COMPANY}}</strong> - {{STAGE_POSITION}}</p>
    <p>{{STAGE_DESC}}</p>
    <span class='tag {{STAGE_TAG_CLASS}}'>{{STAGE_STATUS}}</span>
</div>";
?>

<?php require_once 'util/config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dashboard des Offres — SF Prosit</title>

    <script>
        window.APP_CONFIG = {
            cdnUrl: "<?php echo CDN_URL; ?>"
        };
    </script>

    <link rel="stylesheet" href="<?php echo CDN_URL; ?>/styles.css"/>

    <style>
        .salary-badge { font-weight: 600; color: #16a34a; font-size: 0.85rem; }
        .meta-info { font-size: 0.75rem; color: #666; margin-top: 5px; }
    </style>
</head>
<body>

<script type="module" src="<?php echo CDN_URL; ?>/assets/scripts/load_head_foot.js?v=2"></script>

<header></header>

<div class="page-wrapper">
    <main>

        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1>Catalogue des Offres</h1>
                <p>Gestion centrale des opportunités de stage et emploi.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="search.php" class="btn btn-ghost">🔍 Rechercher</a>
                <a href="editor.php" class="btn btn-primary">+ Publier une offre</a>
            </div>
        </div>

        <div class="notice notice-info">
            <span class="notice-icon">📊</span>
            <p>
                Il y a actuellement <strong><?= $count; ?> offre<?= $plural; ?></strong> enregistrée<?= $plural; ?>.
                Les offres en <em>Brouillon</em> ne sont visibles que par vous.
            </p>
        </div>

        <div class="offer-list">
            <?php
            // Mapping for the status tags
            $stateClasses = [
                    'open'    => 'tag-green',
                    'pending' => 'tag-amber',
                    'draft'   => 'tag-slate'
            ];

            if ($count > 0) {
                foreach ($offers as $offer) {
                    // Logic for display title: prefer 'position', fallback to 'title'
                    $displayTitle = !empty($offer['position']) ? $offer['position'] : $offer['title'];

                    // Format Salary Display
                    $salaryText = "Salaire non précisé";
                    if ($offer['salary_min'] || $offer['salary_max']) {
                        $salaryText = ($offer['salary_min'] ?? '?') . "€ - " . ($offer['salary_max'] ?? '?') . "€";
                    }

                    // Define what placeholders to swap in the HTML template
                    $replacements = [
                            '{{STAGE_ID}}'        => $offer['id'],
                            '{{STAGE_NAME}}'      => htmlspecialchars($displayTitle),
                            '{{STAGE_POSITION}}'  => htmlspecialchars($offer['location']),
                            '{{STAGE_COMPANY}}'   => htmlspecialchars($offer['company_name'] ?? 'Inconnue'),
                            '{{STAGE_DATE}}'      => date('d/m/Y', strtotime($offer['created_at'])),
                            '{{STAGE_DESC}}'      => mb_strimwidth(htmlspecialchars($offer['description']), 0, 150, "..."),
                            '{{STAGE_STATUS}}'    => strtoupper($offer['state']),
                            '{{STAGE_TAG_CLASS}}' => $stateClasses[$offer['state']] ?? 'tag-slate',
                        // Additional metadata you can add to your template if it supports it:
                            '{{STAGE_SALARY}}'    => $salaryText,
                            '{{STAGE_TYPE}}'      => ucfirst($offer['job_type'] ?? 'N/A')
                    ];

                    echo strtr($cardTemplate, $replacements);
                }
            } else {
                echo "
                <div class='card' style='text-align:center; padding: 3rem;'>
                    <p>Aucune offre n'est disponible pour le moment.</p>
                    <a href='editor.php' class='btn btn-primary'>Créer la première offre</a>
                </div>";
            }
            ?>
        </div>
    </main>
</div>
</body>
</html>