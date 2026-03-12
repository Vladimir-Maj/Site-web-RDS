<?php
// models/BaseModel.php
declare(strict_types=1);

class CampusModel extends BaseModel {
    public string $id;
    public string $name;
    public ?string $address;

    public static function fromArray(array $data): self {
        $inst = new self(null);
        $inst->id = $data['id'] ?? '';
        $inst->name = $data['name'] ?? '';
        $inst->address = $data['address'] ?? null;
        return $inst;
    }
}