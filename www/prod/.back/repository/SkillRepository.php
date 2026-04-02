<?php
declare(strict_types=1);

namespace App\Repository;

use App\Models\SkillModel;
use PDO;

class SkillRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function getById(int|string $id): ?SkillModel
    {
        $sql = "SELECT 
                    id_skill,
                    id_skill AS id,
                    label_skill,
                    label_skill AS label
                FROM skill
                WHERE id_skill = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => (int) $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? SkillModel::fromArray($row) : null;
    }

    public function deleteById(int|string $id): bool
    {
        $sql = "DELETE FROM skill WHERE id_skill = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => (int) $id]);
    }

    /**
     * @return SkillModel[]
     */
    public function findAll(): array
    {
        $sql = "SELECT 
                    id_skill,
                    id_skill AS id,
                    label_skill,
                    label_skill AS label
                FROM skill
                ORDER BY label_skill ASC";

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => SkillModel::fromArray($row), $rows);
    }

    public function pushSkill(SkillModel $skill): bool
    {
        $id = $skill->id_skill ?? $skill->id ?? null;
        $label = $skill->label_skill ?? $skill->label ?? '';

        if (empty($id)) {
            $sql = "INSERT INTO skill (label_skill) VALUES (:label)";
            $params = ['label' => $label];
        } else {
            $sql = "UPDATE skill SET label_skill = :label WHERE id_skill = :id";
            $params = [
                'label' => $label,
                'id'    => (int) $id,
            ];
        }

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute($params);

        if ($success && empty($id)) {
            $newId = (int) $this->db->lastInsertId();
            $skill->id = $newId;
            $skill->id_skill = $newId;
        }

        return $success;
    }

    /**
     * Fetch skills already linked to a specific offer.
     *
     * @return SkillModel[]
     */
    public function getSkillsByOffer(int|string $offerId): array
    {
        $sql = "SELECT 
                    s.id_skill,
                    s.id_skill AS id,
                    s.label_skill,
                    s.label_skill AS label
                FROM skill s
                JOIN offer_requirement os 
                    ON s.id_skill = os.skill_requirement_id
                WHERE os.offer_requirement_id = :offer_id
                ORDER BY s.label_skill ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['offer_id' => (int) $offerId]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => SkillModel::fromArray($row), $rows);
    }
}
