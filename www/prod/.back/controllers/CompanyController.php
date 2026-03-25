<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controllers\BaseController;
use App\Models\CompanyModel;
use App\Models\CompanySiteModel;
use App\Repository\CompanyRepository;
use Exception;
use PharIo\Manifest\Email;
use Twig\Environment;

class CompanyController extends BaseController
{

    private CompanyRepository $repo;
    protected Environment $twig;

    public function __construct(CompanyRepository $repo, Environment $twig)
    {
        $this->repo = $repo;
        $this->twig = $twig;
    }

    /**
     * Liste toutes les entreprises actives
     */
    public function index(): array
    {
        return $this->repo->findAllActive();
    }

    /**
     * Récupère une entreprise par ID (avec ses sites)
     *
     * @throws \InvalidArgumentException si l'ID est vide
     * @throws \RuntimeException si l'entreprise est introuvable
     */
    public function getCompany(string $id): CompanyModel
    {
        if (empty($id)) {
            throw new \InvalidArgumentException("Company ID must not be empty.");
        }

        $company = $this->repo->getById($id);

        if ($company === null) {
            throw new \RuntimeException("Company not found for ID: $id");
        }

        return $company;
    }

    /**
     * Récupère une entreprise par SIREN
     *
     * @throws \InvalidArgumentException si le SIREN est vide
     * @throws \RuntimeException si l'entreprise est introuvable
     */
    public function getCompanyBySiren(string $siren): CompanyModel
    {
        if (empty($siren)) {
            throw new \InvalidArgumentException("SIREN must not be empty.");
        }

        $company = $this->repo->getBySiren($siren);

        if ($company === null) {
            throw new \RuntimeException("Company not found for SIREN: $siren");
        }

        return $company;
    }

    /**
     * Recherche floue par nom — retourne un tableau (peut être vide)
     *
     * @throws \InvalidArgumentException si le nom est trop court
     */
    public function searchByName(string $name): array
    {
        if (strlen($name) < 2) {
            throw new \InvalidArgumentException("Search term must be at least 2 characters.");
        }

        return $this->repo->getByName($name);
    }

    /**
     * Crée ou met à jour une entreprise
     *
     * @throws \InvalidArgumentException si le modèle est invalide
     * @throws \RuntimeException en cas d'échec de la sauvegarde
     */
    public function saveCompany(CompanyModel $company): CompanyModel
    {
        $this->validateCompany($company);

        if (!$this->repo->push($company)) {
            throw new \RuntimeException("Failed to save company: $company->name");
        }

        return $this->getCompany($company->id);
    }

    /**
     * Supprime une entreprise et tous ses sites
     *
     * @throws \RuntimeException si l'entreprise est introuvable ou si la suppression échoue
     */
    public function deleteCompany(string $id): void
    {
        // Ensures the company exists before attempting deletion
        $this->getCompany($id);

        $this->repo->deleteSitesByCompany($id);

        if (!$this->repo->deleteById($id)) {
            throw new \RuntimeException("Failed to delete company with ID: $id");
        }
    }

    /**
     * Récupère les sites d'une entreprise
     *
     * @throws \RuntimeException si l'entreprise est introuvable
     */
    public function getCompanySites(string $companyId): array
    {
        $this->getCompany($companyId);

        return $this->repo->findSitesByCompany($companyId);
    }

    /**
     * Crée ou met à jour un site
     *
     * @throws \InvalidArgumentException si le modèle est invalide
     * @throws \RuntimeException en cas d'échec de la sauvegarde
     */
    public function saveSite(CompanySiteModel $site): CompanySiteModel
    {
        $this->validateSite($site);

        // Ensure the parent company exists
        $this->getCompany($site->company_id);

        if (!$this->repo->pushSite($site)) {
            throw new \RuntimeException("Failed to save site for company ID: $site->company_id");
        }

        return $site;
    }

    /**
     * Supprime un site spécifique
     *
     * @throws \RuntimeException en cas d'échec de la suppression
     */
    public function deleteSite(string $siteId): void
    {
        if (!$this->repo->deleteSiteById($siteId)) {
            throw new \RuntimeException("Failed to delete site with ID: $siteId");
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function validateCompany(CompanyModel $company): void
    {
        if (empty($company->id)) {
            throw new \InvalidArgumentException("Company ID must not be empty.");
        }
        if (empty($company->name)) {
            throw new \InvalidArgumentException("Company name must not be empty.");
        }
        if (empty($company->siren)) {
            throw new \InvalidArgumentException("Company SIREN must not be empty.");
        }
    }

    private function validateSite(CompanySiteModel $site): void
    {
        if (empty($site->id)) {
            throw new \InvalidArgumentException("Site ID must not be empty.");
        }
        if (empty($site->company_id)) {
            throw new \InvalidArgumentException("Site must be linked to a company.");
        }
        if (empty($site->address) || empty($site->city)) {
            throw new \InvalidArgumentException("Site address and city must not be empty.");
        }
    }

    /**
     * Handles POST /app/companies/{id}
     * This method is called by the router after authorization is verified.
     */
    public function handlePostSave(string $id): void
    {
        try {
            // 1. Prepare data array for the Model factory
            // Note: We use the ID from the URI to ensure the resource is correct
            $data = [
                'id' => $id,
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? null,
                'siren' => $_POST['siren'] ?? '000000000', // Default if not in form
                'sector_id' => $_POST['sector_id'] ?? '1',         // Default if not in form
                'is_active' => ($_POST['status'] ?? 'active') === 'active',
            ];

            // 2. Hydrate the model using your existing static method
            $company = CompanyModel::fromArray($data);

            // 3. Persist via your existing saveCompany logic
            $this->saveCompany($company);

            // 4. Redirect to the list or detail view on success
            header("Location: /companies?success=true");
            exit;

        } catch (Exception $e) {
            // 5. If validation or DB fails, re-render the form with the error
            echo $this->twig->render('companies/company_form.html.twig', [
                'error' => $e->getMessage(),
                'company' => $_POST, // Return the input so the user doesn't lose progress
                'id' => $id
            ]);
        }
    }

    public function renderForm(string $id): void
    {
        $company = ($id === 'new') ? null : $this->repo->getById($id);
        $sectors = $this->repo->findAllSectors(); // On récupère les secteurs ici

        echo $this->twig->render('companies/company_form.html.twig', [
            'id' => $id,
            'company' => $company,
            'sectors' => $sectors, // On les injecte dans le template
            'csrf_token' => \App\Util::getCSRFToken(),
            'error' => $_SESSION['flash_error'] ?? null,
            'success' => $_GET['success'] ?? null
        ]);
        unset($_SESSION['flash_error']);
    }

    public function renderList(): void
    {
        // Basic mapping of GET params to a filter array
        $filters = [
            'name' => $_GET['name'] ?? null,
            'sector_id' => $_GET['sector_id'] ?? null,
            'status' => $_GET['status'] ?? null,
            'page' => (int) ($_GET['page'] ?? 1),
            'limit' => 10
        ];

        $companies = $this->repo->search($filters);

        // Note: Ensure your Twig render uses 'companies' (fixed typo)
        echo $this->twig->render('companies/company_list.html.twig', [
            'companies' => $companies,
            'sectors' => $this->repo->findAllSectors(), // You'll need this for the dropdown
            'csrf_token' => \App\Util::getCSRFToken()
        ]);
    }

    /**
     * POST /app/companies/new OR /app/companies/{id}
     */
    public function handleFormSave(string $id): void
    {
        try {
            $finalId = ($id === 'new') ? bin2hex(random_bytes(16)) : $id;

            // On passe des STRINGS au fromArray, pas des Objets
            $company = CompanyModel::fromArray([
                'id' => $finalId,
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?: null,
                'email' => $_POST['email'] ?: 'temp@temp.com', // String ici
                'phone' => $_POST['phone'] ?: null,
                'siren' => $_POST['siren'] ?: '000000000',
                'sector_id' => $_POST['sector_id'] ?: str_repeat('0', 32),
                'status' => $_POST['status'] ?? 'active',
            ]);

            if (!$this->repo->push($company)) {
                throw new Exception("Échec de la sauvegarde en base de données.");
            }

            header("Location: /app/companies/{$finalId}?success=1");
            exit;

        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Erreur: " . $e->getMessage();
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}