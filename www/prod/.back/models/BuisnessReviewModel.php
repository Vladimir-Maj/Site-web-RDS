<?php
namespace App\Models;

use DateTime;
class BuisnessReviewModel 
{
    public string $business_id;
    public string $reviewer_id;
    public string $compound_id;
    public string $rating;
    public string $comment;
    public DateTime $review_date;

    public function __construct()
    {
        // Initialize properties with default values if needed
        $this->business_id = '';
        $this->reviewer_id = '';
        $this->compound_id = '';
        $this->rating = '';
        $this->comment = '';
        $this->review_date = new DateTime();
    }

    public static function fromArray(array $data): self
    {
        $instance = new self();
        $instance->business_id = $data['business_id'] ?? '';
        $instance->reviewer_id = $data['reviewer_id'] ?? '';
        $instance->compound_id = $data['compound_id'] ?? '';
        $instance->rating = $data['rating'] ?? '';
        $instance->comment = $data['comment'] ?? '';
        $instance->review_date = isset($data['review_date']) ? new DateTime($data['review_date']) : new DateTime();
        return $instance;
    }
}