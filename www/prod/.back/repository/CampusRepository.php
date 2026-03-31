<?php
declare(strict_types=1);

namespace App\Repository;

use App\Models\CampusModel;
use PDO;

class CampusRepository
{
    public function __construct(private PDO $db) {}

    public function getById(string $id): ?CampusModel {
        $sql = "SELECT HEX(id) as id, name, address FROM campus WHERE id = UNHEX(:id)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? CampusModel::fromArray($row) : null;
    }

    public function findAll(): array {
        $sql = "SELECT HEX(id) as id, name, address FROM campus ORDER BY name ASC";
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => CampusModel::fromArray($row), $rows);
    }

    public function save(CampusModel $campus): bool {
        if (empty($campus->id)) {
            $sql = "INSERT INTO campus (name, address) VALUES (:name, :address)";
            $params = ['name' => $campus->name, 'address' => $campus->address];
        } else {
            $sql = "UPDATE campus SET name = :name, address = :address WHERE id = UNHEX(:id)";
            $params = [
                'name'    => $campus->name,
                'address' => $campus->address,
                'id'      => $campus->id
            ];
        }
        return $this->db->prepare($sql)->execute($params);
    }

    public function deleteById(string $id): void {
        $sql = "DELETE FROM campus WHERE id = UNHEX(:id)";
        $this->db->prepare($sql)->execute(['id' => $id]);
    }
}
