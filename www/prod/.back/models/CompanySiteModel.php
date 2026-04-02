<?php
declare(strict_types=1);

namespace App\Models;

class CompanySiteModel extends BaseModel
{
    /**
     * Identifiant du site
     */
    public ?int $id_company_site = null;

    public string $address_company_site = '';
    public ?string $city_company_site = null;

    /**
     * SIRET éventuel du site si tu veux le conserver côté code
     * mais il n’existe pas dans ta nouvelle BDD actuelle.
     */
    public ?string $tax_id = null;

    /**
     * FK vers company.id_company
     */
    public int $company_id_company_site = 0;

    /**
     * Factory method to create an instance from an associative array
     */
    public static function fromArray(array $data): self
    {
        $inst = new self(null);

        $idValue = $data['id_company_site'] ?? ($data['id'] ?? null);
        $inst->id_company_site = ($idValue === 'new' || $idValue === '' || $idValue === null)
            ? null
            : (int) $idValue;

        $inst->address_company_site = (string) ($data['address_company_site'] ?? ($data['address'] ?? ''));
        $inst->company_id_company_site = (int) ($data['company_id_company_site'] ?? ($data['company_id'] ?? 0));

        $inst->city_company_site = !empty($data['city_company_site'])
            ? (string) $data['city_company_site']
            : (!empty($data['city']) ? (string) $data['city'] : null);

        $inst->tax_id = !empty($data['tax_id']) ? (string) $data['tax_id'] : null;

        return $inst;
    }
}
