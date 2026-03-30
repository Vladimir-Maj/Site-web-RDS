<?php
/**
 * Path: /var/www/html/prod/offers/offer_editor.php
 */

require_once __DIR__ . '/../.back/util/config.php';

$repo = new OfferRepository($pdo);
$companyRepo = new CompanyRepository($pdo);
$error = null;

try {
    $companies = $companyRepo->findAll(); 

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            'title'            => $_POST['title'] ?? '',
            'position'         => $_POST['title'] ?? '', 
            'company_id'       => (int)($_POST['company_id'] ?? 0),
            'location'         => $_POST['location'] ?? '',
            'description'      => $_POST['description'] ?? '',
            'state'            => $_POST['state'] ?? 'draft',
            'salary_min'       => $_POST['salary_min'] !== '' ? (int)$_POST['salary_min'] : null,
            'salary_max'       => $_POST['salary_max'] !== '' ? (int)$_POST['salary_max'] : null,
            'salary_currency'  => $_POST['salary_currency'] ?? 'EUR',
            'job_type'         => $_POST['job_type'] ?? 'full-time',
            'remote_type'      => $_POST['remote_type'] ?? 'on-site',
            'experience_level' => $_POST['experience_level'] ?? 'mid',
            'education_level'  => $_POST['education_level'] ?? 'none',
            'required_skills'  => $_POST['required_skills'] ?? '',
            'contact_email'    => $_POST['contact_email'] ?? '',
            'application_url'  => $_POST['application_url'] ?? '',
            'expires_at'       => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null
        ];

        if (!empty($data['title']) && $data['company_id'] > 0) {
            if ($repo->create($data)) {
                header("Location: ../index.php");
                exit();
            } else {
                $error = "Une erreur est survenue lors de l'enregistrement.";
            }
        } else {
            $error = "Le titre et l'entreprise sont obligatoires.";
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Render with Twig
echo TwigFactory::getTwig()->render('offers/offer_editor.html.twig', [
    'companies'   => $companies,
    'error'       => $error,
    'currentPage' => 'editor'
]);