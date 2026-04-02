<?php
declare(strict_types=1);

namespace App\Models;

class CampusModel extends BaseModel
{
    public ?int $id_campus = null;
    public string $name_campus = '';
    public ?string $address_campus = null;

    public static function fromArray(array $data): self
    {
        $inst = new self(null);
        $inst->id_campus = isset($data['id_campus']) && $data['id_campus'] !== ''
            ? (int) $data['id_campus']
            : (isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null);

        $inst->name_campus = $data['name_campus'] ?? ($data['name'] ?? '');
        $inst->address_campus = $data['address_campus'] ?? ($data['address'] ?? null);

        return $inst;
    }

    
}
