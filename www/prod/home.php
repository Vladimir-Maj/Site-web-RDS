<?php
/**
 * 1. REPOSITORIES & DATA FETCHING
 */
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/OfferRepository.php';

$repo = new OfferRepository($pdo);

try {
    // We use the Repository to get our data
    $offers = $repo->findAll();

    $count = count($offers);
    $plural = $count > 1 ? 's' : '';

} catch (Exception $e) {
    // Error handling using the centralized connection
    die("Erreur lors de la récupération des offres : " . $e->getMessage());
}

/**
 * 2. TEMPLATE LOADING
 */
$templatePath = __DIR__ . '/../cdn/assets/elements/card_template.html';
$cardTemplate = file_exists($templatePath) ? file_get_contents($templatePath) : "";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Toutes les Offres - prosit 1</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Raleway:wght@400;500;600;700&display=swap"
          rel="stylesheet">
    <link rel="stylesheet" href="http://cdn.localhost:8080/styles.css"/>
</head>
<body>
<script type="module" src="http://cdn.localhost:8080/assets/scripts/load_head_foot.js"></script>
<header></header>

<div class="page-wrapper">
    <main>

        <div class="page-header">
            <div>
                <h1>Offres de Stage</h1>
                <p>Ensemble des offres enregistrées dans le système</p>
            </div>
            <a href="editor.php" class="btn btn-primary">+ Nouvelle offre</a>
        </div>

        <div class="notice notice-info">
            <span class="notice-icon">(ℹ)</span>
            <p>Cette page affiche les <strong><?php echo $count; ?> offre<?php echo $plural; ?></strong> actuellement
                actives. Utilisez la page <a
                        href="search.php" style="color:#4f46e5; text-decoration:underline;">Recherche</a> pour
                filtrer.</p>
        </div>

        <div class="offer-list">
            <?php
            // Mapping for the tag colors based on the state in DB
            $stateClasses = [
                    'open' => 'tag-green',
                    'pending' => 'tag-amber',
                    'draft' => 'tag-slate'
            ];

            if ($count > 0 && !empty($cardTemplate)) {
                foreach ($offers as $offer) {
                    // Define what placeholders to swap
                    $replacements = [
                            '{{STAGE_ID}}' => $offer['id'], // Add this line
                            '{{STAGE_NAME}}' => htmlspecialchars($offer['title']),
                            '{{STAGE_POSITION}}' => htmlspecialchars($offer['location']),
                            '{{STAGE_COMPANY}}' => htmlspecialchars($offer['company_name']),
                            '{{STAGE_DATE}}' => date('d/m/Y', strtotime($offer['created_at'])),
                            '{{STAGE_DESC}}' => htmlspecialchars($offer['description']),
                            '{{STAGE_STATUS}}' => strtoupper($offer['state']),
                            '{{STAGE_TAG_CLASS}}' => $stateClasses[$offer['state']] ?? 'tag-slate'
                    ];

                    // Inject the data into the template and display
                    echo strtr($cardTemplate, $replacements);
                }
            } elseif (empty($cardTemplate)) {
                echo "<p>Erreur: Template introuvable dans le dossier CDN.</p>";
            } else {
                echo "<p>Aucune offre n'est disponible pour le moment.</p>";
            }
            ?>
        </div>
    </main>
</div>
<footer></footer>
</body>
</html>