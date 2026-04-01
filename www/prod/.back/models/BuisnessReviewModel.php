<?php
namespace App\Models;

use DateTime;

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

    public static function fromArray(array $data): self
    {
        $instance = new self();
        $instance->company_id_business_review = (int) ($data['company_id_business_review'] ?? ($data['business_id'] ?? 0));
        $instance->pilot_id_business_review = (int) ($data['pilot_id_business_review'] ?? ($data['reviewer_id'] ?? 0));
        $instance->compound_id = $data['compound_id'] ?? '';
        $instance->rating_business_review = (int) ($data['rating_business_review'] ?? ($data['rating'] ?? 0));
        $instance->comment_business_review = $data['comment_business_review'] ?? ($data['comment'] ?? '');
        $instance->reviewed_at_business_review = isset($data['reviewed_at_business_review'])
            ? new DateTime($data['reviewed_at_business_review'])
            : (isset($data['review_date']) ? new DateTime($data['review_date']) : new DateTime());

        return $instance;
    }
}
