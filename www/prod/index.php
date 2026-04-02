<?php
// prod/index.php
declare(strict_types=1);

use App\Controllers\CVFast;
use App\Models\RoleEnum;
use App\Repository\CompanyRepository;
use App\Repository\CompanySiteRepository;
use App\Repository\SkillRepository;
use App\Repository\UserRepository;
use App\Repository\ApplicationRepository;
use App\Repository\OfferRepository;
use App\Repository\WishlistRepository;
use App\Controllers\CompanyController;
use App\Controllers\SiteController;
use App\Controllers\SkillController;
use App\Controllers\UserController;
use App\Controllers\ApplicationController;
use App\Controllers\AuthController;
use App\Controllers\OfferController;
use App\Controllers\DashboardController;
use App\Controllers\WishlistController;
use App\Util;

// --- 1. SESSION & SECURITY ---
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
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

// --- 3. HELPERS & VIEW DATA ---
function currentRoleValue(): ?string {
    return Util::getRole()?->value;
}

function isPrivilegedUser(): bool {
    return in_array(currentRoleValue(), [
        RoleEnum::Admin->value,
        RoleEnum::Pilote->value,
    ], true);
}

function defaultViewData(array $data = []): array {
    return array_merge([
        'isLoggedIn'   => Util::isLoggedIn(),
        'isPrivileged' => isPrivilegedUser(),
        'currentUser'  => Util::getUser(),
        'currentRole'  => currentRoleValue(),
    ], $data);
}

$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Globals Twig
$twig->addGlobal('isLoggedIn', Util::isLoggedIn());
$twig->addGlobal('isPrivileged', isPrivilegedUser());
$twig->addGlobal('currentUser', Util::getUser());
$twig->addGlobal('currentRole', currentRoleValue());
$twig->addGlobal('flashMessage', $flashMessage);
$twig->addGlobal('flashType', $flashType);

// --- 4. ROUTER SETUP ---
$router = new Router($pdo, $twig);

$staff = [RoleEnum::Admin->value, RoleEnum::Pilote->value];
$everyone = [RoleEnum::Admin->value, RoleEnum::Pilote->value, RoleEnum::Student->value];
$student = [RoleEnum::Student->value];
$idPattern = '([a-fA-F0-9]{32}|[0-9]+)'; // Supports both MD5 hashes and Integer IDs

// --- 5. CONTROLLER FACTORIES ---
$authHandler = fn($pdo, $twig) => new AuthController(new UserRepository($pdo), $twig, $pdo);
$cvHandler = fn($pdo, $twig) => new CVFast(new UserRepository($pdo), $twig, $pdo);
$dashHandler = fn($pdo, $twig) => new DashboardController($twig);
$compHandler = fn($pdo, $twig) => new CompanyController(new CompanyRepository($pdo), $twig);
$offerHandler = fn($pdo, $twig) => new OfferController($twig, new OfferRepository($pdo), $pdo);
$siteHandler = fn($pdo, $twig) => new SiteController(new CompanySiteRepository($pdo), new CompanyRepository($pdo), $twig);
$skillHandler = fn($pdo, $twig) => new SkillController(new SkillRepository($pdo), $twig);
$appHandler = fn($pdo, $twig) => new ApplicationController(new ApplicationRepository($pdo), new OfferRepository($pdo), new UserRepository($pdo), $twig);
$wishlistHandler = fn($pdo, $twig) => new WishlistController(new WishlistRepository($pdo), $twig);

// --- 6. ROUTES ---

// ── AUTH & PROFILE ──────────────────────────────────────────────────────────
$router->add('GET',  '/login',    fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->login());
$router->add('POST', '/login',    fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->login());
$router->add('GET',  '/logout',   fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->logout());
$router->add('GET',  '/register', fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->register());
$router->add('POST', '/register', fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->register());

$router->add('GET',  '/profile',           fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->profile(), roles: $everyone);
$router->add('POST', '/profile',           fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->profile(), roles: $everyone);
$router->add('POST', '/profile/upload-cv', fn($p, $pdo, $twig) => $authHandler($pdo, $twig)->uploadCv(), roles: $everyone);
$router->add('GET',  '/api/profile/get-cvs', fn($p, $pdo, $twig) => $cvHandler($pdo, $twig)->ajaxGetAll(Util::getUserId()), roles: $student);

// ── HOME ─────────────────────────────────────────────────────────────────────
$router->add('GET', '/', function ($p, $pdo, $twig) {
    $offerRepo = new OfferRepository($pdo);
    $applicationRepo = new ApplicationRepository($pdo);

    $limit = 5;
    $page = (int) (filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
    $page = max(1, $page);
    $offset = ($page - 1) * $limit;
    $totalOffers = $offerRepo->countAll();

    $studentProgress = ['pending' => 0, 'accepted' => 0, 'rejected' => 0];
    $recentApplications = [];

    if (currentRoleValue() === RoleEnum::Student->value && Util::getUserId() !== null) {
        $studentId = (int) Util::getUserId();
        $studentProgress = $applicationRepo->getStudentProgress($studentId);
        $recentApplications = $applicationRepo->findStudentApplicationsOverview($studentId, 5);
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
