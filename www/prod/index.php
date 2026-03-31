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
use App\Controller\SiteController;
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

// --- WEB ROUTES: OFFER MANAGEMENT ---
if (str_starts_with($path, '/app/offers')) {
    $offerRepo = new \App\Repository\OfferRepository($pdo);
    $offerCtrl = new \App\Controllers\OfferController($twig, $offerRepo, $pdo);
    $method = $_SERVER['REQUEST_METHOD'];

    // 1. Search Route (The Board)
    if ($path === '/app/offers/search') {
        $offerCtrl->search();
        exit;
    }

    // 2. Creation Route
    if ($path === '/app/offers/create') {
        if ($method === 'GET') {
            $offerCtrl->create();
        } else {
            $offerCtrl->store();
        }
        exit;
    }

    // --- WEB ROUTES: OFFER MANAGEMENT ---
    if (preg_match('#^/app/offers/(show|edit|delete|update)/([a-fA-F0-9]{32})$#', $path, $matches)) {
        $action = $matches; // Action string
        $id = $matches;     // ID string (The 32-char Hex)

        switch ($action) {
            case 'show':
                // Pass $id (string), not $matches (array)
                $offerCtrl->show($id);
                break;

            case 'edit':
                $offerCtrl->edit($id);
                break;

            case 'update':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $offerCtrl->update($id);
                }
                break;

            case 'delete':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $offerCtrl->destroy($id);
                }
                break;
        }
        exit;
    }

    // 4. Default List View (if just /offers)
    if ($path === '/app/offers') {
        $offerCtrl->index();
        exit;
    }
}

// --- WEB ROUTES: SITE MANAGEMENT ---
if (str_starts_with($path, '/app/sites')) {
    $repo = new CompanyRepository($pdo);
    $siteCtrl = new SiteController($repo);
    $method = $_SERVER['REQUEST_METHOD'];

    // Handle Save (Create/Update)
    if ($path === '/app/sites/save' && $method === 'POST') {
        $siteCtrl->handleSave();
        exit;
    }

    // Handle Delete: /app/sites/delete/{hex32}
    if (preg_match('#^/app/sites/delete/([a-fA-F0-9]{32})$#', $path, $m)) {
        $siteCtrl->delete($m);
        exit;
    }
}

// --- AJAX/API ROUTES ---

// Existant
if (preg_match('#^/app/companies/([a-fA-F0-9]{32})/sites$#', $path, $m)) {
    $repo = new CompanyRepository($pdo);
    (new CompanyController($repo, $twig))->getSitesByCompany($m[1]);
    exit;
}

// Applications : GET /api/applications  (liste de l'étudiant connecté)
if ($path === '/api/applications' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    (new ApplicationController($applicationRepo, $twig))->listForStudentJson();
    exit;
}

// Applications : GET /api/applications/{id}
if (preg_match('#^/api/applications/([a-fA-F0-9-]+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    (new ApplicationController($applicationRepo, $twig))->showJson($m[1]);
    exit;
}

// Applications : DELETE /api/applications/{id}
if (preg_match('#^/api/applications/([a-fA-F0-9-]+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    (new ApplicationController($applicationRepo, $twig))->deleteJson($m[1]);
    exit;
}

// Offers : GET /api/offers/search
if ($path === '/api/offers/search' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    (new OfferController($twig, $offerRepo, $pdo))->searchJson();
    exit;
}

// Users : GET /api/users/{id}
if (preg_match('#^/api/users/([a-fA-F0-9-]+)$#', $path, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    (new UserController($userRepo, $twig))->getUserByIdJson($m[1]);
    exit;
}

// 404
http_response_code(404);
die("Page non trouvée.");
