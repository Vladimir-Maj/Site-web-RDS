<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Util;
use App\Repository\OfferRepository;
use App\Repository\CompanyRepository;
use App\Repository\WishlistRepository;
use App\Models\OfferModel;
use PDO;
use Twig\Environment;

class OfferController extends BaseController
{
    public function __construct(
        Environment $twig,
        private readonly OfferRepository $offerRepository,
        protected readonly PDO $pdo
    ) {
        parent::__construct($twig);

        // Ensure a CSRF token exists for form security
        if (Util::getCSRFToken() === null) {
            Util::setCSRFToken(bin2hex(random_bytes(32)));
        }
    }

    /**
     * Helper to verify token and abort if invalid
     */
    private function validateCSRF(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || $token !== Util::getCSRFToken()) {
            $this->abort(403, "Requête invalide (CSRF Token mismatch).");
        }
    }

    public function edit(string $id): void
    {
        $this->abortIfNotPriv();

        $offer = $this->offerRepository->findById((int) $id);
        if (!$offer) {
            $this->abort(404, "Offre introuvable.");
        }

        $siteRepo = new CompanyRepository($this->pdo);
        // Supports both old and new schema naming during transition
        $companyId = $offer->company_id_company_site ?? $offer->company_id ?? null;
        $sites = $companyId ? $siteRepo->findSitesByCompany((int) $companyId) : [];

        echo $this->twig->render('offers/offer_editor.html.twig', [
            'mode' => 'edit',
            'offer' => $offer,
            'sites' => $sites,
            'error' => null,        // ← add this
            'companies' => [],      // ← add this (edit mode doesn't need the full list, AJAX handles it)
            'csrf_token' => Util::getCSRFToken(),
            'sidebar_active' => 'offers'
        ]);
    }

    public function update(string $id): void
    {
        $this->abortIfNotPriv();
        $this->validateCSRF();

        $siteId = $_POST['site_id'] ?? ($_POST['company_site_id_internship_offer'] ?? '');

        $data = [
            'title_internship_offer' => $_POST['title'] ?? '',
            'description_internship_offer' => $_POST['description'] ?? '',
            'hourly_rate_internship_offer' => !empty($_POST['hourly_rate']) ? (float) $_POST['hourly_rate'] : 0.0,
            'is_active_internship_offer' => isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1,
            'start_date_internship_offer' => $_POST['start_date'] ?? null,
            'duration_weeks_internship_offer' => !empty($_POST['duration_weeks']) ? (int) $_POST['duration_weeks'] : null,
            'company_site_id_internship_offer' => $siteId,
        ];

        if ($this->offerRepository->update((int) $id, $data)) {
            header("Location: /app/offers/show/$id?updated=1");
            exit;
        }

        $this->abort(500, "Erreur lors de la mise à jour.");
    }

    public function index(): void
    {
        $limit = 10;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($page - 1) * $limit;

        $filters = [
            'title_internship_offer' => $_GET['q'] ?? null,
            'city_company_site' => $_GET['city'] ?? null,
        ];

        $offers = $this->offerRepository->findPaginated($limit, $offset);
        $totalOffers = $this->offerRepository->countAll();
        $totalPages = (int) ceil($totalOffers / $limit);

        echo $this->twig->render('offers/index.html.twig', [
            'offers' => $offers,
            'filters' => $filters,
            'page' => $page,
            'totalPages' => $totalPages,
            'isPrivileged' => $this->isPrivileged(),
            'sidebar_active' => 'offers'
        ]);

    }

    public function search(): void
    {
        $filters = [
            'title_internship_offer' => $_GET['keyword'] ?? null,
            'city_company_site' => $_GET['city'] ?? null,
            'duration_weeks_internship_offer' => !empty($_GET['duration']) ? (int) $_GET['duration'] : null,
            'sort' => $_GET['sort'] ?? 'recent',
        ];

        $limit = 10;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($page - 1) * $limit;

        $results = $this->offerRepository->advancedSearch($filters, $limit, $offset);

        $offers = $results['data'] ?? [];
        $totalCount = $results['total'] ?? 0;
        $totalPages = (int) ceil($totalCount / $limit);

        echo $this->twig->render('offers/offer_search.html.twig', [
            'offers' => $offers,
            'filters' => $filters,
            'count' => $totalCount,
            'page' => $page,
            'totalPages' => $totalPages,
            'isPrivileged' => $this->isPrivileged(),
            'sidebar_active' => 'offers'
        ]);
    }

    public function searchJson(): void
    {
        $filters = [
            'title_internship_offer' => $_GET['keyword'] ?? null,
            'city_company_site' => $_GET['city'] ?? null,
            'duration_weeks_internship_offer' => !empty($_GET['duration']) ? (int) $_GET['duration'] : null,
            'sort' => $_GET['sort'] ?? 'recent',
        ];

        $limit = 10;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($page - 1) * $limit;

        $results = $this->offerRepository->advancedSearch($filters, $limit, $offset);

        $this->jsonResponse([
            'data' => $results['data'] ?? [],
            'total' => $results['total'] ?? 0,
            'page' => $page,
            'total_pages' => (int) ceil(($results['total'] ?? 0) / $limit),
        ]);
    }

    public function show(int $id): void
    {
        $offer = $this->offerRepository->findById((int) $id);

        if (!$offer) {
            $this->abort(404, "L'offre d'internat introuvable.");
        }

        $isWishlisted = false;
        if (Util::getRole()?->value === 'student') {
            $wishlistRepo = new WishlistRepository($this->pdo);
            $isWishlisted = $wishlistRepo->exists((int) Util::getUserId(), (int) $offer->id);
        }

        echo $this->twig->render('offers/show.html.twig', [
            'offer' => $offer,
            'isPrivileged' => $this->isPrivileged(),
            'csrf_token' => Util::getCSRFToken(),
            'isWishlisted' => $isWishlisted,
        ]);
    }

    public function create(): void
    {
        $this->abortIfNotPriv();

        $rep = new CompanyRepository($this->pdo);
        $companies = $rep->findAllActive();

        echo $this->twig->render('offers/offer_editor.html.twig', [
            'mode' => 'create',
            'error' => null,
            'companies' => $companies,
            'sites' => [],
            'isPrivileged' => $this->isPrivileged(),
            'offer' => new OfferModel(),
            'csrf_token' => Util::getCSRFToken(),
            'sidebar_active' => 'offers'
        ]);
    }

    public function store(): void
    {
        $this->abortIfNotPriv();
        $this->validateCSRF();

        $siteId = $_POST['site_id'] ?? ($_POST['company_site_id_internship_offer'] ?? '');

        $data = [
            'title_internship_offer' => $_POST['title'] ?? '',
            'description_internship_offer' => $_POST['description'] ?? null,
            'hourly_rate_internship_offer' => !empty($_POST['hourly_rate']) ? (float) $_POST['hourly_rate'] : null,
            'start_date_internship_offer' => $_POST['start_date'] ?? null,
            'duration_weeks_internship_offer' => !empty($_POST['duration_weeks']) ? (int) $_POST['duration_weeks'] : null,
            'company_site_id_internship_offer' => $siteId,
        ];

        if ($this->offerRepository->create($data)) {
            header('Location: /app/offers?success=1');
            exit;
        }

        $this->abort(500, "Erreur lors de la création de l'offre.");
    }

    public function destroy(int $id): void
    {
        $this->abortIfNotPriv(); // Added security check from common logic
        if ($this->offerRepository->delete((int) $id)) {
            header('Location: /app/offers?deleted=1');
            exit;
        }

        $this->abort(400, "Impossible de supprimer cette offre.");
    }
}
