<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Util;
use App\Repository\OfferRepository;
use App\Repository\CompanyRepository;
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

        // Ensure a CSRF token exists
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

    /**
     * GET /offers/edit/{id}
     */
    public function edit(string $id): void
    {
        $this->abortIfNotPriv();

        $offer = $this->offerRepository->findById($id);
        if (!$offer) {
            $this->abort(404, "Offre introuvable.");
        }

        $siteRepo = new CompanyRepository($this->pdo);
        $sites = $siteRepo->findSitesByCompany($offer->company_id);

        echo $this->twig->render('offers/offer_editor.html.twig', [
            'mode' => 'edit',
            'offer' => $offer,
            'sites' => $sites,
            'csrf_token' => Util::getCSRFToken() // Pass to view
        ]);
    }

    /**
     * POST /offers/update/{id}
     */
    public function update(string $id): void
    {
        $this->abortIfNotPriv();
        $this->validateCSRF(); // SAFE

        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'hourly_rate' => !empty($_POST['hourly_rate']) ? (float) $_POST['hourly_rate'] : 0.0,
            'is_active' => isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1,
            'start_date' => $_POST['start_date'] ?? null,
            'duration_weeks' => !empty($_POST['duration_weeks']) ? (int) $_POST['duration_weeks'] : null,
            'site_id' => $_POST['site_id'] ?? '', // CRITICAL: Must be passed to Repository
        ];

        if ($this->offerRepository->update($id, $data)) {
            header("Location: /offers/show/$id?updated=1");
            exit;
        }

        $this->abort(500, "Erreur lors de la mise à jour.");
    }

    /**
     * GET /offers
     */
    public function index(): void
    {
        $limit = 10;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($page - 1) * $limit;

        $filters = [
            'keyword' => $_GET['q'] ?? null,
            'city' => $_GET['city'] ?? null,
        ];

        $offers = $this->offerRepository->findPaginated($limit, $offset);
        $totalOffers = $this->offerRepository->countAll();
        $totalPages = (int) ceil($totalOffers / $limit);

        echo $this->twig->render('offers/index.html.twig', [
            'offers' => $offers,
            'filters' => $filters,
            'page' => $page,
            'totalPages' => $totalPages,
            'isPrivileged' => $this->isPrivileged()
        ]);
    }

    /**
     * GET /offers/search
     * Handles complex filtering, sorting, and pagination/offers/new
     */
    public function search(): void
    {
        // 1. Capture Filters from GET
        $filters = [
            'keyword' => $_GET['keyword'] ?? null,
            'city' => $_GET['city'] ?? null,
            'duration' => !empty($_GET['duration']) ? (int) $_GET['duration'] : null,
            'sort' => $_GET['sort'] ?? 'recent'
        ];

        // 2. Handle Pagination Logic
        $limit = 10;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($page - 1) * $limit;

        // 3. Fetch Data from Repository
        // Note: You'll need a search method in your Repo that supports these keys
        $results = $this->offerRepository->advancedSearch($filters, $limit, $offset);

        // We assume the Repo returns an array with 'data' and 'total'
        $offers = $results['data'] ?? [];
        $totalCount = $results['total'] ?? 0;
        $totalPages = ceil($totalCount / $limit);

        // 4. Render the Modernized View
        echo $this->twig->render('offers/offer_search.html.twig', [
            'offers' => $offers,
            'filters' => $filters,
            'count' => $totalCount,
            'page' => $page,
            'totalPages' => $totalPages,
            'isPrivileged' => $this->isPrivileged()
        ]);
    }

    /**
     * GET /offers/{id}
     */
    public function show(string $id): void
    {
        $offer = $this->offerRepository->findById($id);

        if (!$offer) {
            $this->abort(404, "L'offre d'internat introuvable.");
        }

        echo $this->twig->render('offers/show.html.twig', [
            'offer' => $offer,
            'isPrivileged' => $this->isPrivileged(),
            'csrf_token' => Util::getCSRFToken() // Pass to view for
        ]);
    }
    /**
     * GET /app/offers/new (The Form)
     */
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
            'csrf_token' => Util::getCSRFToken() // Pass to view
        ]);
    }

    /**
     * POST /offers/store
     */
    public function store(): void
    {
        $this->abortIfNotPriv();
        $this->validateCSRF(); // SAFE

        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? null,
            'hourly_rate' => !empty($_POST['hourly_rate']) ? (float) $_POST['hourly_rate'] : null,
            'start_date' => $_POST['start_date'] ?? null,
            'duration_weeks' => !empty($_POST['duration_weeks']) ? (int) $_POST['duration_weeks'] : null,
            'site_id' => $_POST['site_id'] ?? ''
        ];

        if ($this->offerRepository->create($data)) {
            header('Location: /offers?success=1');
            exit;
        }

        $this->abort(500, "Erreur lors de la création de l'offre.");
    }

    /**
     * POST /offers/delete/{id}
     */
    public function destroy(string $id): void
    {
        if ($this->offerRepository->delete($id)) {
            header('Location: /app/offers?deleted=1');
            exit;
        }

        $this->abort(400, "Impossible de supprimer cette offre.");
    }
}