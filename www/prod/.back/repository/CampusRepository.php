<?php
// .back/repository/CampusRepository.php
declare(strict_types=1);
namespace App\Repository;

// 1. Import PDO from the global namespace
use PDO; 
// 2. Import your Model from the Models namespace
use App\Models\CampusModel;
use App\Models\PromotionModel;

class CampusRepository {
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getAllCampuses(): array {
        $stmt = $this->pdo->query("SELECT HEX(id) as id, name, address FROM campus");
        return array_map(fn($row) => CampusModel::fromArray($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getPromotionsByCampus(string $campusId): array {
        $stmt = $this->pdo->prepare("SELECT HEX(id) as id, label, academic_year, HEX(campus_id) as campus_id 
                                     FROM promotion WHERE campus_id = UNHEX(?)");
        $stmt->execute([$campusId]);
        return array_map(fn($row) => PromotionModel::fromArray($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}