<?php
// prod/index.php
declare(strict_types=1);

require_once __DIR__ . '/.back/util/config.php';

use App\Controller\CompanyController;
use App\Models\ApplicationModel;
use App\Models\RoleEnum;
use App\Repository\CompanyRepository;
use App\Repository\UserRepository;
use App\Repository\ApplicationRepository;
use App\Controllers\AuthController;
use App\Controllers\ApplicationController;
use App\Util;

// --- 1. SESSION & SECURITY ---
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty(Util::getCSRFToken())) {
    Util::setCSRFToken(bin2hex(random_bytes(32)));
}

function jsonResponse(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Décode le body JSON de la requête entrante.
 * Lève une exception si le JSON est malformé.
 */
function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        jsonResponse(['error' => 'Invalid or missing JSON body.'], 400);
    }

    return $data;
}

// --- 2. MIDDLEWARES ---

/**
 * Vérifie si l'utilisateur est connecté
 */
$ensureAuth = function () {
    if (empty(Util::getUserId())) {
        header('Location: /login');
        exit;
    }
};

/**
 * Autorise si Admin/Pilote OU si l'ID cible est celui de l'utilisateur connecté
 */
$ensureSelfOrAdmin = function (?string $targetId = null, array $allowedRoles = ['admin', 'pilote']) {
    if (empty(Util::getUserId())) {
        header('Location: /login');
        exit;
    }

    $userRole = Util::getRole() ?? 'guest';
    $userId = Util::getUserId() ?? null;

    $isStaff = in_array($userRole, $allowedRoles);
    $isOwner = $targetId !== null && $targetId === $userId;

    if (!$isStaff && !$isOwner) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(["error" => "Permission refusée."]);
        exit;
    }
};

// --- 3. ROUTING ---
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$twig = TwigFactory::getTwig();

// ROUTES PUBLIQUES (Auth)
switch ($path) {
    case '/login':
        (new AuthController(new UserRepository($pdo), $twig))->login();
        exit;
    case '/logout':
        (new AuthController(new UserRepository($pdo), $twig))->logout();
        exit;
    case '/register':
        (new AuthController(new UserRepository($pdo), $twig))->register();
        exit;
}

// ROUTES PRIVÉES (Profil & Compte)
if (str_starts_with($path, '/profile')) {
    $ensureAuth();
    $authCtrl = new AuthController(new UserRepository($pdo), $twig);

    if ($path === '/profile') {
        $authCtrl->profile();
    } elseif ($path === '/profile/upload-cv') {
        $authCtrl->uploadCv();
    }
    exit;
}

//// --- WEB ROUTES: COMPANY MANAGEMENT ---
if (str_starts_with($path, '/app/companies')) {
//    if (Util::getRole() !== RoleEnum::Admin || Util::getRole() !== RoleEnum::Pilote) die ("ERR: INSUFFICIENT PERMS") ;

    $companyCtrl = new CompanyController(new CompanyRepository($pdo), $twig);
    $method = $_SERVER['REQUEST_METHOD'];

    // 1. Route spécifique : Création
    if ($path === '/app/companies/new') {
        if ($method === 'GET') {
            $companyCtrl->renderForm('new');
        } else {
            $companyCtrl->handleFormSave('new');
        }
        exit;
    }

    if ($path === '/app/companies') {
        $companyCtrl->renderList();
    }

    if (preg_match('#^/app/companies/([^/]+)$#', $path, $m)) {
        $id = $m[1]; // "create", "42", etc.
        if ($method === 'GET') {
            $companyCtrl->renderForm($id);
        } else {
            $companyCtrl->handleFormSave($id);
        }
        exit;
    }
}

// --- 4. CATALOGUE / HOME (FALLBACK) ---
if ($path === '/' || $path === '/index.php') {
    $offerRepo = new \App\Repository\OfferRepository($pdo);
    $limit = 5;
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    $offset = (max(1, $page) - 1) * $limit;

    try {
        $offers = $offerRepo->findPaginated($limit, $offset);
        $totalOffers = $offerRepo->countAll();

        TwigFactory::render('index.html.twig', [
            'offers' => $offers,
            'totalPages' => (int) ceil($totalOffers / $limit),
            'page' => $page,
            'csrf_token' => Util::getCSRFToken(),
            'user' => Util::getUser() ?? null
        ]);
    } catch (Exception $e) {
        error_log($e->getMessage());
        http_response_code(500);
        die("Erreur système.");
    }
    exit;
}

// 404
http_response_code(404);
die("Page non trouvée.");