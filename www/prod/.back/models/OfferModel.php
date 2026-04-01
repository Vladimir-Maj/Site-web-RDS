<?php

namespace App\Models;

class OfferModel
{
    // --- Nouvelle BDD ---
    public ?int $id_internship_offer = null;
    public ?string $title_internship_offer = null;
    public ?string $description_internship_offer = null;
    public ?float $hourly_rate_internship_offer = 0.0;
    public ?string $start_date_internship_offer = null;
    public ?int $duration_weeks_internship_offer = null;
    public int $is_active_internship_offer = 1;
    public ?string $published_at_internship_offer = null;
    public ?int $company_site_id_internship_offer = null;

    // --- Relations / jointures nouvelle BDD ---
    public ?int $company_id_company_site = null;
    public ?string $name_company = null;
    public ?string $city_company_site = null;
    public ?string $address_company_site = null;
    public ?string $required_skills = null;

    // --- Alias compatibilité ancien code / Twig ---
    public ?int $id = null;
    public ?string $title = null;
    public ?string $description = null;
    public ?float $hourly_rate = 0.0;
    public ?string $start_date = null;
    public ?int $duration_weeks = null;
    public int $is_active = 1;
    public ?string $published_at = null;
    public ?int $site_id = null;
    public ?int $company_id = null;
    public ?string $company_name = null;
    public ?string $location = null;
    public ?string $address = null;

    public static function fromArray(array $data): self
    {
        $offer = new self();

        $id = isset($data['id_internship_offer']) && $data['id_internship_offer'] !== ''
            ? (int) $data['id_internship_offer']
            : (isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null);

        $title = $data['title_internship_offer'] ?? ($data['title'] ?? null);
        $description = $data['description_internship_offer'] ?? ($data['description'] ?? null);
        $hourlyRate = isset($data['hourly_rate_internship_offer'])
            ? (float) $data['hourly_rate_internship_offer']
            : (isset($data['hourly_rate']) && $data['hourly_rate'] !== '' ? (float) $data['hourly_rate'] : 0.0);
        $startDate = $data['start_date_internship_offer'] ?? ($data['start_date'] ?? null);
        $durationWeeks = isset($data['duration_weeks_internship_offer'])
            ? (int) $data['duration_weeks_internship_offer']
            : (isset($data['duration_weeks']) && $data['duration_weeks'] !== '' ? (int) $data['duration_weeks'] : null);
        $isActive = isset($data['is_active_internship_offer'])
            ? (int) $data['is_active_internship_offer']
            : (isset($data['is_active']) ? (int) $data['is_active'] : 1);
        $publishedAt = $data['published_at_internship_offer'] ?? ($data['published_at'] ?? null);
        $siteId = isset($data['company_site_id_internship_offer']) && $data['company_site_id_internship_offer'] !== ''
            ? (int) $data['company_site_id_internship_offer']
            : (isset($data['site_id']) && $data['site_id'] !== '' ? (int) $data['site_id'] : null);

        $companyId = isset($data['company_id_company_site']) && $data['company_id_company_site'] !== ''
            ? (int) $data['company_id_company_site']
            : (isset($data['company_id']) && $data['company_id'] !== '' ? (int) $data['company_id'] : null);

        $companyName = $data['name_company'] ?? ($data['company_name'] ?? null);
        $location = $data['city_company_site'] ?? ($data['location'] ?? null);
        $address = $data['address_company_site'] ?? ($data['address'] ?? null);

        // Nouvelle BDD
        $offer->id_internship_offer = $id;
        $offer->title_internship_offer = $title;
        $offer->description_internship_offer = $description;
        $offer->hourly_rate_internship_offer = $hourlyRate;
        $offer->start_date_internship_offer = $startDate;
        $offer->duration_weeks_internship_offer = $durationWeeks;
        $offer->is_active_internship_offer = $isActive;
        $offer->published_at_internship_offer = $publishedAt;
        $offer->company_site_id_internship_offer = $siteId;
        $offer->company_id_company_site = $companyId;
        $offer->name_company = $companyName;
        $offer->city_company_site = $location;
        $offer->address_company_site = $address;
        $offer->required_skills = $data['required_skills'] ?? null;

        // Alias compatibilité Twig / ancien code
        $offer->id = $id;
        $offer->title = $title;
        $offer->description = $description;
        $offer->hourly_rate = $hourlyRate;
        $offer->start_date = $startDate;
        $offer->duration_weeks = $durationWeeks;
        $offer->is_active = $isActive;
        $offer->published_at = $publishedAt;
        $offer->site_id = $siteId;
        $offer->company_id = $companyId;
        $offer->company_name = $companyName;
        $offer->location = $location;
        $offer->address = $address;

        return $offer;
    }
}
