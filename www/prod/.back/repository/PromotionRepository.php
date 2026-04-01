<?php
declare(strict_types=1);

namespace App\Repository;

use App\Models\PromotionModel;
use PDO;

class PromotionRepository
{
    public function __construct(private PDO $db) {}

    public function getById(int|string $id): ?PromotionModel
    {
        $sql = "SELECT
                    id_promotion,
                    id_promotion AS id,
                    label_promotion,
                    label_promotion AS label,
                    academic_year_promotion,
                    academic_year_promotion AS academic_year,
                    campus_id_promotion,
                    campus_id_promotion AS campus_id
                FROM promotion
                WHERE id_promotion = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => (int) $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? PromotionModel::fromArray($row) : null;
    }

    public function findAll(): array
    {
        $sql = "SELECT
                    id_promotion,
                    id_promotion AS id,
                    label_promotion,
                    label_promotion AS label,
                    academic_year_promotion,
                    academic_year_promotion AS academic_year,
                    campus_id_promotion,
                    campus_id_promotion AS campus_id
                FROM promotion
                ORDER BY academic_year_promotion DESC, label_promotion ASC";

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => PromotionModel::fromArray($row), $rows);
    }

    /**
     * Get all promotions belonging to a specific campus
     */
    public function getByCampus(int|string $campusId): array
    {
        $sql = "SELECT
                    id_promotion,
                    id_promotion AS id,
                    label_promotion,
                    label_promotion AS label,
                    academic_year_promotion,
                    academic_year_promotion AS academic_year,
                    campus_id_promotion,
                    campus_id_promotion AS campus_id
                FROM promotion
                WHERE campus_id_promotion = :campus_id
                ORDER BY academic_year_promotion DESC, label_promotion ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['campus_id' => (int) $campusId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => PromotionModel::fromArray($row), $rows);
    }

    public function save(PromotionModel $promotion): bool
    {
        $id = $promotion->id_promotion ?? null;
        $label = $promotion->label_promotion ?? '';
        $academicYear = $promotion->academic_year_promotion ?? null;
        $campusId = $promotion->campus_id_promotion ?? 0;

        if (empty($id)) {
            $sql = "INSERT INTO promotion (
                        label_promotion,
                        academic_year_promotion,
                        campus_id_promotion
                    ) VALUES (
                        :label,
                        :academic_year,
                        :campus_id
                    )";

            $params = [
                'label' => $label,
                'academic_year' => $academicYear,
                'campus_id' => $campusId,
            ];
        } else {
            $sql = "UPDATE promotion
                    SET
                        label_promotion = :label,
                        academic_year_promotion = :academic_year,
                        campus_id_promotion = :campus_id
                    WHERE id_promotion = :id";

            $params = [
                'label' => $label,
                'academic_year' => $academicYear,
                'campus_id' => $campusId,
                'id' => (int) $id,
            ];
        }

        return $this->db->prepare($sql)->execute($params);
    }

    public function deleteById(int|string $id): void
    {
        $sql = "DELETE FROM promotion WHERE id_promotion = :id";
        $this->db->prepare($sql)->execute(['id' => (int) $id]);
    }
}
