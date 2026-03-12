<?php
// .back/repository/CompanyRepository.php
declare(strict_types=1);

class CompanyRepository {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function findAllActive(): array {
        $stmt = $this->pdo->query("SELECT HEX(id) as id, name, description, tax_id, is_active, HEX(sector_id) as sector_id FROM company WHERE is_active = 1");
        return array_map(fn($row) => CompanyModel::fromArray($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findSitesByCompany(string $companyId): array {
        $stmt = $this->pdo->prepare("SELECT HEX(id) as id, address, city, HEX(company_id) as company_id FROM company_site WHERE company_id = UNHEX(?)");
        $stmt->execute([$companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Returns sites for the company
    }
}