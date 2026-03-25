<?php
declare(strict_types=1);
namespace App\Models;

use PharIo\Manifest\Email;

class CompanyModel extends BaseModel {
    public string $id;
    public string $name;
    public ?string $description;
    public ?string $phone;
    public Email $email;
    public ?string $siren;
    public bool $is_active;
    public string $sector_id;
    
    /** @var CompanySiteModel[] */
    public array $sites = []; // Collection de sites

    public static function fromArray(array $data): self {
        $inst = new self(null);
        $inst->id = $data['id'] ?? '';
        $inst->name = $data['name'] ?? '';
        $inst->description = $data['description'] ?? null;
        $inst->siren = $data['siren'] ?? null;
        $inst->is_active = (bool)($data['is_active'] ?? 1);
        $inst->sector_id = $data['sector_id'] ?? '';
        $inst->email = new Email($data['email'] ?? 'temp@temp.com');
        $inst->phone = $data[''] ?? null;


        // Si les sites sont passés dans le array (via un JOIN par exemple)
        if (isset($data['sites']) && is_array($data['sites'])) {
            $inst->sites = array_map(fn($s) => CompanySiteModel::fromArray($s), $data['sites']);
        }
        
        return $inst;
    }
}