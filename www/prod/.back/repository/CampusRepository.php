<?php
declare(strict_types=1);

namespace App\Repository;

use App\Models\CampusModel;
use PDO;

class CampusRepository
{
    public function __construct(private PDO $db) {}

    public function getById(int|string $id): ?CampusModel
    {
        $sql = "SELECT 
                    id_campus,
                    name_campus,
                    address_campus
                FROM campus
                WHERE id_campus = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => (int) $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? CampusModel::fromArray($row) : null;
    }

    public function findAll(): array
    {
        $sql = "SELECT 
                    id_campus,
                    name_campus,
                    address_campus
                FROM campus
                ORDER BY name_campus ASC";

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => CampusModel::fromArray($row), $rows);
    }

    public function save(CampusModel $campus): bool
    {
        $id = $campus->id_campus ?? null;
        $name = $campus->name_campus ?? '';
        $address = $campus->address_campus ?? null;

        if (empty($id)) {
            $sql = "INSERT INTO campus (name_campus, address_campus)
                    VALUES (:name, :address)";
            $params = [
                'name' => $name,
                'address' => $address
            ];
        } else {
            $sql = "UPDATE campus
                    SET name_campus = :name,
                        address_campus = :address
                    WHERE id_campus = :id";
            $params = [
                'name' => $name,
                'address' => $address,
                'id' => (int) $id
            ];
        }

        return $this->db->prepare($sql)->execute($params);
    }

    public function deleteById(int|string $id): void
    {
        $sql = "DELETE FROM campus WHERE id_campus = :id";
        $this->db->prepare($sql)->execute(['id' => (int) $id]);
    }
}
