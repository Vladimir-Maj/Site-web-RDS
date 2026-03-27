<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use App\Models\OfferModel;

class OfferRepository
{
    // Constructor Promotion (PHP 8.0+)
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    /**
     * CREATE: Insert a new offer
     */
    public function create(array $data): bool
    {
        $sql = "INSERT INTO internship_offer (id, title, description, hourly_rate, start_date, duration_weeks, site_id) 
                VALUES (UNHEX(REPLACE(UUID(), '-', '')), :title, :desc, :rate, :start, :duration, UNHEX(:site_id))";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':title' => $data['title'],
            ':desc' => $data['description'] ?? null,
            ':rate' => $data['hourly_rate'] ?? null,
            ':start' => $data['start_date'] ?? null,
            ':duration' => $data['duration_weeks'] ?? null,
            ':site_id' => $data['site_id']
        ]);
    }

    /**
     * READ: Find by Hex ID
     */
    public function findById(string $hexId): ?OfferModel
    {
        $sql = "SELECT HEX(o.id) as id, o.*, c.name as company_name, s.city as location, s.address
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
                title = :title, description = :desc, hourly_rate = :rate, is_active = :active 
                WHERE id = UNHEX(:id)";

        return $this->pdo->prepare($sql)->execute([
            ':id' => $hexId,
            ':title' => $data['title'],
            ':desc' => $data['description'],
            ':rate' => $data['hourly_rate'],
            ':active' => (int) $data['is_active']
        ]);
    }

    /**
     * DELETE (or Soft Delete)
     */
    public function delete(string $hexId, bool $softDelete = true): bool
    {
        if ($softDelete) {
            $sql = "UPDATE internship_offer SET is_active = 0 WHERE id = UNHEX(:id)";
        } else {
            $sql = "DELETE FROM internship_offer WHERE id = UNHEX(:id)";
        }
        return $this->pdo->prepare($sql)->execute([':id' => $hexId]);
    }

    /**
     * SEARCH: With dynamic filters
     */
    public function search(array $filters = []): array
    {
        $sql = "SELECT HEX(o.id) as id, o.*, c.name as company_name, s.city as location 
                FROM internship_offer o
                JOIN company_site s ON o.site_id = s.id
                JOIN company c ON s.company_id = c.id
                WHERE o.is_active = 1";

        $params = [];

        if (!empty($filters['keyword'])) {
            $sql .= " AND (o.title LIKE :kw OR o.description LIKE :kw OR c.name LIKE :kw)";
            $params[':kw'] = "%{$filters['keyword']}%";
        }

        if (!empty($filters['city'])) {
            $sql .= " AND s.city = :city";
            $params[':city'] = $filters['city'];
        }

        if (!empty($filters['min_rate'])) {
            $sql .= " AND o.hourly_rate >= :min_rate";
            $params[':min_rate'] = $filters['min_rate'];
        }

        $sql .= " ORDER BY o.published_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map([OfferModel::class, 'fromArray'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * ADVANCED SEARCH: Paginated, Sorted, and Filtered
     * * @param array $filters  ['keyword', 'city', 'min_rate', 'max_rate', 'start_after', 'company_id']
     * @param string $sortBy  'date', 'rate', 'title'
     * @param int $page       Current page number
     * @param int $limit      Records per page
     * @return array          ['data' => OfferModel[], 'total' => int]
     */
    /**
     * Advanced filtering with pagination and total count.
     * Matches the signature: advancedSearch($filters, $limit, $offset)
     */
    public function advancedSearch(array $filters, int $limit, int $offset): array
    {
        // 1. Base Query - Using HEX() for the binary IDs
        $sql = "SELECT SQL_CALC_FOUND_ROWS 
                HEX(o.id) as id, 
                o.title, 
                o.description, 
                o.hourly_rate, 
                o.start_date, 
                o.duration_weeks, 
                o.published_at,
                o.is_active,
                s.city as location, 
                c.name as company_name,
                o.required_skills
            FROM internship_offer o
            JOIN company_site s ON o.site_id = s.id
            JOIN company c ON s.company_id = c.id
            WHERE 1=1"; // "WHERE 1=1" makes appending AND clauses easier

        $params = [];

        // 2. Dynamic Filters
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

        // 3. Sorting Logic
        $sortMap = [
            'recent' => 'o.published_at DESC',
            'rate' => 'o.hourly_rate DESC',
            'duration' => 'o.duration_weeks ASC'
        ];
        $orderBy = $sortMap[$filters['sort']] ?? 'o.published_at DESC';
        $sql .= " ORDER BY $orderBy";

        // 4. Pagination
        $sql .= " LIMIT :limit OFFSET :offset";

        // 5. Execution
        $stmt = $this->pdo->prepare($sql);

        // Bind parameters manually to ensure correct types for LIMIT/OFFSET
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 6. Get Total Count for Pagination
        $totalCount = (int) $this->pdo->query("SELECT FOUND_ROWS()")->fetchColumn();

        return [
            'data' => $data,
            'total' => $totalCount
        ];
    }

    /**
     * READ: Find paginated records for the home/catalogue view.
     */
    public function findPaginated(int $limit, int $offset): array
    {
        $sql = "SELECT HEX(o.id) as id, o.*, c.name as company_name, s.city as location 
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

        // Map results to OfferModel objects
        return array_map([OfferModel::class, 'fromArray'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * COUNT: Total number of active offers.
     */
    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) FROM internship_offer WHERE is_active = 1";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }
}