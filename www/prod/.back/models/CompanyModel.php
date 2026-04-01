<?php
declare(strict_types=1);

namespace App\Models;

use PharIo\Manifest\Email;
use JsonSerializable;

class CompanyModel extends BaseModel implements JsonSerializable
{
    /** @var CompanySiteModel[] */
    public array $sites = [];

    public function __construct(
        public ?int $id_company = null,
        public string $name_company = '',
        public ?string $description_company = null,
        public ?string $phone_company = null,
        public ?Email $email_company = null,
        public ?string $tax_id_company = null,
        public bool $is_active_company = true,
        public ?int $sector_id_company = null,
        public ?string $created_at_company = null
    ) {
        parent::__construct(null);
        $this->email_company ??= new Email('temp@temp.com');
    }

    public static function fromArray(array $data): self
    {
        $inst = new self(
            id_company: isset($data['id_company']) && $data['id_company'] !== ''
                ? (int) $data['id_company']
                : (isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null),
            name_company: $data['name_company'] ?? ($data['name'] ?? ''),
            description_company: $data['description_company'] ?? ($data['description'] ?? null),
            phone_company: $data['phone_company'] ?? ($data['phone'] ?? null),
            email_company: new Email($data['email_company'] ?? ($data['email'] ?? 'temp@temp.com')),
            tax_id_company: $data['tax_id_company'] ?? ($data['siren'] ?? null),
            is_active_company: isset($data['is_active_company'])
                ? (bool) $data['is_active_company']
                : (bool) ($data['is_active'] ?? (($data['status'] ?? null) === 'active' || ($data['status'] ?? null) === true)),
            sector_id_company: isset($data['sector_id_company']) && $data['sector_id_company'] !== ''
                ? (int) $data['sector_id_company']
                : (isset($data['sector_id']) && $data['sector_id'] !== '' ? (int) $data['sector_id'] : null),
            created_at_company: $data['created_at_company'] ?? ($data['created_at'] ?? null)
        );

        if (!empty($data['sites']) && is_array($data['sites'])) {
            $inst->sites = array_map(fn($s) => CompanySiteModel::fromArray($s), $data['sites']);
        }

        return $inst;
    }

    public function jsonSerialize(): array
    {
        return [
            'id_company' => $this->id_company,
            'name_company' => $this->name_company,
            'description_company' => $this->description_company,
            'email_company' => $this->email_company?->asString(),
            'phone_company' => $this->phone_company,
            'tax_id_company' => $this->tax_id_company,
            'is_active_company' => $this->is_active_company,
            'sector_id_company' => $this->sector_id_company,
            'created_at_company' => $this->created_at_company,
            'sites' => $this->sites,
        ];
    }
}
