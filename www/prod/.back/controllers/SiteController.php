<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CompanySiteModel;
use App\Repository\CompanyRepository;
use App\Repository\CompanySiteRepository;
use Exception;
use Twig\Environment;

class SiteController extends BaseController
{
    /**
     * Merged constructor to include both repositories and Twig
     */
    public function __construct(
        private CompanySiteRepository $repo,
        private CompanyRepository $companyRepository,
        protected Environment $twig
    ) {
    }

    /**
     * GET /app/companies/{companyId}/sites
     * Displays the list of all sites for a specific company
     */
    public function index(int $companyId): void
    {
        $company = $this->companyRepository->getById($companyId);
        $sites = $this->repo->getSitesByCompany($companyId);

        echo $this->twig->render('sites/index.html.twig', [
            'company' => $company,
            'sites' => $sites
        ]);
    }

    /**
     * GET /api/companies/{companyId}/sites
     * Returns JSON for AJAX requests
     */
    public function getSitesJson(int $companyId): void
    {
        error_log("[AJAX] Request received for Company ID: " . $companyId);

        try {
            $sites = $this->repo->getSitesByCompany($companyId);
            error_log("[AJAX] Found " . count($sites) . " sites");

            header('Content-Type: application/json');
            echo json_encode($sites);
            exit;
        } catch (Exception $e) {
            error_log("[AJAX] ERROR: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * GET /app/sites/new?company_id={companyId}
     * Renders the creation form
     */
    public function new(): void
    {
        $companyId = isset($_GET['company_id']) ? (int) $_GET['company_id'] : null;
        if (!$companyId) {
            header("Location: /app/companies");
            exit;
        }

        $company = $this->companyRepository->getById($companyId);

        $site = new CompanySiteModel(null);
        $site->company_id_company_site = $companyId;

        echo $this->twig->render('sites/form.html.twig', [
            'company' => $company,
            'site' => $site
        ]);
    }

    /**
     * GET /app/sites/{id}
     */
    public function show(int $id): void
    {
        $site = $this->repo->getById($id);

        if (!$site) {
            throw new Exception("Site not found.");
        }

        $company = $this->companyRepository->getById($site->company_id_company_site);

        if (!$company) {
            throw new Exception("Associated company not found.");
        }

        echo $this->twig->render('sites/form.html.twig', [
            'company' => $company,
            'site' => $site
        ]);
    }

    /**
     * POST /app/sites/save
     */
    public function handleSave(): void
    {
        try {
            $companyId = isset($_POST['company_id_company_site'])
                ? (int) $_POST['company_id_company_site']
                : (isset($_POST['company_id']) ? (int) $_POST['company_id'] : null);

            if (!$companyId) {
                throw new Exception("Company ID missing");
            }

            $isNew = empty($_POST['id_company_site']) && (empty($_POST['id']) || $_POST['id'] === 'new');

            $id = $isNew
                ? null
                : (isset($_POST['id_company_site']) ? (int) $_POST['id_company_site'] : (int) $_POST['id']);

            $data = array_merge($_POST, [
                'id_company_site' => $id,
                'company_id_company_site' => $companyId,
                'address_company_site' => $_POST['address_company_site'] ?? ($_POST['address'] ?? null),
                'city_company_site' => $_POST['city_company_site'] ?? ($_POST['city'] ?? null),
            ]);

            $site = CompanySiteModel::fromArray($data);

            if (!$this->repo->pushSite($site)) {
                throw new Exception("Save failed");
            }

            header("Location: /app/companies/{$companyId}?site_saved=1");
            exit;
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/app/companies'));
            exit;
        }
    }

    /**
     * POST /app/sites/delete/{id}
     */
    public function delete(int $id): void
    {
        $companyId = isset($_POST['company_id_company_site'])
            ? (int) $_POST['company_id_company_site']
            : (isset($_POST['company_id']) ? (int) $_POST['company_id'] : null);

        $this->repo->deleteSiteById($id);

        header("Location: /app/companies/{$companyId}?site_deleted=1");
        exit;
    }
}
