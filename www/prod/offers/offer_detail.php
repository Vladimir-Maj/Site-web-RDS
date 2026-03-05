<?php
/**
 * Path: /var/www/html/prod/offers/offer_detail.php
 */

require_once __DIR__ . '/../.back/util/config.php';

// 1. Initialize Repositories
$offerRepo = new OfferRepository($pdo);
// Ensure you have a PostulationRepository or similar to check status
$postulationRepo = new PostulationRepository($pdo);

// 2. Get and Validate Offer ID
$offerId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$offer = $offerId ? $offerRepo->findById($offerId) : null;

// 3. Handle 404
if (!$offer) {
    header("Location: ../index.php?error=not_found");
    exit();
}

// 4. Business Logic
$offerRepo->incrementViews($offer->id);

// FIX: Always initialize $alreadyApplied so Twig doesn't crash
$alreadyApplied = false; 

if (isset($_SESSION['user_id'])) {
    $alreadyApplied = $postulationRepo->hasAlreadyApplied($_SESSION['user_id'], $offer->id);
}

// 5. Render
// Note: 'currentPage' is passed to fix the navigation crash in base.html.twig
echo TwigFactory::getTwig()->render('offers/offer_detail.html.twig', [
    'offer'          => $offer,
    'alreadyApplied' => $alreadyApplied,
    'currentPage'    => 'offers' 
]);