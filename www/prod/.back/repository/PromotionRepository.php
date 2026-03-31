<?php
declare(strict_types=1);

namespace App\Repository;

use App\Models\PromotionModel;
use PDO;

class PromotionRepository
{
    public function __construct(private PDO $db) {}

    public function getById(string $id): ?PromotionModel {
        $sql = "SELECT HEX(id) as id, label, academic_year, HEX(campus_id) as campus_id 
                FROM promotion WHERE id = UNHEX(:id)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? PromotionModel::fromArray($row) : null;
    }

    public function findAll(): array {
        $sql = "SELECT HEX(id) as id, label, academic_year, HEX(campus_id) as campus_id 
                FROM promotion ORDER BY academic_year DESC, label ASC";
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => PromotionModel::fromArray($row), $rows);
    }

    /**
     * Get all promotions belonging to a specific campus
     */
    public function getByCampus(string $campusId): array {
        $sql = "SELECT HEX(id) as id, label, academic_year, HEX(campus_id) as campus_id 
                FROM promotion WHERE campus_id = UNHEX(:campus_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['campus_id' => $campusId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => PromotionModel::fromArray($row), $rows);
    }

    public function save(PromotionModel $promotion): bool {
        if (empty($promotion->id)) {
            $sql = "INSERT INTO promotion (label, academic_year, campus_id) 
                    VALUES (:label, :academic_year, UNHEX(:campus_id))";
            $params = [
                'label' => $promotion->label,
                'academic_year' => $promotion->academic_year,
                'campus_id' => $promotion->campus_id
            ];
        } else {
            $sql = "UPDATE promotion SET label = :label, academic_year = :academic_year, 
                    campus_id = UNHEX(:campus_id) WHERE id = UNHEX(:id)";
            $params = [
                'label' => $promotion->label,
                'academic_year' => $promotion->academic_year,
                'campus_id' => $promotion->campus_id,
                'id' => $promotion->id
            ];
        }
        return $this->db->prepare($sql)->execute($params);
    }

    public function deleteById(string $id): void {
        $sql = "DELETE FROM promotion WHERE id = UNHEX(:id)";
        $this->db->prepare($sql)->execute(['id' => $id]);
    }
}