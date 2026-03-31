<?php
declare(strict_types=1);

namespace App\Models;

class CompanySiteModel extends BaseModel
{
    /**
     * @var string|null $id 
     * Initialized as null for new records to allow DB triggers to fire.
     * Note: This will hold a Hex string in PHP, but maps to binary(16) in DB.
     */
    public ?string $id = null;

    // Initialize with an empty string
    public string $address = '';

    public ?string $city = null;
    /** @var string|null $tax_id The SIRET of the specific site */
    public ?string $tax_id = null;
    /**
     * @var string $company_id 
     * The Hex representation of the parent company binary(16) ID.
     */
    public string $company_id = '';
    /**
     * Factory method to create an instance from an associative array (e.g., $_POST or DB row)
     */
    public static function fromArray(array $data): self
    {
        // Assuming BaseModel constructor handles basic initialization
        $inst = new self(null);

        // 1. Handle the ID: If 'new' or empty, keep it null so the DB Trigger triggers.
        $idValue = $data['id'] ?? null;
        $inst->id = ($idValue === 'new' || empty($idValue)) ? null : (string) $idValue;

        // 2. Mandatory fields (matching NOT NULL in schema)
        $inst->address = (string) ($data['address'] ?? '');
        $inst->company_id = (string) ($data['company_id'] ?? '');

        // 3. Optional fields (matching YES NULL in schema)
        $inst->city = !empty($data['city']) ? (string) $data['city'] : null;
        $inst->tax_id = !empty($data['tax_id']) ? (string) $data['tax_id'] : null;

        return $inst;
    }
}