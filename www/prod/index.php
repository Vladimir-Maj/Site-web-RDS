<?php
// prod/index.php
declare(strict_types=1);

use App\Models\ApplicationModel;
use App\Models\RoleEnum;
use App\Repository\CompanyRepository;
use App\Repository\CompanySiteRepository;
use App\Repository\SkillRepository;
use App\Repository\UserRepository;
use App\Repository\ApplicationRepository;
use App\Repository\OfferRepository;
use App\Controllers\CompanyController;
use App\Controllers\SiteController;
use App\Controllers\SkillController;
use App\Controllers\UserController;
use App\Controllers\ApplicationController;
use App\Controllers\AuthController;
use App\Controllers\OfferController;
use App\Util;

// --- 1. SESSION & SECURITY (must come before config.php) ---
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 2. REQUIRES (after session is configured) ---
require_once __DIR__ . '/.back/util/config.php';
require_once __DIR__ . '/.back/util/Router.php';

$twig = TwigFactory::getTwig();

// --- 2. HELPERS ---
function jsonResponse(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        jsonResponse(['error' => 'Invalid or missing JSON body.'], 400);
    }

    return $data;
}

// --- 3. PREDICATES ---

// TODO: Use $isSelf predicate on routes that require ownership checks
//       e.g. a student editing only their own profile: add 'predicate: $isSelf'
//       to the relevant router->add() calls below
$isSelf = fn(array $params) => ($params[0] ?? null) === Util::getUserId();

// --- 4. ROUTER SETUP ---
$router = new Router($pdo, $twig);

$staff = [RoleEnum::Admin->value, RoleEnum::Pilote->value];
$everyone = [RoleEnum::Admin->value, RoleEnum::Pilote->value, RoleEnum::Student->value];

// ── AUTH (public) ────────────────────────────────────────────────────────────

$router->add('GET', '/login', fn($p, $pdo, $twig) => (new AuthController(new UserRepository($pdo), $twig))->login());
$router->add('POST', '/login', fn($p, $pdo, $twig) => (new AuthController(new UserRepository($pdo), $twig))->login());
$router->add('GET', '/logout', fn($p, $pdo, $twig) => (new AuthController(new UserRepository($pdo), $twig))->logout());
$router->add('GET', '/register', fn($p, $pdo, $twig) => (new AuthController(new UserRepository($pdo), $twig))->register());
$router->add('POST', '/register', fn($p, $pdo, $twig) => (new AuthController(new UserRepository($pdo), $twig))->register());

// ── PROFILE (any authenticated role) ─────────────────────────────────────────

$router->add('GET', '/profile', fn($p, $pdo, $twig) => (new AuthController(new UserRepository($pdo), $twig))->profile(), roles: $everyone);
$router->add('POST', '/profile', fn($p, $pdo, $twig) => (new AuthController(new UserRepository($pdo), $twig))->profile(), roles: $everyone);
$router->add('POST', '/profile/upload-cv', fn($p, $pdo, $twig) => (new AuthController(new UserRepository($pdo), $twig))->uploadCv(), roles: $everyone);

// ── HOME / CATALOGUE ──────────────────────────────────────────────────────────

$router->add('GET', '/', function ($p, $pdo, $twig) {
    $offerRepo = new OfferRepository($pdo);
    $limit = 5;
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    $offset = (max(1, $page) - 1) * $limit;

    try {
        $offers = $offerRepo->findPaginated($limit, $offset);
        $totalOffers = $offerRepo->countAll();

        // 'user' and 'csrf_token' are now Twig globals — no need to pass them manually
        echo $twig->render('index.html.twig', [
            'offers' => $offers,
            'totalPages' => (int) ceil($totalOffers / $limit),
            'page' => $page,
        ]);
    } catch (Exception $e) {
        error_log($e->getMessage());
        http_response_code(500);
        die("Erreur système.");
    }
});

// ── COMPANIES ─────────────────────────────────────────────────────────────────

$router->add('GET', '/app/companies', fn($p, $pdo, $twig) => (new CompanyController(new CompanyRepository($pdo), $twig))->renderList(), roles: $staff);
$router->add('GET', '/app/companies/new', fn($p, $pdo, $twig) => (new CompanyController(new CompanyRepository($pdo), $twig))->renderForm('new'), roles: $staff);
$router->add('POST', '/app/companies/new', fn($p, $pdo, $twig) => (new CompanyController(new CompanyRepository($pdo), $twig))->handleFormSave('new'), roles: $staff);
$router->add('GET', '/app/companies/([^/]+)', fn($p, $pdo, $twig) => (new CompanyController(new CompanyRepository($pdo), $twig))->renderForm($p[0]), roles: $staff);
$router->add('POST', '/app/companies/([^/]+)', fn($p, $pdo, $twig) => (new CompanyController(new CompanyRepository($pdo), $twig))->handleFormSave($p[0]), roles: $staff);

// ── OFFERS ────────────────────────────────────────────────────────────────────

$offerHandler = fn($pdo, $twig) => new OfferController($twig, new OfferRepository($pdo), $pdo);

$router->add('GET', '/app/offers', fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->index());
$router->add('GET', '/app/offers/search', fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->search());
$router->add('GET', '/app/offers/new', fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->create(), roles: $staff);
$router->add('POST', '/app/offers/new', fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->store(), roles: $staff);
$router->add('GET', '/app/offers/show/([a-fA-F0-9]{32})', fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->show($p[0]));
$router->add('GET', '/app/offers/edit/([a-fA-F0-9]{32})', fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->edit($p[0]), roles: $staff);
$router->add('POST', '/app/offers/update/([a-fA-F0-9]{32})', fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->update($p[0]), roles: $staff);
$router->add('POST', '/app/offers/delete/([a-fA-F0-9]{32})', fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->destroy($p[0]), roles: $staff);

// ── SITES ─────────────────────────────────────────────────────────────────────

$siteHandler = fn($pdo, $twig) => new SiteController(
    new CompanySiteRepository($pdo),
    new CompanyRepository($pdo),
    $twig
);

$router->add('GET', '/app/companies/([a-fA-F0-9]{32})/sites', fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->index($p[0]), roles: $staff);
$router->add('GET', '/app/sites/new', fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->new(), roles: $staff);
$router->add('POST', '/app/sites/save', fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->handleSave(), roles: $staff);
$router->add('GET', '/app/sites/([a-fA-F0-9]{32})', fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->show($p[0]), roles: $staff);
$router->add('POST', '/app/sites/delete/([a-fA-F0-9]{32})', fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->delete($p[0]), roles: $staff);

// ── AJAX: sites by company ────────────────────────────────────────────────────
// NOTE: Moved to /api/ prefix to avoid duplicate route conflict with GET /app/companies/{id}/sites above


// Sites
$router->add('GET', '/api/companies/([a-fA-F0-9]{32})/sites', fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->getSitesJson($p[0]), roles: $staff);
 
// Skills
$router->add('GET',    '/api/skills',                           fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->listJson());
$router->add('POST',   '/api/skills/create',                    fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->createAjax(),      roles: $staff);
$router->add('PATCH',  '/api/skills/update/([a-fA-F0-9]{32})', fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->updateAjax($p[0]), roles: $staff);
$router->add('DELETE', '/api/skills/delete/([a-fA-F0-9]{32})', fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->deleteAjax($p[0]), roles: $staff);
 
// Applications
$appHandler = fn($pdo, $twig) => new ApplicationController(new ApplicationRepository($pdo), $twig);
 
$router->add('GET',    '/app/offers/([a-fA-F0-9]{32})/apply',       fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->viewApply($p[0]),        roles: $student);
$router->add('POST',   '/app/offers/([a-fA-F0-9]{32})/apply',       fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->doApply($p[0]),          roles: $student);
$router->add('POST',   '/api/offers/([a-fA-F0-9]{32})/apply',       fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->applyAjax($p[0]),        roles: $student);
$router->add('GET',    '/api/applications/([a-fA-F0-9]{32})',        fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->showJson($p[0]),         roles: $everyone);
$router->add('DELETE', '/api/applications/([a-fA-F0-9]{32})',        fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->deleteAjax($p[0]),       roles: $everyone);
$router->add('PATCH',  '/api/applications/([a-fA-F0-9]{32})/status', fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->updateStatusAjax($p[0]), roles: $staff);

// --- 5. DISPATCH ---
// TODO: Remove the debug session logger below before deploying to production
error_log('[SESSION DUMP] ' . print_r($_SESSION, true));
error_log(
    '[AUTH] userId=' . (Util::getUserId() ?? 'null')
    . ' role=' . (Util::getRole()?->value ?? 'null')
    . ' path=' . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
    . ' method=' . $_SERVER['REQUEST_METHOD']
);



$router->run($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
