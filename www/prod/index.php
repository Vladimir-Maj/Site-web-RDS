<?php
// prod/index.php

declare(strict_types=1);

/**
 * 1. INITIALIZE AUTOLOADER & CONFIG
 * This MUST happen before session_start() so PHP can unserialize 
 * custom objects/enums stored in the session.
 */
require_once __DIR__ . '/.back/util/config.php';

use App\Repository\OfferRepository;
use App\Repository\UserRepository;
use App\Controllers\AuthController;

/**
 * 2. SECURE SESSION CONFIGURATION (TASK-25)
 */
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => true, // Assumes HTTPS on stageflow.fr
    'httponly' => true,
    'samesite' => 'Strict'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * 3. CSRF TOKEN INITIALIZATION (TASK-28)
 */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Prepare Routing
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

/**
 * 4. AUTHENTICATION ROUTES (TASK-22)
 */
if ($path === '/login') {
    $userRepo = new UserRepository($pdo);
    $twigInstance = TwigFactory::getTwig(); 
    $controller = new AuthController($userRepo, $twigInstance);
    $controller->login();
    exit;
}

if ($path === '/logout') {
    $userRepo = new UserRepository($pdo);
    $twigInstance = TwigFactory::getTwig(); 
    $controller = new AuthController($userRepo, $twigInstance);
    $controller->logout();
    exit;
}

if ($path === '/register') {
    $userRepo = new UserRepository($pdo);
    $twigInstance = TwigFactory::getTwig(); 
    $controller = new AuthController($userRepo, $twigInstance);
    $controller->register();
    exit;
}

// www/prod/index.php

if ($path === '/profile') {
    $userRepo = new UserRepository($pdo);
    $twigInstance = TwigFactory::getTwig(); 
    $controller = new AuthController($userRepo, $twigInstance);
    $controller->profile(); // Call the new method
    exit;
}

if ($path === '/profile/upload-cv') {
    $userRepo = new UserRepository($pdo);
    $controller = new AuthController($userRepo, TwigFactory::getTwig());
    $controller->uploadCv();
    exit;
}

/**
 * 5. APPLICATION ROUTES
 */
// Match /app/applications/{id}
if (preg_match('#^/app/applications/([a-zA-Z0-9-]+)$#', $path, $matches)) {
    $id = $matches[1];
    $appRepo = new \App\Repository\ApplicationRepository($pdo);
    $controller = new \App\Controllers\ApplicationController($appRepo, $twig);
    $controller->showJson($id);
    exit;
}

/**
 * 6. HOME / CATALOGUE LOGIC
 */
// 1. Repository setup
$offerRepo = new OfferRepository($pdo);

// 2. Pagination Parameters
$limit = 5;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$offset = (max(1, $page) - 1) * $limit;

try {
    $totalOffers = $offerRepo->countAll();
    $totalPages = (int) ceil($totalOffers / $limit);
    $offers = $offerRepo->findPaginated($limit, $offset);

    // 3. Render Home via TwigFactory
    // Note: Use global variables or passed-in instances for the render call
    TwigFactory::render('index.html.twig', [
        'pageTitle'   => "Catalogue des Offres - StageFlow",
        'offers'      => $offers,
        'totalOffers' => $totalOffers,
        'page'        => $page,
        'totalPages'  => $totalPages,
        'currentPage' => 'index',
        'csrf_token'  => $_SESSION['csrf_token'],
        'user'        => $_SESSION['user'] ?? null // Pass user data to navbar
    ]);

} catch (Exception $e) {
    error_log("Error loading index.php: " . $e->getMessage());
    http_response_code(500);
    die("Une erreur système est survenue. Veuillez réessayer plus tard.");
}