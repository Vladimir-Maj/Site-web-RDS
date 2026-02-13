<?php
/**
 * SF Prosit - Job Editor
 */

require_once 'util/config.php';
require_once 'util/db_connect.php';
require_once 'util/OfferRepository.php';

// --- 1. CONFIGURATION ---
$pageTitle = "Publier une offre — SF Prosit";
$currentPage = "editor.php"; // Explicitly set for navigation highlighting

// --- 2. HEADER FETCH LOGIC ---
$localHeaderPath = '/var/www/html/cdn/assets/elements/header.html';
$headerUrl = CDN_URL . "/assets/elements/header.html";

$headerHtml = "";
if (file_exists($localHeaderPath)) {
    $headerHtml = file_get_contents($localHeaderPath);
} else {
    $context = stream_context_create(["ssl" => ["verify_peer"=>false, "verify_peer_name"=>false]]);
    $headerHtml = @file_get_contents($headerUrl, false, $context) ?: "";
}
$headerHtml = str_replace('data-page="' . $currentPage . '"', 'data-page="' . $currentPage . '" class="active"', $headerHtml);

// --- 3. PAGE LOGIC ---
$repo = new OfferRepository($pdo);
$error = null;

try {
    $companies = $repo->getAllCompanies();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $offerData = [
                'title'                => $_POST['title'] ?? '',
                'position'             => $_POST['title'] ?? '',
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
                header("Location: search.php");
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

    <script>
        window.APP_CONFIG = { cdnUrl: "<?php echo CDN_URL; ?>" };
    </script>

    <link rel="icon" type="image/x-icon" href="<?php echo CDN_URL; ?>/favicon.ico">
    <link rel="stylesheet" href="<?php echo CDN_URL; ?>/styles.css">

    <style>
        .page-content { padding: 2rem 0; }
        .form-section { border-top: 1px solid #eee; margin-top: 20px; padding-top: 20px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .label-group { font-weight: bold; color: var(--clr-primary); font-size: 0.9rem; text-transform: uppercase; margin-bottom: 10px; display: block; }
        .error-notice { color: #d9534f; background: #f9f2f2; border: 1px solid #d9534f; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; }
    </style>
</head>
<body>

<header>
    <?php echo $headerHtml; ?>
</header>

<div class="page-wrapper">
    <main class="container page-content">
        <div class="page-header">
            <h1>Publier une offre</h1>
        </div>

        <?php if($error): ?>
            <div class="error-notice">
                <strong>Erreur :</strong> <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="editor.php" method="POST" class="card" style="padding: 20px;">

            <span class="label-group">Poste & Entreprise</span>
            <div class="form-group">
                <label>Titre de l'offre</label>
                <input type="text" name="title" placeholder="ex: Développeur PHP Fullstack" required/>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Entreprise</label>
                    <select name="company_id" required>
                        <option value="">— Choisir —</option>
                        <?php foreach($companies as $c): ?>
                            <option value="<?= $c['id']; ?>"><?= htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ville / Lieu</label>
                    <input type="text" name="location" placeholder="ex: Nancy (54)" required/>
                </div>
            </div>

            <div class="form-section">
                <span class="label-group">Salaire & Contrat</span>
                <div class="grid-3">
                    <div class="form-group">
                        <label>Salaire Min (€)</label>
                        <input type="number" name="salary_min" placeholder="35000">
                    </div>
                    <div class="form-group">
                        <label>Salaire Max (€)</label>
                        <input type="number" name="salary_max" placeholder="45000">
                    </div>
                    <div class="form-group">
                        <label>Type de contrat</label>
                        <select name="job_type">
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
                        <select name="remote_type">
                            <option value="on-site">Sur site</option>
                            <option value="hybrid">Hybride</option>
                            <option value="remote">Télétravail</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Niveau d'expérience</label>
                        <select name="experience_level">
                            <option value="entry">Junior</option>
                            <option value="mid">Confirmé</option>
                            <option value="senior">Senior</option>
                            <option value="lead">Lead</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Niveau d'études</label>
                        <select name="education_level">
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
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4" placeholder="Missions, environnement technique..."></textarea>
                </div>
                <div class="form-group">
                    <label>Compétences (séparées par des virgules)</label>
                    <input type="text" name="required_skills" placeholder="PHP, Symfony, Docker..."/>
                </div>
            </div>

            <div class="form-section">
                <span class="label-group">Publication</span>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Statut</label>
                        <select name="state">
                            <option value="draft">Brouillon (Privé)</option>
                            <option value="open">Publié (Public)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Expire le (Optionnel)</label>
                        <input type="date" name="expires_at">
                    </div>
                </div>
            </div>

            <div style="margin-top: 30px; display: flex; gap: 10px; justify-content: flex-end;">
                <a href="search.php" class="btn btn-ghost">Annuler</a>
                <button type="submit" class="btn btn-primary" style="padding: 10px 30px;">Enregistrer l'offre</button>
            </div>
        </form>
    </main>
</div>

<footer></footer>

<script type="module" src="<?php echo CDN_URL; ?>/assets/scripts/load_head_foot.js"></script>
</body>
</html>