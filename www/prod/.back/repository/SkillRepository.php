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

    public function getById(string $id): SkillModel|null {
     $sql = "SELECT HEX(id) as id, label FROM skill WHERE id = UNHEX(:id)";
     $stmt = $this->db->prepare($sql);
     $stmt->execute(['id' => $id]);
     $row = $stmt->fetch(PDO::FETCH_ASSOC);
     return $row ? SkillModel::fromArray($row) : null;
    }

    public function deleteById(string $id): void {
     $sql = "DELETE FROM skill WHERE id = UNHEX(:id)";
$stmt = $this->db->prepare($sql);
$stmt->execute(['id' => $id]);
}


    /**
     * Retrieves all skills. 
     * Uses HEX(id) so the Model receives the 32-char string.
     * @return SkillModel[]
     */
    public function findAll(): array
    {
        $sql = "SELECT HEX(id) as id, label FROM skill ORDER BY label ASC";
        
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => SkillModel::fromArray($row), $rows);
    }

    /**
     * Implementation for saving or updating.
     * On INSERT, we only send the label; the Trigger handles the Binary ID.
     */
    public function pushSkill(SkillModel $skill): bool
    {
        if (empty($skill->id)) {
            // INSERT: Trigger handles the id (binary(16))
            $sql = "INSERT INTO skill (label) VALUES (:label)";
            $params = ['label' => $skill->label];
        } else {
            // UPDATE: Find by Hex string converted to Binary
            $sql = "UPDATE skill SET label = :label WHERE id = UNHEX(:id)";
            $params = [
                'label' => $skill->label,
                'id'    => $skill->id
            ];
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Fetch skills already linked to a specific offer.
     * Used for the "Edit Offer" view to pre-check checkboxes.
     */
    public function getSkillsByOffer(string $offerHexId): array
    {
        $sql = "SELECT HEX(s.id) as id, s.label 
                FROM skill s
                JOIN offer_skill os ON s.id = os.skill_id
                WHERE os.offer_id = UNHEX(:offer_id)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['offer_id' => $offerHexId]);
        
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => SkillModel::fromArray($row), $rows);
    }
}