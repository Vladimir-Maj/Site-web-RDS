<?php

namespace App\Models;

class OfferModel
{
    public ?string $id = null;
    public ?string $title = null;
    public ?string $description = null;
    public ?float $hourly_rate = 0.0;
    public ?string $start_date = null;
    public ?int $duration_weeks = null;
    public int $is_active = 1;
    public ?string $published_at = null;
    
    // Relationship properties (Foreign Keys & Joins)
    public ?string $site_id = null;
    public ?string $company_id = null;
    public ?string $company_name = null;
    public ?string $location = null; // maps to s.city
    public ?string $address = null;

    public static function fromArray(array $data): self
    {
        $offer = new self();
        foreach ($data as $key => $value) {
            // Use property_exists or just assignment if keys match
            if (property_exists($offer, $key)) {
                $offer->$key = $value;
            }
        }
        return $offer;
    }
}