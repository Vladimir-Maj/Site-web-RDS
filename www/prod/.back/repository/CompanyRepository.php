<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use Exception;
use App\Models\CompanyModel;
use App\Models\CompanySiteModel;

class CompanyRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findAllSectors(): array
    {
        $stmt = $this->pdo->query("SELECT HEX(id) as id, name FROM business_sector ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Helper pour le SELECT de base avec conversion HEX
     */
    private function getBaseSelect(): string
    {
        return "SELECT 
                HEX(id) as id, 
                name, 
                description, 
                siren, 
                email,   -- Added
                phone,   -- Added
                is_active, 
                HEX(sector_id) as sector_id 
            FROM company";
    }

    /**
     * Sécurité : Vérifie si une chaîne est un hexadécimal valide (32 chars pour UUID/Binary16)
     */
    private function isValidHex(?string $hex): bool
    {
        if ($hex === null)
            return false;
        return ctype_xdigit($hex) && (strlen($hex) % 2 === 0);
    }

    /**
     * Récupère toutes les entreprises actives
     */
    public function findAllActive(): array
    {
        $stmt = $this->pdo->query($this->getBaseSelect() . " WHERE is_active = 1");
        return array_map(fn($row) => CompanyModel::fromArray($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Récupère une entreprise par ID avec validation Hex
     */
    public function getById(string $id): ?CompanyModel
    {
        if (!$this->isValidHex($id))
            return null;

        $stmt = $this->pdo->prepare($this->getBaseSelect() . " WHERE id = UNHEX(?)");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row)
            return null;

        $company = CompanyModel::fromArray($row);
        $company->sites = $this->findSitesByCompany($id);

        return $company;
    }

    /**
     * Recherche par SIREN (protection injection standard via PDO)
     */
    public function getBySiren(string $siren): ?CompanyModel
    {
        $stmt = $this->pdo->prepare($this->getBaseSelect() . " WHERE siren = ?");
        $stmt->execute([$siren]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row)
            return null;

        $company = CompanyModel::fromArray($row);
        $company->sites = $this->findSitesByCompany($company->id);

        return $company;
    }

    /**
     * Recherche floue par nom
     */
    public function getByName(string $name): array
    {
        $stmt = $this->pdo->prepare($this->getBaseSelect() . " WHERE name LIKE ?");
        $stmt->execute(['%' . $name . '%']);

        return array_map(fn($row) => CompanyModel::fromArray($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Sauvegarde ou Mise à jour avec Transaction
     */
    public function push(CompanyModel $company): bool
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Gestion de l'ID : Si 'new' ou invalide, on génère un nouvel Hex
            if (!$this->isValidHex($company->id)) {
                $company->id = bin2hex(random_bytes(16));
            }

            // 2. Sécurité sector_id : Validation du format hexadécimal
            $sectorId = $this->isValidHex($company->sector_id)
                ? $company->sector_id
                : str_repeat('0', 32);

            // 3. Requête SQL incluant les nouveaux champs contact (email, phone, siren)
            $sql = "INSERT INTO company (id, name, description, siren, email, phone, is_active, sector_id) 
                VALUES (UNHEX(:id), :name, :description, :siren, :email, :phone, :is_active, UNHEX(:sector_id))
                ON DUPLICATE KEY UPDATE 
                name = VALUES(name), 
                description = VALUES(description), 
                siren = VALUES(siren),
                email = VALUES(email),
                phone = VALUES(phone),
                is_active = VALUES(is_active), 
                sector_id = VALUES(sector_id)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id' => $company->id,
                'name' => $company->name,
                'description' => $company->description,
                'siren' => $company->siren,
                'email' => $company->email ? $company->email->asString() : null, // Gestion du null si optionnel
                'phone' => $company->phone ?? null, // Gestion du null si optionnel
                'is_active' => (int) $company->is_active,
                'sector_id' => $sectorId
            ]);

            // 4. Sauvegarde des sites liés (Cascade manuelle)
            if (!empty($company->sites)) {
                foreach ($company->sites as $site) {
                    $site->company_id = $company->id; // On force la FK vers l'entreprise parente
                    $this->pushSite($site);
                }
            }

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // Log ou Debug
            // error_log("Erreur SQL dans push(): " . $e->getMessage());
            die("Erreur SQL : " . $e->getMessage());
            return false;
        }
    }

    public function search(array $filters): array
    {
        $limit = (int) ($filters['limit'] ?? 10);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        // 1. Updated 'business_sector' table name and JOIN alias
        $sql = "SELECT HEX(c.id) as id, c.name, c.description, c.siren, c.email, c.phone, 
                   c.is_active, HEX(c.sector_id) as sector_id, s.name as sector_name 
            FROM company c 
            LEFT JOIN business_sector s ON c.sector_id = s.id 
            WHERE 1=1";

        $params = [];

        // Filter by Name or SIREN
        if (!empty($filters['name'])) {
            // We use the SAME placeholder twice, but PDO requires either 
            // unique names or specific binding behavior depending on the driver.
            // To be safe, we use two different names.
            $sql .= " AND (c.name LIKE :name_search OR c.siren LIKE :siren_search)";
            $params['name_search'] = '%' . $filters['name'] . '%';
            $params['siren_search'] = '%' . $filters['name'] . '%';
        }

        // Filter by Sector
        if (!empty($filters['sector_id']) && $this->isValidHex($filters['sector_id'])) {
            $sql .= " AND c.sector_id = UNHEX(:sector_id)";
            $params['sector_id'] = $filters['sector_id'];
        }

        // Filter by Status
        if (!empty($filters['status'])) {
            $sql .= " AND c.is_active = :is_active";
            $params['is_active'] = ($filters['status'] === 'active') ? 1 : 0;
        }

        // Add Pagination (Note: LIMIT/OFFSET placeholders)
        $sql .= " ORDER BY c.name ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);

        // Bind all dynamic filters
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        // Bind integers for pagination (MANDATORY PARAM_INT for LIMIT)
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les sites d'une entreprise
     */
    public function findSitesByCompany(string $companyId): array
    {
        if (!$this->isValidHex($companyId))
            return [];

        $stmt = $this->pdo->prepare("
            SELECT HEX(id) as id, address, city, tax_id, HEX(company_id) as company_id 
            FROM company_site 
            WHERE company_id = UNHEX(?)
        ");
        $stmt->execute([$companyId]);

        return array_map(fn($row) => CompanySiteModel::fromArray($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Sauvegarde ou Mise à jour d'un site
     */
    public function pushSite(CompanySiteModel $site): bool
    {
        if (!$this->isValidHex($site->id)) {
            $site->id = bin2hex(random_bytes(16));
        }

        $sql = "INSERT INTO company_site (id, address, city, tax_id, company_id) 
                VALUES (UNHEX(:id), :address, :city, :tax_id, UNHEX(:company_id))
                ON DUPLICATE KEY UPDATE 
                address = VALUES(address), 
                city = VALUES(city), 
                tax_id = VALUES(tax_id)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $site->id,
            'address' => $site->address,
            'city' => $site->city,
            'tax_id' => $site->tax_id,
            'company_id' => $site->company_id
        ]);
    }

    /**
     * Supprime une entreprise et ses sites
     */
    public function deleteById(string $id): bool
    {
        if (!$this->isValidHex($id))
            return false;

        try {
            $this->pdo->beginTransaction();

            // On supprime d'abord les sites pour respecter les FK
            $this->deleteSitesByCompany($id);

            $stmt = $this->pdo->prepare("DELETE FROM company WHERE id = UNHEX(?)");
            $res = $stmt->execute([$id]);

            $this->pdo->commit();
            return $res;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction())
                $this->pdo->rollBack();
            return false;
        }
    }

    public function deleteSitesByCompany(string $companyId): bool
    {
        if (!$this->isValidHex($companyId))
            return false;
        $stmt = $this->pdo->prepare("DELETE FROM company_site WHERE company_id = UNHEX(?)");
        return $stmt->execute([$companyId]);
    }

    /**
     * Supprime un site spécifique par son ID
     */
    public function deleteSiteById(string $siteId): bool
    {
        if (!$this->isValidHex($siteId)) {
            return false;
        }

        $stmt = $this->pdo->prepare("DELETE FROM company_site WHERE id = UNHEX(?)");
        return $stmt->execute([$siteId]);
    }
}