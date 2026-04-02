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
}
