<?php
// .back/repository/OfferRepository.php
declare(strict_types=1);

namespace App\Repository;

// 1. Import PDO from the global namespace
use PDO; 
use PDOException;
// 2. Import your Model from the Models namespace
use App\Models\OfferModel;
class OfferRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Replaces the old 'offers' count
     */
    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) FROM internship_offer WHERE is_active = 1";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Updated Paginated Find: Handles the Join with Company and Site
     */
    public function findPaginated(int $limit, int $offset): array
    {
        $sql = "SELECT 
                    HEX(o.id) as id, o.title, o.description, o.hourly_rate, 
                    o.start_date, o.duration_weeks, o.published_at,
                    c.name AS company_name, 
                    s.city AS location
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

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Note: Using OfferModel::fromArray to stay consistent with your new models
        return array_map([OfferModel::class, 'fromArray'], $rows);
    }

    /**
     * Advanced Search: Refactored for the new table structure
     */
    public function search(array $filters): array
    {
        // Keep the emulation fix for fuzzy keyword matching
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        $sql = "SELECT 
                    HEX(o.id) as id, HEX(c.id) as company_id, o.title, o.description, o.hourly_rate, 
                    o.start_date, o.duration_weeks,
                    c.name AS company_name, 
                    s.city AS location
                FROM internship_offer o
                JOIN company_site s ON o.site_id = s.id
                JOIN company c ON s.company_id = c.id
                WHERE o.is_active = 1";
        
        $params = [];

        // 1. Keyword search (Title, Description, Company Name)
        if (!empty($filters['keyword'])) {
            $sql .= " AND (o.title LIKE :kw OR o.description LIKE :kw OR c.name LIKE :kw)";
            $params[':kw'] = '%' . $filters['keyword'] . '%';
        }

        // 2. Location (Now searching the company_site.city field)
        if (!empty($filters['location'])) {
            $sql .= " AND s.city = :loc";
            $params[':loc'] = $filters['location'];
        }

        // 3. Company
        if (!empty($filters['company_id'])) {
            $sql .= " AND c.id = UNHEX(:company_id)";
            $params[':company_id'] = $filters['company_id'];
        }

        // 4. Duration (New filter based on your duration_weeks column)
        if (!empty($filters['duration'])) {
            $sql .= " AND o.duration_weeks <= :dur";
            $params[':dur'] = (int)$filters['duration'];
        }

        $sort = $filters['sort'] ?? 'recent';
        if ($sort === 'salary') {
            $sql .= " ORDER BY o.hourly_rate DESC, o.published_at DESC";
        } else {
            $sql .= " ORDER BY o.published_at DESC";
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_map([OfferModel::class, 'fromArray'], $rows);
        } catch (PDOException $e) {
            error_log("Search Error: " . $e->getMessage());
            return [];
        }
    }

    public function getUniqueLocations(): array
    {
        $sql = "SELECT DISTINCT s.city
                FROM internship_offer o
                JOIN company_site s ON o.site_id = s.id
                WHERE o.is_active = 1 AND s.city IS NOT NULL AND s.city <> ''
                ORDER BY s.city ASC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function getCompaniesWithActiveOffers(): array
    {
        $sql = "SELECT DISTINCT HEX(c.id) AS id, c.name
                FROM internship_offer o
                JOIN company_site s ON o.site_id = s.id
                JOIN company c ON s.company_id = c.id
                WHERE o.is_active = 1
                ORDER BY c.name ASC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(string $hexId): ?OfferModel
    {
        $sql = "SELECT 
                    HEX(o.id) as id, o.title, o.description, o.hourly_rate, 
                    o.start_date, o.duration_weeks,
                    c.name AS company_name, c.description AS company_bio,
                    s.address, s.city
                FROM internship_offer o
                JOIN company_site s ON o.site_id = s.id
                JOIN company c ON s.company_id = c.id
                WHERE o.id = UNHEX(:id)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $hexId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? OfferModel::fromArray($row) : null;
    }
}