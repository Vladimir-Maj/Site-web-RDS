<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controllers\BaseController;
use App\Models\CompanySiteModel;
use App\Repository\CompanyRepository;
use Exception;

class SiteController extends BaseController
{
    public function __construct(private CompanyRepository $repo) {}

    /**
     * POST /app/sites/save
     */
    public function handleSave(): void
    {
        try {
            $companyId = $_POST['company_id'] ?? null;
            if (!$companyId) throw new Exception("Company ID missing");

            $isNew = empty($_POST['id']) || $_POST['id'] === 'new';
            $id = $isNew ? bin2hex(random_bytes(16)) : $_POST['id'];

            $data = array_merge($_POST, ['id' => $id]);
            $site = CompanySiteModel::fromArray($data);

            if (!$this->repo->pushSite($site)) {
                throw new Exception("Save failed");
            }

            // Redirect back to the company form
            header("Location: /app/companies/{$companyId}?site_saved=1");
            exit;
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            header("Location: " . $_SERVER['HTTP_REFERER']);
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