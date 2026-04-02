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
        $siteId = $data['company_site_id_internship_offer'] ?? $data['site_id'] ?? null;

        $sql = "INSERT INTO internship_offer (
                    title_internship_offer,
                    description_internship_offer,
                    hourly_rate_internship_offer,
                    start_date_internship_offer,
                    duration_weeks_internship_offer,
                    company_site_id_internship_offer,
                    is_active_internship_offer
                ) VALUES (
                    :title,
                    :description,
                    :hourly_rate,
                    :start_date,
                    :duration_weeks,
                    :site_id,
                    :is_active
                )";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':title' => $data['title_internship_offer'] ?? $data['title'] ?? '',
            ':description' => $data['description_internship_offer'] ?? $data['description'] ?? null,
            ':hourly_rate' => $data['hourly_rate_internship_offer'] ?? $data['hourly_rate'] ?? null,
            ':start_date' => $data['start_date_internship_offer'] ?? $data['start_date'] ?? null,
            ':duration_weeks' => $data['duration_weeks_internship_offer'] ?? $data['duration_weeks'] ?? null,
            ':site_id' => $siteId,
            ':is_active' => (int) ($data['is_active_internship_offer'] ?? $data['is_active'] ?? 1),
        ]);
    }

    /**
     * READ: Find by ID with full Company/Site info
     */
    public function findById(int|string $id): ?OfferModel
    {
        $sql = "SELECT
                    o.id_internship_offer,
                    o.id_internship_offer AS id,
                    o.title_internship_offer,
                    o.title_internship_offer AS title,
                    o.description_internship_offer,
                    o.description_internship_offer AS description,
                    o.hourly_rate_internship_offer,
                    o.hourly_rate_internship_offer AS hourly_rate,
                    o.start_date_internship_offer,
                    o.start_date_internship_offer AS start_date,
                    o.duration_weeks_internship_offer,
                    o.duration_weeks_internship_offer AS duration_weeks,
                    o.is_active_internship_offer,
                    o.is_active_internship_offer AS is_active,
                    o.published_at_internship_offer,
                    o.published_at_internship_offer AS published_at,
                    o.company_site_id_internship_offer,
                    o.company_site_id_internship_offer AS site_id,
                    s.id_company_site,
                    s.id_company_site AS company_site_id,
                    s.address_company_site,
                    s.address_company_site AS address,
                    s.city_company_site,
                    s.city_company_site AS location,
                    c.id_company,
                    c.id_company AS company_id,
                    c.name_company,
                    c.name_company AS company_name
                FROM internship_offer o
                JOIN company_site s
                    ON o.company_site_id_internship_offer = s.id_company_site
                JOIN company c
                    ON s.company_id_company_site = c.id_company
                WHERE o.id_internship_offer = :id
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => (int) $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? OfferModel::fromArray($row) : null;
    }

    /**
     * UPDATE: Update existing record
     */
    public function update(int|string $id, array $data): bool
    {
        $siteId = $data['company_site_id_internship_offer'] ?? $data['site_id'] ?? null;

        $sql = "UPDATE internship_offer SET
                    title_internship_offer = :title,
                    description_internship_offer = :description,
                    hourly_rate_internship_offer = :hourly_rate,
                    start_date_internship_offer = :start_date,
                    duration_weeks_internship_offer = :duration_weeks,
                    is_active_internship_offer = :is_active,
                    company_site_id_internship_offer = :site_id
                WHERE id_internship_offer = :id";

        return $this->pdo->prepare($sql)->execute([
            ':id' => (int) $id,
            ':title' => $data['title_internship_offer'] ?? $data['title'] ?? '',
            ':description' => $data['description_internship_offer'] ?? $data['description'] ?? null,
            ':hourly_rate' => $data['hourly_rate_internship_offer'] ?? $data['hourly_rate'] ?? null,
            ':start_date' => $data['start_date_internship_offer'] ?? $data['start_date'] ?? null,
            ':duration_weeks' => $data['duration_weeks_internship_offer'] ?? $data['duration_weeks'] ?? null,
            ':is_active' => (int) ($data['is_active_internship_offer'] ?? $data['is_active'] ?? 1),
            ':site_id' => $siteId,
        ]);
    }

    /**
     * DELETE: Soft delete
     */
    public function delete(int|string $id): bool
    {
        $sql = "UPDATE internship_offer
                SET is_active_internship_offer = 0
                WHERE id_internship_offer = :id";

        return $this->pdo->prepare($sql)->execute([':id' => (int) $id]);
    }

    /**
     * ADVANCED SEARCH: Paginated, Sorted, and Filtered
     */
    public function advancedSearch(array $filters, int $limit, int $offset): array
    {
        $sql = "SELECT SQL_CALC_FOUND_ROWS
                    o.id_internship_offer,
                    o.id_internship_offer AS id,
                    o.title_internship_offer,
                    o.title_internship_offer AS title,
                    o.description_internship_offer,
                    o.description_internship_offer AS description,
                    o.hourly_rate_internship_offer,
                    o.hourly_rate_internship_offer AS hourly_rate,
                    o.start_date_internship_offer,
                    o.start_date_internship_offer AS start_date,
                    o.duration_weeks_internship_offer,
                    o.duration_weeks_internship_offer AS duration_weeks,
                    o.published_at_internship_offer,
                    o.published_at_internship_offer AS published_at,
                    o.is_active_internship_offer,
                    o.is_active_internship_offer AS is_active,
                    o.company_site_id_internship_offer,
                    o.company_site_id_internship_offer AS site_id,
                    s.city_company_site,
                    s.city_company_site AS location,
                    s.address_company_site,
                    s.address_company_site AS address,
                    c.id_company,
                    c.id_company AS company_id,
                    c.name_company,
                    c.name_company AS company_name
                FROM internship_offer o
                JOIN company_site s
                    ON o.company_site_id_internship_offer = s.id_company_site
                JOIN company c
                    ON s.company_id_company_site = c.id_company
                WHERE o.is_active_internship_offer = 1";

        $params = [];

        $keyword = $filters['keyword'] ?? $filters['title_internship_offer'] ?? null;
        if (!empty($keyword)) {
            $sql .= " AND (
        o.title_internship_offer LIKE :keyword_title
        OR o.description_internship_offer LIKE :keyword_desc
        OR c.name_company LIKE :keyword_company
        )";
            $params['keyword_title'] = '%' . $keyword . '%';
            $params['keyword_desc'] = '%' . $keyword . '%';
            $params['keyword_company'] = '%' . $keyword . '%';
        }

        $city = $filters['city'] ?? $filters['city_company_site'] ?? null;
        if (!empty($city)) {
            $sql .= " AND s.city_company_site LIKE :city";
            $params['city'] = '%' . $city . '%';
        }

        $duration = $filters['duration'] ?? $filters['duration_weeks_internship_offer'] ?? null;
        if (!empty($duration)) {
            $sql .= " AND o.duration_weeks_internship_offer <= :duration";
            $params['duration'] = (int) $duration;
        }

        $sortMap = [
            'recent' => 'o.published_at_internship_offer DESC',
            'rate' => 'o.hourly_rate_internship_offer DESC',
            'duration' => 'o.duration_weeks_internship_offer ASC',
        ];
        $orderBy = $sortMap[$filters['sort'] ?? 'recent'] ?? 'o.published_at_internship_offer DESC';
        $sql .= " ORDER BY {$orderBy} LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $val) {
            $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $val, $type);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalCount = (int) $this->pdo->query("SELECT FOUND_ROWS()")->fetchColumn();

        return [
            'data' => array_map([OfferModel::class, 'fromArray'], $rows),
            'total' => $totalCount,
        ];
    }

    /**
     * Home page paginated view
     */
    public function findPaginated(int $limit, int $offset): array
    {
        $sql = "SELECT
                    o.id_internship_offer,
                    o.id_internship_offer AS id,
                    o.title_internship_offer,
                    o.title_internship_offer AS title,
                    o.description_internship_offer,
                    o.description_internship_offer AS description,
                    o.hourly_rate_internship_offer,
                    o.hourly_rate_internship_offer AS hourly_rate,
                    o.start_date_internship_offer,
                    o.start_date_internship_offer AS start_date,
                    o.duration_weeks_internship_offer,
                    o.duration_weeks_internship_offer AS duration_weeks,
                    o.published_at_internship_offer,
                    o.published_at_internship_offer AS published_at,
                    o.is_active_internship_offer,
                    o.is_active_internship_offer AS is_active,
                    o.company_site_id_internship_offer,
                    o.company_site_id_internship_offer AS site_id,
                    s.city_company_site,
                    s.city_company_site AS location,
                    s.address_company_site,
                    s.address_company_site AS address,
                    c.id_company,
                    c.id_company AS company_id,
                    c.name_company,
                    c.name_company AS company_name
                FROM internship_offer o
                JOIN company_site s
                    ON o.company_site_id_internship_offer = s.id_company_site
                JOIN company c
                    ON s.company_id_company_site = c.id_company
                WHERE o.is_active_internship_offer = 1
                ORDER BY o.published_at_internship_offer DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([OfferModel::class, 'fromArray'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function countAll(): int
    {
        return (int) $this->pdo
            ->query("SELECT COUNT(*) FROM internship_offer WHERE is_active_internship_offer = 1")
            ->fetchColumn();
    }

    public function getUniqueLocations(): array
    {
        $sql = "SELECT DISTINCT city_company_site AS location
                FROM company_site
                WHERE city_company_site IS NOT NULL
                  AND city_company_site <> ''
                ORDER BY city_company_site ASC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAllJobTypes(): array
    {
        // La nouvelle BDD ne contient pas de colonne job_type.
        // On conserve la méthode pour compatibilité avec l'ancien code.
        return [];
    }
}
