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
    public function index(string $companyId): void
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
    // App/Controller/SiteController.php

    public function getSitesJson(string $companyId): void
    {
        // This will show up in your terminal/CLI
        error_log("[AJAX] Request received for Company HEX: " . $companyId);

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
        $companyId = $_GET['company_id'] ?? null;
        if (!$companyId) {
            header("Location: /app/companies");
            exit;
        }

        $company = $this->companyRepository->getById($companyId);

        // Create an empty model instance for the form
        $site = new CompanySiteModel(null);
        $site->company_id = $companyId;

        echo $this->twig->render('sites/form.html.twig', [
            'company' => $company,
            'site' => $site
        ]);
    }

    /**
     * GET /app/sites/{id}
     */
    public function show(string $id): void
    {
        $site = $this->repo->getById($id);

        if (!$site) {
            throw new Exception("Site not found.");
        }

        $company = $this->companyRepository->getById($site->company_id);

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
            $companyId = $_POST['company_id'] ?? null;
            if (!$companyId) {
                throw new Exception("Company ID missing");
            }

            $isNew = empty($_POST['id']) || $_POST['id'] === 'new';

            // Logic check: We use the ID from POST if editing, or generate one if new
            $id = $isNew ? bin2hex(random_bytes(16)) : $_POST['id'];

            // Merging data to ensure the ID is passed into the model factory
            $data = array_merge($_POST, ['id' => $id]);
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
    public function delete(string $id): void
    {
        $companyId = $_POST['company_id'] ?? null;
        $this->repo->deleteSiteById($id);

        header("Location: /app/companies/{$companyId}?site_deleted=1");
        exit;
    }
}