<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use App\Models\OfferModel;

class OfferRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    /**
     * CREATE: Insert a new offer
     */
    public function create(array $data): bool
    {
        // Note: We use UNHEX(:site_id) because $data['site_id'] is a Hex string from the form
        $sql = "INSERT INTO internship_offer (id, title, description, hourly_rate, start_date, duration_weeks, site_id, is_active) 
            VALUES (UUID_TO_BIN(UUID()), :title, :desc, :rate, :start, :duration, UNHEX(:site_id), :active)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':title' => $data['title'],
            ':desc' => $data['description'] ?? null,
            ':rate' => $data['hourly_rate'] ?? null,
            ':start' => $data['start_date'] ?? null,
            ':duration' => $data['duration_weeks'] ?? null,
            ':site_id' => $data['site_id'], // Expects hex string
            ':active' => $data['is_active'] ?? 1
        ]);
    }

    /**
     * READ: Find by Hex ID with full Company/Site info
     */
    public function findById(string $hexId): ?OfferModel
    {
        $sql = "SELECT HEX(o.id) as id, o.*, 
                       c.name as company_name, HEX(c.id) as company_id,
                       s.city as location, s.address, HEX(s.id) as site_id
                FROM internship_offer o
                JOIN company_site s ON o.site_id = s.id
                JOIN company c ON s.company_id = c.id
                WHERE o.id = UNHEX(:id) LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $hexId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? OfferModel::fromArray($row) : null;
    }

    /**
     * UPDATE: Update existing record
     */
    public function update(string $hexId, array $data): bool
    {
        $sql = "UPDATE internship_offer SET 
            title = :title, 
            description = :desc, 
            hourly_rate = :rate, 
            start_date = :start,
            duration_weeks = :duration,
            is_active = :active,
            site_id = UNHEX(:site_id) 
            WHERE id = UNHEX(:id)";

        return $this->pdo->prepare($sql)->execute([
            ':id' => $hexId,
            ':title' => $data['title'],
            ':desc' => $data['description'],
            ':rate' => $data['hourly_rate'],
            ':start' => $data['start_date'] ?? null,
            ':duration' => $data['duration_weeks'] ?? null,
            ':active' => (int) $data['is_active'],
            ':site_id' => $data['site_id'] // Added this to match DB needs
        ]);
    }
    
    /**
     * DELETE: Soft delete (is_active = 0)
     */
    public function delete(string $hexId): bool
    {
        $sql = "UPDATE internship_offer SET is_active = 0 WHERE id = UNHEX(:id)";
        return $this->pdo->prepare($sql)->execute([':id' => $hexId]);
    }

    /**
     * ADVANCED SEARCH: Paginated, Sorted, and Filtered
     */
    public function advancedSearch(array $filters, int $limit, int $offset): array
    {
        $sql = "SELECT SQL_CALC_FOUND_ROWS 
                HEX(o.id) as id, o.title, o.description, o.hourly_rate, 
                o.start_date, o.duration_weeks, o.published_at, o.is_active,
                s.city as location, c.name as company_name, HEX(c.id) as company_id
            FROM internship_offer o
            JOIN company_site s ON o.site_id = s.id
            JOIN company c ON s.company_id = c.id
            WHERE 1=1";

        $params = [];

        if (!empty($filters['keyword'])) {
            $sql .= " AND (o.title LIKE :keyword OR o.description LIKE :keyword OR c.name LIKE :keyword)";
            $params['keyword'] = '%' . $filters['keyword'] . '%';
        }

        if (!empty($filters['city'])) {
            $sql .= " AND s.city LIKE :city";
            $params['city'] = '%' . $filters['city'] . '%';
        }

        if (!empty($filters['duration'])) {
            $sql .= " AND o.duration_weeks <= :duration";
            $params['duration'] = $filters['duration'];
        }

        $sortMap = [
            'recent' => 'o.published_at DESC',
            'rate' => 'o.hourly_rate DESC',
            'duration' => 'o.duration_weeks ASC'
        ];
        $orderBy = $sortMap[$filters['sort'] ?? 'recent'] ?? 'o.published_at DESC';
        $sql .= " ORDER BY $orderBy LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalCount = (int) $this->pdo->query("SELECT FOUND_ROWS()")->fetchColumn();

        return [
            'data' => array_map([OfferModel::class, 'fromArray'], $rows),
            'total' => $totalCount
        ];
    }

    /**
     * Home page paginated view
     */
    public function findPaginated(int $limit, int $offset): array
    {
        $sql = "SELECT HEX(o.id) as id, o.*, c.name as company_name, HEX(c.id) as company_id, s.city as location 
                FROM internship_offer o
                JOIN company_site s ON o.site_id = s.id
                JOIN company c ON s.company_id = c.id
                WHERE o.is_active = 1
                ORDER BY o.published_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([OfferModel::class, 'fromArray'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function countAll(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM internship_offer WHERE is_active = 1")->fetchColumn();
    }
}