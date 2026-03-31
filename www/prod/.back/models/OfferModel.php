<?php
// .back/models/Offer.php
namespace App\Models;

class OfferModel
{
    public $id;
    public $title;
    public $description;
    public $location;
    public $company_name;
    public $company_id;
    
    // --- ADD THESE TO FIX THE TWIG ERRORS ---
    public $hourly_rate;    // Matches your DB column and Twig variable
    public $start_date;     // For the editor form
    public $duration_weeks; // For the editor form
    public $is_active;      // For the visibility toggle
    public $site_id;        // To handle the relationship in the form
    // ----------------------------------------

    public $required_skills;
    public $published_at;
    public $views_count;

    // ... Keep your existing JOB_TYPES and REMOTE_TYPES constants ...

    /**
     * Transforme un tableau associatif SQL en objet Offer
     */
    public static function fromArray(array $data): self
    {
        $offer = new self();
        foreach ($data as $key => $value) {
            // This is why it was failing: property_exists was returning false 
            // because hourly_rate wasn't defined in this class!
            if (property_exists($offer, $key)) {
                $offer->$key = $value;
            }
        }
        return $offer;
    }

    /**
     * Helper to avoid "null" crashes in the creation form
     */
    public function __construct() {
        // Initialize defaults if needed
        $this->is_active = 1;
        $this->hourly_rate = 0.0;
    }

    public function getSkillsArray(): array
    {
        if (empty($this->required_skills)) return [];
        return array_map('trim', explode(',', $this->required_skills));
    }
}