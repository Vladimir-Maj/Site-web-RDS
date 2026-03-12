<?php
// .back/models/CompanyModel.php

declare(strict_types=1);

class CompanyModel
{
    public int $id;
    public string $name;
    public ?string $industry;
    public ?string $description;
    public ?string $website;
    public string $created_at;

    /**
     * Factory method to create an object from a PDO result
     */
    public static function fromArray(array $data): self
    {
        $company = new self();
        $company->id = (int)$data['id'];
        $company->name = $data['name'];
        $company->industry = $data['industry'] ?? null;
        $company->description = $data['description'] ?? null;
        $company->website = $data['website'] ?? null;
        $company->created_at = $data['created_at'];
        
        return $company;
    }
}