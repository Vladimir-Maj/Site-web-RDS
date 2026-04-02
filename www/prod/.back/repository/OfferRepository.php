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

        $return = $stmt->execute([
            ':title' => $data['title_internship_offer'] ?? $data['title'] ?? '',
            ':description' => $data['description_internship_offer'] ?? $data['description'] ?? null,
            ':hourly_rate' => $data['hourly_rate_internship_offer'] ?? $data['hourly_rate'] ?? null,
            ':start_date' => $data['start_date_internship_offer'] ?? $data['start_date'] ?? null,
            ':duration_weeks' => $data['duration_weeks_internship_offer'] ?? $data['duration_weeks'] ?? null,
            ':site_id' => $siteId,
            ':is_active' => (int) ($data['is_active_internship_offer'] ?? $data['is_active'] ?? 1),
        ]);

        if ($return) {
            $offerId = (int) $this->pdo->lastInsertId();
            $this->syncSkills($offerId, $data['required_skills'] ?? null);
        } else {
            error_log("Failed to create offer: " . implode(", ", $stmt->errorInfo()));
        }
        return $return;
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

        $return = $this->pdo->prepare($sql)->execute([
            ':id' => (int) $id,
            ':title' => $data['title_internship_offer'] ?? $data['title'] ?? '',
            ':description' => $data['description_internship_offer'] ?? $data['description'] ?? null,
            ':hourly_rate' => $data['hourly_rate_internship_offer'] ?? $data['hourly_rate'] ?? null,
            ':start_date' => $data['start_date_internship_offer'] ?? $data['start_date'] ?? null,
            ':duration_weeks' => $data['duration_weeks_internship_offer'] ?? $data['duration_weeks'] ?? null,
            ':is_active' => (int) ($data['is_active_internship_offer'] ?? $data['is_active'] ?? 1),
            ':site_id' => $siteId,
        ]);

        if ($return) {
            $this->syncSkills((int) $id, $data['required_skills'] ?? null);
        } else {
            error_log("Failed to update offer: " . implode(", ", $this->pdo->errorInfo()));
        }

        return $return;
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
        $sql = "
        SELECT SQL_CALC_FOUND_ROWS
            o.id_internship_offer AS id,
            o.title_internship_offer AS title,
            o.description_internship_offer AS description,
            o.hourly_rate_internship_offer AS hourly_rate,
            o.start_date_internship_offer AS start_date,
            o.is_active_internship_offer AS is_active,
            o.views_internship_offer AS views,
            o.duration_weeks_internship_offer AS duration_weeks,
            o.published_at_internship_offer AS published_at,
            o.is_active_internship_offer AS is_active,
            s.city_company_site AS location,
            c.name_company AS company_name,
            GROUP_CONCAT(sk.label_skill SEPARATOR ', ') AS required_skills
        FROM internship_offer o
        JOIN company_site s ON o.company_site_id_internship_offer = s.id_company_site
        JOIN company c ON s.company_id_company_site = c.id_company
        LEFT JOIN offer_requirement orq ON orq.offer_requirement_id = o.id_internship_offer
        LEFT JOIN skill sk ON sk.id_skill = orq.skill_requirement_id
        WHERE o.is_active_internship_offer = 1
    ";

        $params = [];



        // Keyword filter
        if (!empty($filters['keyword'])) {
            $sql .= " AND (
        o.title_internship_offer LIKE :keyword_title
        OR o.description_internship_offer LIKE :keyword_desc
        OR c.name_company LIKE :keyword_company
        OR sk.label_skill LIKE :keyword_skill
    )";
            $keywordParam = '%' . $filters['keyword'] . '%';
            $params['keyword_title'] = $keywordParam;
            $params['keyword_desc'] = $keywordParam;
            $params['keyword_company'] = $keywordParam;
            $params['keyword_skill'] = $keywordParam;
        }

        // City filter
        if (!empty($filters['city'])) {
            $sql .= " AND s.city_company_site LIKE :city";
            $params['city'] = '%' . $filters['city'] . '%';
        }

        // Duration filter
        if (!empty($filters['duration'])) {
            $sql .= " AND o.duration_weeks_internship_offer <= :duration";
            $params['duration'] = (int) $filters['duration'];
        }

        // Sorting
        $sortMap = [
            'recent' => 'o.published_at_internship_offer DESC',
            'rate' => 'o.hourly_rate_internship_offer DESC',
            'duration' => 'o.duration_weeks_internship_offer ASC',
        ];
        $orderBy = $sortMap[$filters['sort'] ?? 'recent'] ?? 'o.published_at_internship_offer DESC';

        $sql .= " GROUP BY o.id_internship_offer ORDER BY {$orderBy} LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);

        // Bind dynamic filter parameters only if they exist
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        // Always bind limit/offset
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
                    o.is_active_internship_offer AS is_active,
                    o.views_internship_offer AS views,
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

    private function syncSkills(int $offerId, ?string $skillsString): void
    {
        // Clear existing relations
        $this->pdo->prepare(
            "DELETE FROM offer_requirement WHERE offer_requirement_id = :id"
        )->execute([':id' => $offerId]);

        if (!$skillsString) {
            return;
        }

        $skills = array_filter(array_map('trim', explode(',', $skillsString)));

        if (empty($skills)) {
            return;
        }

        // Fetch matching skill IDs
        $placeholders = implode(',', array_fill(0, count($skills), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT id_skill, label_skill FROM skill WHERE label_skill IN ($placeholders)"
        ); //343 here!
        $stmt->execute($skills);

        $existingSkills = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($existingSkills as $skill) {
            $this->pdo->prepare(
                "INSERT INTO offer_requirement (offer_requirement_id, skill_requirement_id)
             VALUES (:offer_id, :skill_id)"
            )->execute([
                        ':offer_id' => $offerId,
                        ':skill_id' => $skill['id_skill']
                    ]);
        }
    }

    public function getSkillsAsString(int $offerId): string
    {
        $stmt = $this->pdo->prepare("
        SELECT s.label_skill
        FROM skill s
        JOIN offer_requirement o 
            ON s.id_skill = o.skill_requirement_id -- Check if this is skill_id or skill_requirement_id in your DB
        WHERE o.offer_requirement_id = :id -- Check if this is offer_id or offer_requirement_id
    ");

        $stmt->execute([':id' => $offerId]);
        $skills = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return implode(', ', $skills);
    }

    public function incrementViews(int|string $offerId): void
    {

        error_log("Incrementing views for offer ID: " . $offerId);
        $offerId = (int) $offerId;
        $stmt = $this->pdo->prepare(
            'UPDATE internship_offer
         SET views_internship_offer = views_internship_offer + 1
         WHERE id_internship_offer = :id'
        );
        $stmt->execute(['id' => $offerId]);
    }

    public function countViews(int $offerId): string
    {
        $stmt = $this->pdo->prepare(
            'SELECT views_internship_offer
         FROM internship_offer
         WHERE id_internship_offer = :id'
        );
        $stmt->execute(['id' => $offerId]);

        // Fetch the result once
        $views = $stmt->fetchColumn();

        // Handle case where no row is found (fetchColumn returns false)
        if ($views === false) {
            return "0";
        }

        error_log("Counting views for offer ID: " . $offerId . "\nViews: " . $views);

        return (string) $views;
    }
}
