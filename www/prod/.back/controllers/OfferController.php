<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repository\OfferRepository;
use App\Repository\CompanyRepository;
use App\Models\OfferModel;
use PDO;
use Twig\Environment;

class OfferController extends BaseController
{
    /**
     * We call parent::__construct to ensure Twig and Sessions are initialized
     */
    public function __construct(
        Environment $twig,
        private readonly OfferRepository $offerRepository
        ,
        protected readonly PDO $pdo
    ) {
        parent::__construct($twig);
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

        // Fetch sites to populate the dropdown in the editor
        $siteRepo = new CompanyRepository($this->pdo);
        $sites = $siteRepo->findSitesByCompany($offer->company_id);

        echo $this->twig->render('offers/offer_editor.html.twig', [
            'mode' => 'edit',
            'offer' => $offer,
            'sites' => $sites
        ]);
    }

    /**
     * POST /offers/update/{id}
     */
    public function update(string $id): void
    {
        $this->abortIfNotPriv();

        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'hourly_rate' => !empty($_POST['hourly_rate']) ? (float) $_POST['hourly_rate'] : 0.0,
            'is_active' => isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1,
            // Add other fields as necessary to match your Repository::update signature
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
        // 1. Pagination Parameters
        $limit = 10;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($page - 1) * $limit;

        // 2. Capture Filters
        $filters = [
            'keyword' => $_GET['q'] ?? null,
            'city' => $_GET['city'] ?? null,
        ];

        // 3. Fetch Data & Total Count
        // We use findPaginated (for the data) and countAll (for the logic)
        $offers = $this->offerRepository->findPaginated($limit, $offset);
        $totalOffers = $this->offerRepository->countAll();
        $totalPages = (int) ceil($totalOffers / $limit);

        // 4. Render with ALL required variables
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
     * Handles complex filtering, sorting, and pagination
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
            'offer' => $offer
        ]);
    }

    /**
     * GET /app/offers/create (The Form)
     */
    /**
     * GET /app/offers/create (The Form)
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
            // Pass a new empty model instead of null
            'offer' => new OfferModel() 
        ]);
    }

    /**
     * POST /offers/store
     */
    public function store(): void
    {
        $this->abortIfNotPriv();

        // Basic CSRF/Validation logic here
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
        // Security check using your BaseController logic
        $this->abortIfNotPriv();

        if ($this->offerRepository->delete($id)) {
            header('Location: /offers?deleted=1');
            exit;
        }

        $this->abort(400, "Impossible de supprimer cette offre.");
    }
}