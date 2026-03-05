<?php
/**
 * Path: /var/www/html/prod/offers/offer_search.php
 */

require_once __DIR__ . '/../.back/util/config.php';

$offerRepo = new OfferRepository($pdo);
$companyRepo = new CompanyRepository($pdo);

// 1. Collecte des filtres
$filters = [
    'keyword'     => $_GET['keyword'] ?? '',
    'location'    => $_GET['location'] ?? '',
    'job_type'    => $_GET['job_type'] ?? '',
    'company_id'  => $_GET['company_id'] ?? '',
    'remote_type' => $_GET['remote_type'] ?? '',
    'sort'        => $_GET['sort'] ?? 'recent'
];

try {
    // 2. Récupération des données pour les filtres
    $locations = $offerRepo->getUniqueLocations();
    $jobTypes  = $offerRepo->getAllJobTypes();
    $companies = $companyRepo->getCompaniesWithActiveOffers();

    // 3. Exécution de la recherche
    $offers = $offerRepo->search($filters);
    $count = count($offers);
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Une erreur technique est survenue.");
}

// 4. Rendu Twig
echo TwigFactory::getTwig()->render('offers/offer_search.html.twig', [
    'offers'      => $offers,
    'count'       => $count,
    'filters'     => $filters,
    'locations'   => $locations,
    'jobTypes'    => $jobTypes,
    'companies'   => $companies,
    'currentPage' => 'search' // Important pour le menu header
]);