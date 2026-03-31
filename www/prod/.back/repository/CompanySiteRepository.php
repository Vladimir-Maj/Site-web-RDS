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
        $stmt = $this->db->prepare("DELETE FROM company_site WHERE id = UNHEX(:id)");
        return $stmt->execute(['id' => $hexId]);
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