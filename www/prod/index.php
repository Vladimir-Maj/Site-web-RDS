<?php
// prod/index.php
declare(strict_types=1);

use App\Controllers\CVFast;
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
use App\Controllers\DashboardController;
use App\Util;

// --- 1. SESSION & SECURITY ---
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

// --- 2. REQUIRES & FACTORIES ---
require_once __DIR__ . '/.back/util/config.php';
require_once __DIR__ . '/.back/util/Router.php';

$twig = TwigFactory::getTwig();

// --- 3. HELPERS ---
function jsonResponse(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// --- 4. ROUTER SETUP ---
$router = new Router($pdo, $twig);

$staff = [RoleEnum::Admin->value, RoleEnum::Pilote->value];
$everyone = [RoleEnum::Admin->value, RoleEnum::Pilote->value, RoleEnum::Student->value];
$student = [RoleEnum::Student->value];

// --- 5. ROUTES ---

// ── AUTH & REGISTRATION ──────────────────────────────────────────────────────
$authHandler = fn($pdo, $twig) => new AuthController(new UserRepository($pdo), $twig, $pdo);
$cvHandler = fn($pdo, $twig) => new CVFast(new UserRepository($pdo), $twig, $pdo);

$router->add('GET',  '/login',    fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->login());
$router->add('POST', '/login',    fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->login());
$router->add('GET',  '/logout',   fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->logout());
$router->add('GET',  '/register', fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->register());
$router->add('POST', '/register', fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->register());

$router->add('GET',  '/profile',           fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->profile(), roles: $everyone);
$router->add('POST', '/profile',           fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->profile(), roles: $everyone);
$router->add('POST', '/dashboard/profile/upload-cv', fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->uploadCv(), roles: $everyone);
$router->add('GET', '/dashboard/profile/upload-cv', fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->uploadCv(), roles: $everyone);


$router->add('GET', '/api/profile/get-cvs',        fn($p, $pdo, $twig) => $cvHandler($pdo, $twig)->ajaxGetAll(Util::getUserId()), roles: RoleEnum::Student);

// ── DASHBOARDS (Integrated from dashboard branch) ───────────────────────────
$dashHandler = fn($pdo, $twig) => new DashboardController($twig);
$compHandler = fn($pdo, $twig) => new CompanyController(new CompanyRepository($pdo), $twig);

$router->add('GET', '/admin/dashboard',  fn($p, $pdo, $twig) => $dashHandler($pdo, $twig)->index(), roles: [RoleEnum::Admin->value]);
$router->add('GET', '/pilote/dashboard', fn($p, $pdo, $twig) => $dashHandler($pdo, $twig)->index(), roles: [RoleEnum::Pilote->value]);
$router->add('GET', '/dashboard/pilotes',   fn($p, $pdo, $twig) => $dashHandler($pdo, $twig)->pilots(), roles: [RoleEnum::Admin->value]);
$router->add('GET', '/dashboard/etudiants', fn($p, $pdo, $twig) => $dashHandler($pdo, $twig)->students(), roles: $staff);
$router->add('GET',  '/dashboard/companies',          fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->renderList(), roles: $staff);

// ── COMPANIES ────────────────────────────────────────────────────────────────

$router->add('GET',  '/dashboard/companies/new',      fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->renderForm('new'), roles: $staff);
$router->add('POST', '/dashboard/companies/new',      fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->handleFormSave('new'), roles: $staff);
$router->add('GET',  '/dashboard/companies/([a-fA-F0-9]{32})', fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->renderForm($p[0]), roles: $staff);
$router->add('POST', '/dashboard/companies/([a-fA-F0-9]{32})', fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->handleFormSave($p[0]), roles: $staff);

// ── OFFERS ───────────────────────────────────────────────────────────────────
$offerHandler = fn($pdo, $twig) => new OfferController($twig, new OfferRepository($pdo), $pdo);

$router->add('GET', '/', function ($p, $pdo, $twig) {
    $offerRepo = new OfferRepository($pdo);
    $limit = 5;
    $page = (int)(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
    $offset = (max(1, $page) - 1) * $limit;
    echo $twig->render('index.html.twig', [
        'offers' => $offerRepo->findPaginated($limit, $offset),
        'totalPages' => (int) ceil($offerRepo->countAll() / $limit),
        'page' => $page,
    ]);
});

$router->add('GET',  '/dashboard/offers',        fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->index());
$router->add('GET',  '/app/offers/search', fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->search());
$router->add('GET',  '/dashboard/offers/new',    fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->create(), roles: $staff);
$router->add('POST', '/dashboard/offers/new',    fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->store(), roles: $staff);
$router->add('GET',  '/app/offers/show/([a-fA-F0-9]{32})',   fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->show($p[0]));
$router->add('GET',  '/app/offers/edit/([a-fA-F0-9]{32})',   fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->edit($p[0]), roles: $staff);
$router->add('POST', '/app/offers/update/([a-fA-F0-9]{32})', fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->update($p[0]), roles: $staff);
$router->add('POST', '/app/offers/delete/([a-fA-F0-9]{32})', fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->destroy($p[0]), roles: $staff);

// ── SITES ────────────────────────────────────────────────────────────────────
$siteHandler = fn($pdo, $twig) => new SiteController(new CompanySiteRepository($pdo), new CompanyRepository($pdo), $twig);

$router->add('GET',  '/dashboard/companies/([a-fA-F0-9]{32})/sites', fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->index($p[0]), roles: $staff);
$router->add('GET',  '/app/sites/new',      fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->new(), roles: $staff);
$router->add('POST', '/app/sites/save',     fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->handleSave(), roles: $staff);
$router->add('GET',  '/app/sites/([a-fA-F0-9]{32})',           fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->show($p[0]), roles: $staff);
$router->add('POST', '/app/sites/delete/([a-fA-F0-9]{32})',    fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->delete($p[0]), roles: $staff);
$router->add('GET', '/api/companies/([a-fA-F0-9]{32})/sites', fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->getSitesByCompany($p[0]), roles: $staff);

// ── SKILLS ───────────────────────────────────────────────────────────────────
$skillHandler = fn($pdo, $twig) => new SkillController(new SkillRepository($pdo), $twig);

$router->add('GET',    '/app/skills',        fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->index(), roles: $staff);
$router->add('GET',    '/api/skills',        fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->listJson());
$router->add('POST',   '/api/skills/create', fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->createAjax(), roles: $staff);
$router->add('PATCH',  '/api/skills/update/([a-fA-F0-9]{32})', fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->updateAjax($p), roles: $staff);
$router->add('DELETE', '/api/skills/delete/([a-fA-F0-9]{32})', fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->deleteAjax($p), roles: $staff);

// ── APPLICATIONS ─────────────────────────────────────────────────────────────
$appHandler = fn($pdo, $twig) => new ApplicationController(new ApplicationRepository($pdo), new OfferRepository($pdo), $twig);

$router->add('GET',    '/app/offers/([a-fA-F0-9]{32})/apply',        fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->viewApply($p[0]), roles: $student);
$router->add('POST',   '/app/offers/([a-fA-F0-9]{32})/apply',        fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->doApply($p[0]), roles: $student);
$router->add('PATCH',  '/api/applications/([a-fA-F0-9]{32})/status', fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->updateStatusAjax($p), roles: $staff);
// --- 6. DISPATCH ---
$router->run($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);