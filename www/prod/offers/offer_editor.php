<?php
/**
 * SF Prosit - Job Editor
 * Path: /prod/offers/offer_editor.php
 */

require_once '../util/config.php';
require_once '../util/db_connect.php';
require_once '../util/OfferRepository.php';

// --- 1. CONFIGURATION ---
$pageTitle = "Publier une offre — SF Prosit";
$currentPage = "offer_editor.php";

// --- 2. ELEMENT FETCHING (Harmonisé avec template.php) ---
$basePath = __DIR__ . '/../../cdn/assets/elements/';
$headerPath = $basePath . 'header_template.html';
$footerPath = $basePath . 'footer_template.html';

$headerHtml = file_exists($headerPath) ? file_get_contents($headerPath) : "";
$footerHtml = file_exists($footerPath) ? file_get_contents($footerPath) : "";

// Set Active Link
$headerHtml = str_replace('data-page="' . $currentPage . '"', 'data-page="' . $currentPage . '" class="active"', $headerHtml);

// --- 3. PAGE LOGIC ---
$repo = new OfferRepository($pdo);
$error = null;

try {
    $companies = $repo->getAllCompanies();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $offerData = [
                'title'                => $_POST['title'] ?? '',
                'position'             => $_POST['title'] ?? '', // Doublon par sécurité selon votre repo
                'company_id'           => (int)($_POST['company_id'] ?? 0),
                'location'             => $_POST['location'] ?? '',
                'description'          => $_POST['description'] ?? '',
                'state'                => $_POST['state'] ?? 'draft',
                'salary_min'           => $_POST['salary_min'] !== '' ? (int)$_POST['salary_min'] : null,
                'salary_max'           => $_POST['salary_max'] !== '' ? (int)$_POST['salary_max'] : null,
                'salary_currency'      => $_POST['salary_currency'] ?? 'EUR',
                'job_type'             => $_POST['job_type'] ?? 'full-time',
                'remote_type'          => $_POST['remote_type'] ?? 'on-site',
                'experience_level'     => $_POST['experience_level'] ?? 'mid',
                'education_level'      => $_POST['education_level'] ?? 'none',
                'required_skills'      => $_POST['required_skills'] ?? '',
                'benefits'             => $_POST['benefits'] ?? '',
                'contact_email'        => $_POST['contact_email'] ?? '',
                'application_url'      => $_POST['application_url'] ?? '',
                'application_deadline' => !empty($_POST['deadline']) ? $_POST['deadline'] : null,
                'expires_at'           => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null
        ];

        if (!empty($offerData['title']) && $offerData['company_id'] > 0) {
            if ($repo->create($offerData)) {
                header("Location: ../index.php"); // Redirection vers l'accueil/catalogue
                exit();
            }
        } else {
            $error = "Le titre et l'entreprise sont obligatoires.";
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo $pageTitle; ?></title>

    <script>window.APP_CONFIG = { cdnUrl: "<?php echo CDN_URL; ?>" };</script>
    <link rel="icon" type="image/x-icon" href="<?php echo CDN_URL; ?>/favicon.ico">
    <link rel="stylesheet" href="<?php echo CDN_URL; ?>/styles.css">

    <style>
        .form-section { border-top: 1px solid #eee; margin-top: 20px; padding-top: 20px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .label-group { font-weight: bold; color: #2563eb; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 12px; display: block; letter-spacing: 0.05em; }
        .error-notice { color: #d9534f; background: #fef2f2; border: 1px solid #fecaca; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        /* Responsive simple */
        @media (max-width: 768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<header>
    <?php echo $headerHtml; ?>
</header>

<div class="page-wrapper">
    <main class="container page-content" style="padding: 2rem 0;">
        <div class="page-header" style="margin-bottom: 2rem;">
            <h1>Publier une offre</h1>
            <p>Remplissez les détails pour attirer les meilleurs candidats.</p>
        </div>

        <?php if($error): ?>
            <div class="error-notice">
                <strong>Erreur :</strong> <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="card" style="padding: 30px;">

            <span class="label-group">Poste & Entreprise</span>
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>Titre de l'offre</label>
                <input type="text" name="title" placeholder="ex: Développeur PHP Fullstack" required style="width: 100%;"/>
            </div>

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label>Entreprise</label>
                    <select name="company_id" required style="width: 100%;">
                        <option value="">— Choisir —</option>
                        <?php foreach($companies as $c): ?>
                            <option value="<?= $c['id']; ?>"><?= htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ville / Lieu</label>
                    <input type="text" name="location" placeholder="ex: Nancy (54)" required style="width: 100%;"/>
                </div>
            </div>

            <div class="form-section">
                <span class="label-group">Salaire & Contrat</span>
                <div class="grid-3" style="margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Salaire Min (€)</label>
                        <input type="number" name="salary_min" placeholder="35000" style="width: 100%;">
                    </div>
                    <div class="form-group">
                        <label>Salaire Max (€)</label>
                        <input type="number" name="salary_max" placeholder="45000" style="width: 100%;">
                    </div>
                    <div class="form-group">
                        <label>Type de contrat</label>
                        <select name="job_type" style="width: 100%;">
                            <option value="full-time">CDI</option>
                            <option value="part-time">Temps partiel</option>
                            <option value="internship">Stage</option>
                            <option value="apprenticeship">Alternance</option>
                        </select>
                    </div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label>Mode de travail</label>
                        <select name="remote_type" style="width: 100%;">
                            <option value="on-site">Sur site</option>
                            <option value="hybrid">Hybride</option>
                            <option value="remote">Télétravail</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Niveau d'expérience</label>
                        <select name="experience_level" style="width: 100%;">
                            <option value="entry">Junior</option>
                            <option value="mid">Confirmé</option>
                            <option value="senior">Senior</option>
                            <option value="lead">Lead</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Niveau d'études</label>
                        <select name="education_level" style="width: 100%;">
                            <option value="none">Indifférent</option>
                            <option value="bac+2">Bac +2</option>
                            <option value="bac+3">Bac +3</option>
                            <option value="bac+5">Bac +5</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <span class="label-group">Détails & Compétences</span>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Description</label>
                    <textarea name="description" rows="5" placeholder="Missions, environnement technique..." style="width: 100%; font-family: inherit;"></textarea>
                </div>
                <div class="form-group">
                    <label>Compétences (séparées par des virgules)</label>
                    <input type="text" name="required_skills" placeholder="PHP, Symfony, Docker..." style="width: 100%;"/>
                </div>
            </div>

            <div class="form-section">
                <span class="label-group">Publication</span>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Statut</label>
                        <select name="state" style="width: 100%;">
                            <option value="draft">Brouillon (Privé)</option>
                            <option value="open">Publié (Public)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Expire le (Optionnel)</label>
                        <input type="date" name="expires_at" style="width: 100%;">
                    </div>
                </div>
            </div>

            <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: flex-end;">
                <a href="../index.php" class="btn btn-ghost">Annuler</a>
                <button type="submit" class="btn btn-primary" style="padding: 12px 40px; cursor: pointer;">Enregistrer l'offre</button>
            </div>
        </form>
    </main>
</div>

<footer>
    <?php echo $footerHtml; ?>
</footer>

<script type="module" src="<?php echo CDN_URL; ?>/assets/scripts/load_head_foot.js"></script>
</body>
</html>