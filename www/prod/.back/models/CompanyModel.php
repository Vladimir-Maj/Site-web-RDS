<?php
declare(strict_types=1);

// .back/models/CompanyModel.php
class CompanyModel extends BaseModel {
    public string $id;
    public string $name;
    public ?string $description;
    public ?string $tax_id; // SIRET
    public bool $is_active;
    public string $sector_id;

    public static function fromArray(array $data): self {
        $inst = new self(null);
        $inst->id = $data['id'] ?? '';
        $inst->name = $data['name'] ?? '';
        $inst->description = $data['description'] ?? null;
        $inst->tax_id = $data['tax_id'] ?? null;
        $inst->is_active = (bool)($data['is_active'] ?? 1);
        $inst->sector_id = $data['sector_id'] ?? '';
        return $inst;
    }
}