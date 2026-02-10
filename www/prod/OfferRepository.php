<?php

class OfferRepository {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function findAll() {
        $sql = "SELECT offers.*, companies.name AS company_name 
                FROM offers 
                JOIN companies ON offers.company_id = companies.id 
                ORDER BY created_at DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function search($filters) {
        $sql = "SELECT offers.*, companies.name AS company_name 
                FROM offers 
                JOIN companies ON offers.company_id = companies.id 
                WHERE 1=1";
        $params = [];

        if (!empty($filters['keyword'])) {
            $sql .= " AND (offers.title LIKE :kw OR offers.description LIKE :kw)";
            $params['kw'] = '%' . $filters['keyword'] . '%';
        }

        // Add sorting logic here...
        $sortMap = [
            'city'    => 'offers.location ASC',
            'company' => 'companies.name ASC',
            'recent'  => 'offers.created_at DESC'
        ];
        $order = $sortMap[$filters['sort']] ?? 'offers.created_at DESC';
        $sql .= " ORDER BY $order";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM offers WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }

    /**
     * Fetch all companies for the dropdown
     */
    public function getAllCompanies() {
        return $this->pdo->query("SELECT id, name FROM companies ORDER BY name ASC")->fetchAll();
    }

    /**
     * Create a new offer
     */
    public function create(array $data) {
        $sql = "INSERT INTO offers (title, company_id, location, description, state)
                VALUES (:title, :company_id, :location, :description, :state)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':title'       => $data['title'],
            ':company_id'  => $data['company_id'],
            ':location'    => $data['location'],
            ':description' => $data['description'],
            ':state'       => $data['state'] ?? 'draft'
        ]);
    }
}