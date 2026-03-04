<?php

class OfferRepository {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Dans OfferRepository.php
    public function countAll() {
        return $this->pdo->query("SELECT COUNT(*) FROM offers")->fetchColumn();
    }

    public function findPaginated($limit, $offset) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM offers ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Search with advanced filters matching the new schema
     */
    public function search(array $filters) {
        $sql = "SELECT offers.*, companies.name AS company_name 
                FROM offers 
                LEFT JOIN companies ON offers.company_id = companies.id 
                WHERE 1=1";
        $params = [];

        if (!empty($filters['keyword'])) {
            $sql .= " AND (offers.title LIKE :kw OR offers.position LIKE :kw OR offers.description LIKE :kw OR companies.name LIKE :kw)";
            $params[':kw'] = '%' . $filters['keyword'] . '%';
        }

        if (!empty($filters['location'])) {
            $sql .= " AND offers.location LIKE :loc";
            $params[':loc'] = '%' . $filters['location'] . '%';
        }

        if (!empty($filters['job_type'])) {
            $sql .= " AND offers.job_type = :jt";
            $params[':jt'] = $filters['job_type'];
        }

        if (!empty($filters['remote_type'])) {
            $sql .= " AND offers.remote_type = :rt";
            $params[':rt'] = $filters['remote_type'];
        }

        if (!empty($filters['min_salary'])) {
            $sql .= " AND offers.salary_min >= :min_sal";
            $params[':min_sal'] = (int)$filters['min_salary'];
        }

        // Only show 'open' and non-expired offers if requested
        if (!empty($filters['only_active'])) {
            $sql .= " AND offers.state = 'open' AND (offers.expires_at IS NULL OR offers.expires_at > NOW())";
        }

        $sortMap = [
            'recent' => 'offers.created_at DESC',
            'salary' => 'offers.salary_max DESC',
            'views'  => 'offers.views_count DESC'
        ];
        $order = $sortMap[$filters['sort'] ?? 'recent'] ?? 'offers.created_at DESC';

        $sql .= " ORDER BY $order";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new offer matching the 27-column structure
     */
    public function create(array $data) {
        $sql = "INSERT INTO offers (
                    company_id, company_name, title, position, location, 
                    description, state, salary_min, salary_max, salary_currency,
                    published_at, job_type, remote_type, required_skills, 
                    application_deadline, contact_email, application_url, 
                    benefits, experience_level, education_level, expires_at
                ) VALUES (
                    :company_id, :company_name, :title, :position, :location, 
                    :description, :state, :salary_min, :salary_max, :salary_currency,
                    :published_at, :job_type, :remote_type, :required_skills, 
                    :application_deadline, :contact_email, :application_url, 
                    :benefits, :experience_level, :education_level, :expires_at
                )";

        $stmt = $this->pdo->prepare($sql);

        // Auto-set published_at if state is open
        $published_at = ($data['state'] === 'open') ? date('Y-m-d H:i:s') : null;

        return $stmt->execute([
            ':company_id'           => $data['company_id'],
            ':company_name'         => $data['company_name'] ?? null,
            ':title'                => $data['title'],
            ':position'             => $data['position'] ?? $data['title'],
            ':location'             => $data['location'],
            ':description'          => $data['description'] ?? null,
            ':state'                => $data['state'] ?? 'draft',
            ':salary_min'           => !empty($data['salary_min']) ? (int)$data['salary_min'] : null,
            ':salary_max'           => !empty($data['salary_max']) ? (int)$data['salary_max'] : null,
            ':salary_currency'      => $data['salary_currency'] ?? 'EUR',
            ':published_at'         => $published_at,
            ':job_type'             => $data['job_type'] ?? 'full-time',
            ':remote_type'          => $data['remote_type'] ?? 'on-site',
            ':required_skills'      => $data['required_skills'] ?? null,
            ':application_deadline' => !empty($data['application_deadline']) ? $data['application_deadline'] : null,
            ':contact_email'        => $data['contact_email'] ?? null,
            ':application_url'      => $data['application_url'] ?? null,
            ':benefits'             => $data['benefits'] ?? null,
            ':experience_level'     => $data['experience_level'] ?? 'mid',
            ':education_level'      => $data['education_level'] ?? 'none',
            ':expires_at'           => !empty($data['expires_at']) ? $data['expires_at'] : null
        ]);
    }

    public function getAllCompanies() {
        return $this->pdo->query("SELECT id, name FROM companies ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllJobTypes() {
        return $this->pdo->query("SELECT DISTINCT job_type FROM offers ORDER BY job_type")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllLocations() {
        return $this->pdo->query("SELECT DISTINCT location FROM offers ORDER BY location")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllExperienceLevels() {
        return ['entry', 'mid', 'senior', 'lead'];
    }

    public function findById($id) {
        $sql = "SELECT offers.*, 
                   companies.name AS company_name, 
                   companies.description AS company_bio,
                   companies.website AS company_url
            FROM offers 
            LEFT JOIN companies ON offers.company_id = companies.id 
            WHERE offers.id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches all offers with company names for the main dashboard
     */
    public function findAll() {
        $sql = "SELECT offers.*, companies.name AS company_name 
                FROM offers 
                LEFT JOIN companies ON offers.company_id = companies.id 
                ORDER BY offers.created_at DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Increments the view counter for a specific offer
     */
    public function incrementViews($id) {
        $sql = "UPDATE offers SET views_count = views_count + 1 WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => (int)$id]);
    }
}