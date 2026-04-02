<?php

namespace App\Models;

use DateTime;
use Exception;

class BuisnessReviewModel
{
    public int $company_id_business_review;
    public int $pilot_id_business_review;
    public string $compound_id;
    public int $rating_business_review;
    public string $comment_business_review;
    public DateTime $reviewed_at_business_review;

    public function __construct()
    {
        $this->company_id_business_review = 0;
        $this->pilot_id_business_review = 0;
        $this->compound_id = '';
        $this->rating_business_review = 0;
        $this->comment_business_review = '';
        $this->reviewed_at_business_review = new DateTime();
    }

    /**
     * Maps an array to the Model. 
     * Supports both the new {attribute}_{table} schema and the legacy format.
     */
    public static function fromArray(array $data): self
    {
        $instance = new self();

        // Map Company/Business ID
        $instance->company_id_business_review = (int) ($data['company_id_business_review'] ?? ($data['business_id'] ?? 0));

        // Map Pilot/Reviewer ID
        $instance->pilot_id_business_review = (int) ($data['pilot_id_business_review'] ?? ($data['reviewer_id'] ?? 0));

        // Map Compound ID (Shared)
        $instance->compound_id = $data['compound_id'] ?? '';

        // Map Rating
        $instance->rating_business_review = (int) ($data['rating_business_review'] ?? ($data['rating'] ?? 0));

        // Map Comment
        $instance->comment_business_review = $data['comment_business_review'] ?? ($data['comment'] ?? '');

        // Map Review Date
        if (isset($data['reviewed_at_business_review'])) {
            $instance->reviewed_at_business_review = new DateTime($data['reviewed_at_business_review']);
        } elseif (isset($data['review_date'])) {
            $instance->reviewed_at_business_review = new DateTime($data['review_date']);
        } else {
            $instance->reviewed_at_business_review = new DateTime();
        }

        return $instance;
    }
}
