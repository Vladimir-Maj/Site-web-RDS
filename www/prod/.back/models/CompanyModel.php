<?php
declare(strict_types=1);

namespace App\Models;

use PharIo\Manifest\Email;
use JsonSerializable;

class CompanyModel extends BaseModel implements JsonSerializable
{
    /** @var CompanySiteModel[] */
    public array $sites = [];

    // Using PHP 8 constructor promotion for core fields
    public function __construct(
        public string $id = '',
        public string $name = '',
        public ?string $description = null,
        public ?string $phone = null,
        public ?Email $email = null,
        public ?string $siren = null,
        public bool $is_active = true,
        public string $sector_id = ''
    ) {
        $this->email ??= new Email('temp@temp.com');
    }

    public static function fromArray(array $data): self
    {
        $inst = new self(
            id:          $data['id'] ?? '',
            name:        $data['name'] ?? '',
            description: $data['description'] ?? null,
            phone:       $data['phone'] ?? null,
            email:       new Email($data['email'] ?? 'temp@temp.com'),
            siren:       $data['siren'] ?? null,
            is_active:   (bool)($data['is_active'] ?? ($data['status'] === 'active' || $data['status'] === true)),
            sector_id:   $data['sector_id'] ?? ''
        );

        if (!empty($data['sites']) && is_array($data['sites'])) {
            $inst->sites = array_map(fn($s) => CompanySiteModel::fromArray($s), $data['sites']);
        }
        
        return $inst;
    }

    /**
     * This allows json_encode() to work automatically
     */
    public function jsonSerialize(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'email'       => $this->email->asString(),
            'siren'       => $this->siren,
            'is_active'   => $this->is_active,
            'sites'       => $this->sites
        ];
    }
}