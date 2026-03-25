<?php
declare(strict_types=1);
namespace App\Models;

class CompanySiteModel extends BaseModel {
    public string $id;
    public string $address;
    public ?string $city;
    public ?string $tax_id; // Le SIRET du site spécifique
    public string $company_id;

    public static function fromArray(array $data): self {
        $inst = new self(null);
        $inst->id = $data['id'] ?? '';
        $inst->address = $data['address'] ?? '';
        $inst->city = $data['city'] ?? null;
        $inst->tax_id = $data['tax_id'] ?? null;
        $inst->company_id = $data['company_id'] ?? '';
        return $inst;
    }
}