<?php
require_once 'db_connect.php';
require_once 'OfferRepository.php';

$repo = new OfferRepository($pdo);

/**
 * 1. INITIALIZE SEARCH VARIABLES
 * Prevents "Undefined variable" and "Deprecated" errors in the form
 */
$keyword = $_GET['q-title']   ?? '';
$city    = $_GET['q-city']    ?? '';
$company = $_GET['q-company'] ?? '';
$sort    = $_GET['sort']      ?? 'recent';

$filters = [
        'keyword' => $keyword,
        'city'    => $city,
        'company' => $company,
        'sort'    => $sort
];

/**
 * 2. FETCH DATA
 */
$offers = $repo->search($filters);
$count = count($offers);

/**
 * 3. TEMPLATE LOADING
 * Mirrors the logic from your home.php to stay consistent
 */
$templatePath = __DIR__ . '/../cdn/assets/elements/card_template.html';
if (file_exists($templatePath)) {
    $cardTemplate = file_get_contents($templatePath);
} else {
    // Fallback if the file is missing so strtr() doesn't fail
    $cardTemplate = '<div class="card"><h3>{{STAGE_NAME}}</h3><p>{{STAGE_COMPANY}}</p></div>';
}
?>

<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>Recherche — prosit 1</title>
        <link rel="stylesheet" href="http://cdn.localhost:8080/styles.css"/>
    </head>
    <body>

    <!-- ━━━ HEADER ━━━ -->
    <header>
        <div class="header-inner">
            <a href="home.php" class="logo"><span>SF</span> prosit 1</a>
            <nav>
                <a href="home.php">Index</a>
                <a href="search.html" class="active">Recherche</a>
                <a href="editor.php">Créer</a>
                <a href="legals.html">Légal</a>
            </nav>
        </div>
    </header>

    <!-- ━━━ MAIN ━━━ -->
    <div class="page-wrapper">
        <main>

            <!-- Page Title -->
            <div class="page-header">
                <div>
                    <h1>Recherche d'Offres</h1>
                    <p>Filtrez et trouvez l'offre de stage qui vous correspond</p>
                </div>
            </div>

            <!-- Search Form -->
            <form action="search.php" method="GET">
                <div class="search-bar">
                    <div class="form-group" style="flex:2; min-width:220px;">
                        <label for="q-title">Titre ou mot-clé</label>
                        <input type="text" name="q-title" id="q-title" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Ex : développeur..." />
                    </div>
                    <div class="form-group">
                        <label for="q-city">Ville</label>
                        <input type="text" name="q-city" id="q-city" value="<?php echo htmlspecialchars($city); ?>" placeholder="Paris..." />
                    </div>
                    <div class="form-group">
                        <label for="q-company">Entreprise</label>
                        <input type="text" name="q-company" id="q-company" value="<?php echo htmlspecialchars($company); ?>" placeholder="Nom entreprise" />
                    </div>
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>


            <!-- Extended Filters -->
    <!--        <div class="card" style="margin-bottom:1.8rem;">-->
    <!--            <div class="card-header" style="align-items:center;">-->
    <!--                <h3>Filtres avancés</h3>-->
    <!--            </div>-->
    <!--            <div class="card-body">-->
    <!--                <div class="form-grid">-->
    <!---->
    <!--                    <div class="form-group">-->
    <!--                        <label for="f-domain">Domaine</label>-->
    <!--                        <select id="f-domain">-->
    <!--                            <option value="">Tous les domaines</option>-->
    <!--                            <option>Informatique</option>-->
    <!--                            <option>Data / IA</option>-->
    <!--                            <option>Design</option>-->
    <!--                            <option>Marketing</option>-->
    <!--                            <option>Finance / Comptabilité</option>-->
    <!--                            <option>Recherche &amp; Sciences</option>-->
    <!--                            <option>Management / Projet</option>-->
    <!--                            <option>Embedded / IoT</option>-->
    <!--                        </select>-->
    <!--                    </div>-->
    <!---->
    <!--                    <div class="form-group">-->
    <!--                        <label for="f-status">Statut</label>-->
    <!--                        <select id="f-status">-->
    <!--                            <option value="">Tous les statuts</option>-->
    <!--                            <option>Ouverte</option>-->
    <!--                            <option>En cours de validation</option>-->
    <!--                            <option>Brouillon</option>-->
    <!--                            <option>Clôturée</option>-->
    <!--                        </select>-->
    <!--                    </div>-->
    <!---->
    <!--                    <div class="form-group">-->
    <!--                        <label for="f-start">Début (au plus tôt)</label>-->
    <!--                        <input type="date" id="f-start"/>-->
    <!--                    </div>-->
    <!---->
    <!--                    <div class="form-group">-->
    <!--                        <label for="f-end">Fin (au plus tard)</label>-->
    <!--                        <input type="date" id="f-end"/>-->
    <!--                    </div>-->
    <!---->
    <!--                    <div class="form-group">-->
    <!--                        <label for="f-duration">Durée minimale</label>-->
    <!--                        <select id="f-duration">-->
    <!--                            <option value="">Sans contrainte</option>-->
    <!--                            <option>1 mois</option>-->
    <!--                            <option>2 mois</option>-->
    <!--                            <option>3 mois</option>-->
    <!--                            <option>6 mois</option>-->
    <!--                        </select>-->
    <!--                    </div>-->
    <!---->
    <!--                    <div class="form-group">-->
    <!--                        <label for="f-level">Niveau étudiant</label>-->
    <!--                        <select id="f-level">-->
    <!--                            <option value="">Tous niveaux</option>-->
    <!--                            <option>L1 – L2</option>-->
    <!--                            <option>L3 – M1</option>-->
    <!--                            <option>M2</option>-->
    <!--                            <option>École d'ingénieur</option>-->
    <!--                        </select>-->
    <!--                    </div>-->
    <!---->
    <!--                </div>-->
    <!--                <div class="form-actions">-->
    <!--                    <button type="button" class="btn btn-ghost">Réinitialiser</button>-->
    <!--                    <button type="button" class="btn btn-primary">Appliquer les filtres</button>-->
    <!--                </div>-->
    <!--            </div>-->
    <!--        </div>-->

            <div class="page-inner-element-group">
                <p class="text-sm text-muted">
                    <strong><?php echo $count; ?> résultat<?php echo ($count > 1 ? 's' : ''); ?></strong> trouvé<?php echo ($count > 1 ? 's' : ''); ?>
                </p>

                <select name="sort" onchange="this.form.submit()" >
                    <option value="relevance" <?php echo ($_GET['sort'] ?? '') == 'relevance' ? 'selected' : ''; ?>>Trier par : Pertinence</option>
                    <option value="recent"    <?php echo ($_GET['sort'] ?? '') == 'recent' ? 'selected' : ''; ?>>Trier par : Date (récent)</option>
                    <option value="city"      <?php echo ($_GET['sort'] ?? '') == 'city' ? 'selected' : ''; ?>>Trier par : Ville (A→Z)</option>
                    <option value="company"   <?php echo ($_GET['sort'] ?? '') == 'company' ? 'selected' : ''; ?>>Trier par : Entreprise (A→Z)</option>
                </select>
            </div>

            <div class="offer-list">
                <?php
                if ($count > 0) {
                    foreach ($offers as $offer) {
                        $replacements = [
                                '{{STAGE_ID}}' => $offer['id'],
                                '{{STAGE_NAME}}' => htmlspecialchars($offer['title']),
                                '{{STAGE_POSITION}}' => htmlspecialchars($offer['location']),
                                '{{STAGE_COMPANY}}' => htmlspecialchars($offer['company_name']),
                                '{{STAGE_DATE}}' => date('M Y', strtotime($offer['created_at'])),
                                '{{STAGE_DESC}}' => htmlspecialchars(substr($offer['description'], 0, 150)) . '...',
                                '{{STAGE_STATUS}}' => strtoupper($offer['state']),
                                '{{STAGE_TAG_CLASS}}' => ($offer['state'] === 'open' ? 'tag-green' : 'tag-amber')
                        ];
                        echo strtr($cardTemplate, $replacements);
                    }
                } else {
                    echo "<p>Aucun résultat pour cette recherche.</p>";
                }
                ?>
            </div>

            <!-- /offer-list -->
        </main>
    </div><!-- /page-wrapper -->

    <!-- ━━━ FOOTER ━━━ -->
    <footer>
        <div class="footer-inner">
            <span>Gestion d'Offres de Stage</span>
            <span><a href="legals.html">Conditions &amp; Mentions légales</a></span>
        </div>
    </footer>

    </body>
    </html>