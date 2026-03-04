<?php
// prod/.back/repository/CompanyRepository.php

class CompanyRepository {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Fetches all companies for dropdown menus
     * Returns an array of associative arrays with 'id' and 'name'
     */
    public function getAllCompanies(): array {
        $sql = "SELECT id, name FROM companies ORDER BY name ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches a single company by its ID
     */
    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM companies WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Useful for the Search page: 
     * Get companies that actually have active offers
     */
    public function getCompaniesWithActiveOffers(): array {
        $sql = "SELECT DISTINCT c.id, c.name 
                FROM companies c
                INNER JOIN offers o ON c.id = o.company_id
                WHERE o.state = 'open'
                ORDER BY c.name ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}