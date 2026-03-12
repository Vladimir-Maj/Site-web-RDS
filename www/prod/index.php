<?php
// prod/index.php

declare(strict_types=1);

// This handles your autoloader, PDO $pdo, and TwigFactory
require_once __DIR__ . '/.back/util/config.php';

// 1. Repository setup
$offerRepo = new OfferRepository($pdo);

// 2. Pagination Parameters
$limit  = 5; 
$page   = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$offset = (max(1, $page) - 1) * $limit;

try {
    $totalOffers = $offerRepo->countAll();
    $totalPages  = (int)ceil($totalOffers / $limit);
    
    // Ensure findPaginated uses your OfferModel::fromArray internally
    $offers = $offerRepo->findPaginated($limit, $offset);

} catch (Exception $e) {
    error_log("Error loading index.php: " . $e->getMessage());
    // You might have a specific error page in Twig
    die("Une erreur est survenue.");
}

// 3. Render
TwigFactory::render('index.html.twig', [
    'pageTitle'   => "Catalogue des Offres - StageFlow",
    'offers'      => $offers,
    'totalOffers' => $totalOffers,
    'page'        => $page,
    'totalPages'  => $totalPages,
    'currentPage' => 'index' 
]);