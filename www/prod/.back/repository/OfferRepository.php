<?php
// prod/.back/repository/OfferRepository.php

class OfferRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) FROM offers";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Fetches a specific slice of offers with company names
     * Useful for the homepage and pagination
     */
    public function findPaginated(int $limit, int $offset): array
    {
        $sql = "SELECT 
                o.*, 
                c.name AS company_name 
            FROM offers o 
            LEFT JOIN companies c ON o.company_id = c.id 
            ORDER BY o.created_at DESC 
            LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);

        // We bind parameters as Integers to avoid SQL syntax errors with LIMIT/OFFSET
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Utility for Search Filters: Get unique locations
     */
    public function getAllLocations(): array
    {
        return $this->pdo->query("SELECT DISTINCT location FROM offers WHERE location IS NOT NULL ORDER BY location")
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Utility for Search Filters: Get unique job types
     */
    public function getAllJobTypes(): array
    {
        return $this->pdo->query("SELECT DISTINCT job_type FROM offers WHERE job_type IS NOT NULL ORDER BY job_type")
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Advanced Search Method
     */
    public function search(array $filters): array
    {
        // Start with a base query that always evaluates to true
        $sql = "SELECT o.*, c.name AS company_name 
            FROM offers o 
            LEFT JOIN companies c ON o.company_id = c.id 
            WHERE 1=1";

        $params = [];

        // 1. Keyword Filter
        if (!empty($filters['keyword'])) {
            // We use unique placeholders to avoid issues with some PDO drivers
            $sql .= " AND (o.title LIKE :kw1 OR o.position LIKE :kw2 OR o.description LIKE :kw3 OR c.name LIKE :kw4)";
            $searchTerm = '%' . $filters['keyword'] . '%';
            $params[':kw1'] = $searchTerm;
            $params[':kw2'] = $searchTerm;
            $params[':kw3'] = $searchTerm;
            $params[':kw4'] = $searchTerm;
        }

        // 2. Location Filter
        if (!empty($filters['location'])) {
            $sql .= " AND o.location = :loc";
            $params[':loc'] = $filters['location'];
        }

        // 3. Job Type Filter
        if (!empty($filters['job_type'])) {
            $sql .= " AND o.job_type = :jt";
            $params[':jt'] = $filters['job_type'];
        }

        // 4. Remote Type Filter
        if (!empty($filters['remote_type'])) {
            $sql .= " AND o.remote_type = :rt";
            $params[':rt'] = $filters['remote_type'];
        }

        // 5. Active Only Filter
        if (!empty($filters['only_active'])) {
            $sql .= " AND o.state = 'open'";
        }

        // 6. Sorting
        $sort = $filters['sort'] ?? 'recent';
        $orderMap = [
            'recent' => 'o.created_at DESC',
            'salary' => 'o.salary_max DESC',
            'views' => 'o.views_count DESC'
        ];
        $orderBy = $orderMap[$sort] ?? 'o.created_at DESC';

        // Dans OfferRepository.php -> méthode search()

        // 7. Filtrage par Entreprise
        if (!empty($filters['company_id'])) {
            $sql .= " AND o.company_id = :cid";
            $params[':cid'] = (int) $filters['company_id'];
        }

        $sql .= " ORDER BY $orderBy";

        // Execution
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Finds a single offer with full company details
     * Joined with companies table to get bio, name, and logo
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT 
                o.*, 
                c.name AS company_name, 
                c.description AS company_bio, -- On mappe 'description' vers 'company_bio'
                c.website AS company_website   -- On récupère le site web tant qu'à faire
            FROM offers o 
            LEFT JOIN companies c ON o.company_id = c.id 
            WHERE o.id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            // En cas d'erreur SQL, on logue pour le débug
            error_log("Erreur findById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Increments the view counter for an offer
     * Used for "Most Popular" sorting and analytics
     */
    public function incrementViews(int $id): bool
    {
        $sql = "UPDATE offers 
                SET views_count = views_count + 1 
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}