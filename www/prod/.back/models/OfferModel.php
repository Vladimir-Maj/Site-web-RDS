<?php
// .back/models/Offer.php
namespace App\Models;

class OfferModel
{
    public $id;
    public $company_id;
    public $company_name;
    public $title;
    public $position;
    public $location;
    public $description;
    public $state;
    public $salary_min;
    public $salary_max;
    public $salary_currency; // Added
    public $created_at;       // Added
    public $published_at;     // Added
    public $job_type;
    public $remote_type;      // Added
    public $required_skills;  // FIXED: Was missing, causing your error
    public $application_deadline; // Added
    public $experience_level; // Added
    public $education_level;  // Added
    public $views_count;
    public $company_bio;

    // Strict list of Job Types
    public const JOB_TYPES = [
        'full-time',
        'part-time',
        'contract',
        'internship',
        'apprenticeship'
    ];

    // Strict list of Remote Types
    public const REMOTE_TYPES = [
        'office',
        'hybrid',
        'remote'
    ];

    /**
     * Transforme un tableau associatif SQL en objet Offer
     */
    public static function fromArray(array $data): self
    {
        $offer = new self();
        foreach ($data as $key => $value) {
            if (property_exists($offer, $key)) {
                $offer->$key = $value;
            }
        }
        return $offer;
    }

    /**
     * Exemple de logique métier dans le Model
     */
    public function isHighSalary(): bool
    {
        return $this->salary_max > 50000;
    }

    public function getDisplayTitle(): string
    {
        return $this->position ?? $this->title ?? 'Sans titre';
    }

    public function getSkillsArray(): array
    {
        // Now $this->required_skills is defined!
        if (empty($this->required_skills))
            return [];
        return array_map('trim', explode(',', $this->required_skills));
    }

    public function formatSalary(): string
    {
        if (!$this->salary_min && !$this->salary_max)
            return "N/C";
        $currency = $this->salary_currency ?? 'EUR';
        return "{$this->salary_min} - {$this->salary_max} {$currency}";
    }
}