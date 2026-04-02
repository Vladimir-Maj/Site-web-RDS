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

    echo $twig->render('index.html.twig', defaultViewData([
        'offers' => $offerRepo->findPaginated($limit, $offset),
        'totalOffers' => $totalOffers,
        'totalPages' => (int) ceil($totalOffers / $limit),
        'page' => $page,
        'studentProgress' => $studentProgress,
        'recentApplications' => $recentApplications,
    ]));
});

// ── DASHBOARDS ───────────────────────────────────────────────────────────────
$router->add('GET', '/admin/dashboard',     fn($p, $pdo, $twig) => $dashHandler($pdo, $twig)->index(), roles: [RoleEnum::Admin->value]);
$router->add('GET', '/pilote/dashboard',    fn($p, $pdo, $twig) => $dashHandler($pdo, $twig)->index(), roles: [RoleEnum::Pilote->value]);
$router->add('GET', '/dashboard/pilotes',   fn($p, $pdo, $twig) => $dashHandler($pdo, $twig)->pilots(), roles: [RoleEnum::Admin->value]);
$router->add('GET', '/dashboard/etudiants', fn($p, $pdo, $twig) => $dashHandler($pdo, $twig)->students(), roles: $staff);
$router->add('GET', '/dashboard/companies', fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->renderList(), roles: $staff);
$router->add('GET', '/dashboard/offers',    fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->index(), roles: $staff);
$router->add('GET', '/dashboard/applications', fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->myApplications(), roles: $student);
$router->add('GET', '/dashboard/applications/'.$idPattern, fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->viewStudentApplications($p), roles: $staff);

// ── COMPANIES ────────────────────────────────────────────────────────────────
$router->add('GET',  '/dashboard/companies/new',           fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->renderForm('new'), roles: $staff);
$router->add('POST', '/dashboard/companies/new',           fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->handleFormSave('new'), roles: $staff);
$router->add('GET',  '/dashboard/companies/' . $idPattern, fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->renderForm((int) $p[0]), roles: $staff);
$router->add('POST', '/dashboard/companies/' . $idPattern, fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->handleFormSave((int) $p[0]), roles: $staff);

// ── OFFERS ───────────────────────────────────────────────────────────────────
$router->add('GET',  '/app/offers',                        fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->search());
$router->add('GET',  '/app/offers/search',                 fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->search());
$router->add('GET',  '/app/offers/new',                    fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->create(), roles: $staff);
$router->add('POST', '/app/offers/new',                   fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->store(), roles: $staff);
$router->add('GET',  '/app/offers/show/' . $idPattern,     fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->show((int) $p[0]));
$router->add('GET',  '/app/offers/edit/' . $idPattern,     fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->edit($p[0]), roles: $staff);
$router->add('POST', '/app/offers/update/' . $idPattern,  fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->update($p), roles: $staff);
$router->add('POST', '/app/offers/delete/' . $idPattern,  fn($p, $pdo, $twig) => $offerHandler($pdo, $twig)->destroy((int) $p[0]), roles: $staff);

// ── SITES ────────────────────────────────────────────────────────────────────
$router->add('GET',  '/dashboard/companies/' . $idPattern . '/sites', fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->index($p), roles: $staff);
$router->add('GET',  '/app/sites/new',                               fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->new(), roles: $staff);
$router->add('POST', '/app/sites/save',                              fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->handleSave(), roles: $staff);
$router->add('GET',  '/app/sites/' . $idPattern,                     fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->show($p), roles: $staff);
$router->add('POST', '/app/sites/delete/' . $idPattern,              fn($p, $pdo, $twig) => $siteHandler($pdo, $twig)->delete($p), roles: $staff);
$router->add('GET',  '/api/companies/' . $idPattern . '/sites',      fn($p, $pdo, $twig) => $compHandler($pdo, $twig)->getSitesByCompany($p), roles: $staff);

// ── SKILLS ───────────────────────────────────────────────────────────────────
$router->add('GET',    '/app/skills',                         fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->index(), roles: $staff);
$router->add('GET',    '/api/skills',                         fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->listJson());
$router->add('POST',   '/api/skills/create',                  fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->createAjax(), roles: $staff);
$router->add('PATCH',  '/api/skills/update/' . $idPattern,    fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->updateAjax($p), roles: $staff);
$router->add('DELETE', '/api/skills/delete/' . $idPattern,    fn($p, $pdo, $twig) => $skillHandler($pdo, $twig)->deleteAjax($p), roles: $staff);

// ── APPLICATIONS ─────────────────────────────────────────────────────────────
$router->add('GET',   '/app/offers/' . $idPattern . '/apply',        fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->viewApply($p), roles: $student);
$router->add('POST',  '/app/offers/' . $idPattern . '/apply',        fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->doApply($p), roles: $student);
$router->add('PATCH', '/api/applications/' . $idPattern . '/status', fn($p, $pdo, $twig) => $appHandler($pdo, $twig)->updateStatusAjax($p), roles: $staff);

// ── WISHLIST ─────────────────────────────────────────────────────────────────
$router->add('GET',  '/app/wishlist',                       fn($p, $pdo, $twig) => $wishlistHandler($pdo, $twig)->index(), roles: $student);
$router->add('POST', '/app/wishlist/toggle/' . $idPattern, fn($p, $pdo, $twig) => $wishlistHandler($pdo, $twig)->toggle($p), roles: $student);

// --- 7. DISPATCH ---
$router->run($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
