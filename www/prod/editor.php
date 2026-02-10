<?php
require_once 'db_connect.php';
require_once 'OfferRepository.php';

$repo = new OfferRepository($pdo);

try {
    // 1. Fetch companies using the repo
    $companies = $repo->getAllCompanies();

    // 2. Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $offerData = [
                'title'       => $_POST['title'] ?? '',
                'company_id'  => $_POST['company_id'] ?? null,
                'location'    => $_POST['location'] ?? '',
                'description' => $_POST['description'] ?? '',
                'state'       => $_POST['state'] ?? 'draft'
        ];

        // Basic validation
        if (!empty($offerData['title']) && !empty($offerData['company_id'])) {
            if ($repo->create($offerData)) {
                header("Location: home.php");
                exit();
            }
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
    <title>Créer / Éditer une Offre — prosit 1</title>
    <link rel="stylesheet" href="http://cdn.localhost:8080/styles.css"/>
    <style>
        /* (Keeping your original CSS styles here...) */
        .section-divider { border: none; border-top: 1px solid var(--clr-border); margin: 1.2rem 0 .5rem; }
        .section-label { font-size: .76rem; font-weight: 700; color: var(--clr-text-muted); margin-bottom: .35rem; }
        .status-row { display: flex; gap: .5rem; flex-wrap: wrap; }
        .status-option { flex: 1; min-width: 140px; border: 1px solid var(--clr-border); padding: .55rem .7rem; background: var(--clr-bg); cursor: pointer; position: relative; }
        .status-option input { position: absolute; opacity: 0; cursor: pointer; }
        .status-option:has(input:checked) { border-color: var(--clr-accent); background: #fff; }
        .status-option strong { font-size: .82rem; }
        .status-option p { font-size: .74rem; margin-top: .15rem; color: var(--clr-text-muted); }
        .dot-green { color: #2e7d32; } .dot-amber { color: #f9a825; } .dot-slate { color: #666; }
    </style>
</head>
<body>
<script type="module" src="http://cdn.localhost:8080/assets/scripts/load_head_foot.js"></script>
<header></header>

<div class="page-wrapper">
    <main>
        <div class="page-header">
            <div>
                <h1>Nouvelle Offre de Stage</h1>
                <p>Remplissez le formulaire ci-dessous pour publier une offre</p>
            </div>
            <a href="home.php" class="btn btn-ghost">← Retour à l'index</a>
        </div>

        <?php if(isset($error)): ?>
        <div class="notice notice-warn"><p>Erreur : <?php echo $error; ?></p></div>
        <?php endif; ?>

        <form action="editor.php" method="POST" class="card">
            <div class="card-header">
                <h3>Informations de l'offre</h3>
            </div>
            <div class="card-body">

                <p class="section-label">Identité</p>
                <div class="form-grid">
                    <div class="form-group span-full">
                        <label for="e-title">Titre de l'offre</label>
                        <input type="text" name="title" id="e-title" placeholder="Ex : Développeur Full-Stack" required/>
                    </div>
                    <div class="form-group">
                        <label for="e-company">Entreprise</label>
                        <select name="company_id" id="e-company" required>
                            <option value="">— Choisir une entreprise —</option>
                            <?php foreach($companies as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="e-city">Ville / Lieu</label>
                        <input type="text" name="location" id="e-city" placeholder="Nancy, FR" required/>
                    </div>
                </div>

                <hr class="section-divider"/>
                <p class="section-label">Description</p>
                <div class="form-grid">
                    <div class="form-group span-full">
                        <label for="e-desc">Description de l'offre</label>
                        <textarea name="description" id="e-desc" rows="5" placeholder="Missions, outils..."></textarea>
                    </div>
                </div>

                <hr class="section-divider"/>
                <p class="section-label">Statut de publication</p>
                <div class="status-row">
                    <label class="status-option">
                        <input type="radio" name="state" value="draft" checked>
                        <span class="status-dot dot-slate">[_]</span><strong>Brouillon</strong>
                        <p>Pas encore visible</p>
                    </label>
                    <label class="status-option">
                        <input type="radio" name="state" value="pending">
                        <span class="status-dot dot-amber">[~]</span><strong>En validation</strong>
                        <p>En attente de revue</p>
                    </label>
                    <label class="status-option">
                        <input type="radio" name="state" value="open">
                        <span class="status-dot dot-green">[+]</span><strong>Publiée</strong>
                        <p>Visible en public</p>
                    </label>
                </div>

                <div class="form-actions" style="margin-top:2rem;">
                    <a href="home.php" class="btn btn-ghost">Annuler</a>
                    <button type="submit" class="btn btn-primary">Publier l'offre</button>
                </div>
            </div>
        </form>

    </main>
</div>
<footer></footer>
</body>
</html>