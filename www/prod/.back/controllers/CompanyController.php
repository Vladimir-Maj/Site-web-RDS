<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CompanyModel;
use App\Models\CompanySiteModel;
use App\Repository\CompanyRepository;
use App\Util;
use Exception;
use Twig\Environment;

class CompanyController extends BaseController
{
    /**
     * Modernized Constructor using Property Promotion
     */
    public function __construct(
        private CompanyRepository $repo,
        protected Environment $twig
    ) {}

    // -------------------------------------------------------------------------
    // API / AJAX Methods
    // -------------------------------------------------------------------------

    /**
     * API Endpoint: GET /api/companies/{id}/sites
     * Used by the Offer Editor to populate the site dropdown dynamically.
     */
    public function getSitesByCompany(string $companyId): void
    {
        if (empty($companyId)) {
            $this->jsonResponse(['error' => 'Invalid or missing Company ID'], 400);
            return;
        }

        try {
            $sites = $this->repo->findSitesByCompany($companyId);
            $this->jsonResponse($sites);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => 'Database error'], 500);
        }
    }

    

    // -------------------------------------------------------------------------
    // View Rendering Methods
    // -------------------------------------------------------------------------

    /**
     * Renders the list of companies with optional filters
     */
    public function renderList(): void
    {
        $sectors = $this->repo->findAllSectors(); 

        $filters = [
            'name'      => $_GET['name'] ?? null,
            'sector_id' => $_GET['sector_id'] ?? null,
            'status'    => $_GET['status'] ?? null,
            'page'      => (int) ($_GET['page'] ?? 1),
            'limit'     => 10
        ];

        $companies = $this->repo->search($filters);

        echo $this->twig->render('companies/company_list.html.twig', [
            'companies' => $companies,
            'sectors'   => $sectors,
            'filters'   => $filters,
            'csrf'      => Util::getCSRFToken(),
            'app' => [
                'request' => ['query' => $_GET, 'uri' => $_SERVER['REQUEST_URI']]
            ]
        ]);
    }

    /**
     * Renders the Creation/Edition form
     */
    public function renderForm(string $id): void
    {
        $isNew = ($id === 'new');
        $company = $isNew ? null : $this->repo->getById($id);
        $sectors = $this->repo->findAllSectors();

        echo $this->twig->render('companies/company_form.html.twig', [
            'id'         => $id,
            'company'    => $company,
            'sectors'    => $sectors,
            'csrf_token' => Util::getCSRFToken(),
            'error'      => $_SESSION['flash_error'] ?? null,
            'success'    => $_GET['success'] ?? null
        ]);
        
        unset($_SESSION['flash_error']);
    }

    // -------------------------------------------------------------------------
    // Persistence & Logic
    // -------------------------------------------------------------------------

    /**
     * Unified handler for POST /app/companies/{id}
     */
    public function handleFormSave(string $id): void
    {
        try {
            $isNew = ($id === 'new');
            $finalId = $isNew ? bin2hex(random_bytes(16)) : $id;

            // Merge ID into POST data and hydrate
            $data = array_merge($_POST, ['id' => $finalId]);
            $company = CompanyModel::fromArray($data);

            $this->validateCompany($company);

            if (!$this->repo->push($company)) {
                throw new Exception("Échec de l'enregistrement en base de données.");
            }

            $redirectUrl = "/app/companies/{$finalId}?" . ($isNew ? "created=1" : "success=1");
            header("Location: $redirectUrl");
            exit;

        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }

    /**
     * Deletes a company and its associated sites
     */
    public function deleteCompany(string $id): void
    {
        try {
            $this->repo->deleteSitesByCompany($id);
            if (!$this->repo->deleteById($id)) {
                throw new Exception("Deletion failed.");
            }
            header("Location: /app/companies?deleted=1");
        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Erreur lors de la suppression : " . $e->getMessage();
            header("Location: /app/companies");
        }
        exit;
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------

    private function validateCompany(CompanyModel $company): void
    {
        if (empty($company->name)) throw new \InvalidArgumentException("Le nom est obligatoire.");
        if (empty($company->siren)) throw new \InvalidArgumentException("Le SIREN est obligatoire.");
        if (strlen($company->siren) !== 9) throw new \InvalidArgumentException("Le SIREN doit comporter 9 chiffres.");
    }
}