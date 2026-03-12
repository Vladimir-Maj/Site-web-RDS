<?php
// .back/repository/OfferRepository.php

require_once __DIR__ . '/../util/config.php';
require_once __DIR__ . '/../util/db_connect.php';
require_once __DIR__ . '/../models/OfferModel.php';

class OfferRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Crée une nouvelle offre en base de données
     */
    public function create(array $data): bool
    {
        $sql = "INSERT INTO offers (
                title, position, company_id, location, description, 
                state, salary_min, salary_max, salary_currency, 
                job_type, remote_type, experience_level, 
                education_level, required_skills, 
                expires_at, created_at
            ) VALUES (
                :title, :position, :company_id, :location, :description, 
                :state, :salary_min, :salary_max, :salary_currency, 
                :job_type, :remote_type, :experience_level, 
                :education_level, :required_skills, 
                :expires_at, NOW()
            )";

        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':title' => $data['title'],
                ':position' => $data['position'],
                ':company_id' => $data['company_id'],
                ':location' => $data['location'],
                ':description' => $data['description'],
                ':state' => $data['state'],
                ':salary_min' => $data['salary_min'],
                ':salary_max' => $data['salary_max'],
                ':salary_currency' => $data['salary_currency'],
                ':job_type' => $data['job_type'],
                ':remote_type' => $data['remote_type'],
                ':experience_level' => $data['experience_level'],
                ':education_level' => $data['education_level'],
                ':required_skills' => $data['required_skills'],
                ':expires_at' => $data['expires_at']
            ]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de l'offre : " . $e->getMessage());
            return false;
        }
    }

    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) FROM offers";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    public function findPaginated(int $limit, int $offset): array
    {
        $sql = "SELECT o.*, c.name AS company_name 
                FROM offers o 
                LEFT JOIN companies c ON o.company_id = c.id 
                ORDER BY o.created_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([Offer::class, 'fromArray'], $rows);
    }

    /**
     * Récupère la liste unique des types de job (job_type) pour les filtres
     * @return array
     */
    public function getAllJobTypes(): array
    {
        $sql = "SELECT DISTINCT job_type FROM offers WHERE job_type IS NOT NULL ORDER BY job_type";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Récupère la liste unique des types de télétravail (remote_type) pour les filtres
     * @return array
     */
    public function getAllRemoteTypes(): array
    {
        $sql = "SELECT DISTINCT remote_type FROM offers WHERE remote_type IS NOT NULL ORDER BY remote_type";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Recherche avancée retournant une collection d'objets Offer
     * @param array $filters Tableau associatif contenant les critères (keyword, location, etc.)
     * @return Offer[]
     */

/**
 * Recherche avancée retournant une collection d'objets Offer
 * @param array $filters Tableau associatif contenant les critères
 * @return Offer[]
 */
public function search(array $filters): array
{
    // Correction du bug HY093 : Autorise la réutilisation d'un même paramètre nommé (:kw)
    $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

    $sql = "SELECT o.*, c.name AS company_name 
            FROM offers o 
            LEFT JOIN companies c ON o.company_id = c.id 
            WHERE 1=1";
    $params = [];

    // 1. Keyword (Recherche fuzzy sur plusieurs champs)
    if (!empty($filters['keyword'])) {
        $sql .= " AND (o.title LIKE :kw 
                    OR o.position LIKE :kw 
                    OR o.description LIKE :kw 
                    OR c.name LIKE :kw)";
        $params[':kw'] = '%' . $filters['keyword'] . '%';
    }

    // 2. Location
    if (!empty($filters['location'])) {
        $sql .= " AND o.location = :loc";
        $params[':loc'] = $filters['location'];
    }

    // 3. Company ID
    if (!empty($filters['company_id'])) {
        $sql .= " AND o.company_id = :cid";
        $params[':cid'] = (int)$filters['company_id'];
    }

    // 4. Job Type
    if (!empty($filters['job_type'])) {
        $sql .= " AND o.job_type = :jt";
        $params[':jt'] = $filters['job_type'];
    }

    // 5. Remote Type (Ajouté car présent dans ton search.php)
    if (!empty($filters['remote_type'])) {
        $sql .= " AND o.remote_type = :rt";
        $params[':rt'] = $filters['remote_type'];
    }

    // 6. Tri sécurisé
    $sort = $filters['sort'] ?? 'recent';
    $orderMap = [
        'recent' => 'o.created_at DESC', 
        'salary' => 'o.salary_max DESC', 
        'views'  => 'o.views_count DESC'
    ];
    $orderBy = $orderMap[$sort] ?? 'o.created_at DESC';
    $sql .= " ORDER BY " . $orderBy;

    try {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Transformation des résultats en objets Offer
        // Note: Assure-toi que la classe Offer est bien accessible via Offer::fromArray
        return array_map(function($row) {
            return Offer::fromArray($row);
        }, $rows);

    } catch (PDOException $e) {
        error_log("Erreur lors de la recherche d'offres : " . $e->getMessage());
        return [];
    }
}

    public function findById(int $id): ?Offer
    {
        $sql = "SELECT o.*, c.name AS company_name, c.description AS company_bio, c.website AS company_website 
                FROM offers o 
                LEFT JOIN companies c ON o.company_id = c.id 
                WHERE o.id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Offer::fromArray($row) : null;
    }

    public function incrementViews(int $id): bool
    {
        $sql = "UPDATE offers SET views_count = views_count + 1 WHERE id = :id";
        return $this->pdo->prepare($sql)->execute([':id' => $id]);
    }

    // Utilitaires pour les filtres
    /**
     * Get all unique locations currently in the offers table
     * @return string[]
     */
    public function getUniqueLocations(): array
    {
        $sql = "SELECT DISTINCT location FROM offers WHERE location IS NOT NULL ORDER BY location ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Deletes an offer by its ID
     * Returns true on success, false otherwise
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM offers WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting offer {$id}: " . $e->getMessage());
            return false;
        }
    }
}