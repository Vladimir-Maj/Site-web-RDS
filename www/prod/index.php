<?php
// prod/index.php

require_once __DIR__ . '/.back/util/config.php';

// 1. Dependency Injection & Repository setup
$repo = new OfferRepository($pdo);

// 2. Pagination Logic
$limit = 5; 
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$offset = (max(1, $page) - 1) * $limit;

try {
    $totalOffers = $repo->countAll();
    $totalPages = ceil($totalOffers / $limit);
    $offers = $repo->findPaginated($limit, $offset);
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Une erreur est survenue lors du chargement des offres.");
}

// 3. Render
// Note: 'currentPage' is passed to help Twig highlight the nav menu
TwigFactory::render('index.html.twig', [
    'pageTitle'   => "Catalogue des Offres - StageFlow",
    'offers'      => $offers,
    'totalOffers' => $totalOffers,
    'page'        => $page,
    'totalPages'  => $totalPages,
    'currentPage' => 'index' 
]);