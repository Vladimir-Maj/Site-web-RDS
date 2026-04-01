<?php
declare(strict_types=1);

namespace App\Repository;

use App\Models\CompanySiteModel;
use PDO;
use Exception;

class CompanySiteRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Saves or updates a site for the new BDD structure.
     */
    public function pushSite(CompanySiteModel $site): bool
    {
        if (empty($site->id_company_site)) {
            $sql = "INSERT INTO company_site (
                        address_company_site,
                        city_company_site,
                        company_id_company_site
                    ) VALUES (
                        :address_company_site,
                        :city_company_site,
                        :company_id_company_site
                    )";

            $params = [
                'address_company_site' => $site->address_company_site,
                'city_company_site' => $site->city_company_site,
                'company_id_company_site' => $site->company_id_company_site,
            ];

            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute($params);

            if ($success) {
                $site->id_company_site = (int) $this->db->lastInsertId();
            }

            return $success;
        }

        $sql = "UPDATE company_site
                SET
                    address_company_site = :address_company_site,
                    city_company_site = :city_company_site,
                    company_id_company_site = :company_id_company_site
                WHERE id_company_site = :id_company_site";

        $params = [
            'address_company_site' => $site->address_company_site,
            'city_company_site' => $site->city_company_site,
            'company_id_company_site' => $site->company_id_company_site,
            'id_company_site' => $site->id_company_site,
        ];

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Deletes a site by its numeric ID
     */
    public function deleteSiteById(int|string $id): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM company_site
            WHERE id_company_site = :id
        ");

        return $stmt->execute(['id' => (int) $id]);
    }

    /**
     * Fetches all sites for a company
     */
    public function getSitesByCompany(int|string $companyId): array
    {
        $sql = "SELECT
                    id_company_site,
                    id_company_site AS id,
                    address_company_site,
                    address_company_site AS address,
                    city_company_site,
                    city_company_site AS city,
                    company_id_company_site,
                    company_id_company_site AS company_id
                FROM company_site
                WHERE company_id_company_site = :company_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['company_id' => (int) $companyId]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => CompanySiteModel::fromArray($row), $rows);
    }

    /**
     * Deletes all sites associated with a specific company ID
     */
    public function deleteSitesByCompany(int|string $companyId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM company_site
            WHERE company_id_company_site = :company_id
        ");

        return $stmt->execute(['company_id' => (int) $companyId]);
    }

    public function getById(int|string $siteId): CompanySiteModel
    {
        $sql = "SELECT
                    id_company_site,
                    id_company_site AS id,
                    address_company_site,
                    address_company_site AS address,
                    city_company_site,
                    city_company_site AS city,
                    company_id_company_site,
                    company_id_company_site AS company_id
                FROM company_site
                WHERE id_company_site = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => (int) $siteId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception("Site not found.");
        }

        return CompanySiteModel::fromArray($row);
    }
}<?php
declare(strict_types=1);

namespace App\Repository;

use App\Models\CompanySiteModel;
use PDO;
use Exception;

class CompanySiteRepository
{
    public function __construct(private PDO $db)
    {
    }


    /**
     * Saves or Updates a site. 
     * If $site->id is null, the DB trigger will generate the binary(16) ID.
     */
    public function pushSite(CompanySiteModel $site): bool
    {
        if (empty($site->id)) {
            // INSERT: The ID is omitted so the TRIGGER can generate it
            $sql = "INSERT INTO company_site (address, city, tax_id, company_id) 
                VALUES (:address, :city, :tax_id, UNHEX(:company_id))";

            $params = [
                'address' => $site->address,
                'city' => $site->city,
                'tax_id' => $site->tax_id,
                'company_id' => $site->company_id, // This is a 32-char Hex string
            ];
        } else {
            // UPDATE: We must UNHEX the ID to find the correct binary row
            $sql = "UPDATE company_site 
                SET address = :address, 
                    city = :city, 
                    tax_id = :tax_id 
                WHERE id = UNHEX(:id)";

            $params = [
                'address' => $site->address,
                'city' => $site->city,
                'tax_id' => $site->tax_id,
                'id' => $site->id,
            ];
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Deletes a site by its Hex ID
     */
    public function deleteSiteById(string $hexId): bool
    {
        try {
            $this->db->beginTransaction();

            // 1️⃣ Delete dependent records FIRST
            $stmt = $this->db->prepare("
            DELETE FROM child_table 
            WHERE site_id = UNHEX(:id)
        ");
            $stmt->execute(['id' => $hexId]);

            // 2️⃣ Delete the site itself
            $stmt = $this->db->prepare("
            DELETE FROM company_site 
            WHERE id = UNHEX(:id)
        ");
            $stmt->execute(['id' => $hexId]);

            $this->db->commit();
            return true;

        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Helper to fetch sites (Crucial for converting Binary back to Hex)
     */
    public function getSitesByCompany(string $companyHexId): array
    {
        $sql = "SELECT HEX(id) as id, address, city, tax_id, HEX(company_id) as company_id 
                FROM company_site 
                WHERE company_id = UNHEX(:company_id)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['company_id' => $companyHexId]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => CompanySiteModel::fromArray($row), $rows);
    }

    /**
     * Deletes all sites associated with a specific company ID.
     * Expects a 32-character Hex string.
     */
    public function deleteSitesByCompany(string $companyHexId): bool
    {
        // Use UNHEX because company_id is BINARY(16) in your DB
        $stmt = $this->db->prepare("
        DELETE FROM company_site 
        WHERE company_id = UNHEX(:company_id)
    ");

        return $stmt->execute(['company_id' => $companyHexId]);
    }

    public function getById(string $siteId): CompanySiteModel
    {
        $sql = "SELECT HEX(id) as id, address, city, tax_id, HEX(company_id) as company_id
            FROM company_site 
            WHERE id = UNHEX(:id)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $siteId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $site = CompanySiteModel::fromArray($row);

        if (!$site) {
            throw new Exception("Site not found.");
        }

        return $site;
    }
}
