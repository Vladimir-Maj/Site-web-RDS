<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use App\Models\BusinessReviewModel;
use DateTime;

class ReviewRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new business review.
     * Note: Since pilot_id and company_id are a composite primary key, 
     * this will fail if the same pilot reviews the same company twice.
     */
    public function push(BusinessReviewModel $review): bool
    {
        $sql = "INSERT INTO business_review (pilot_id, company_id, rating, comment, reviewed_at) 
                VALUES (UNHEX(:pilot_id), UNHEX(:company_id), :rating, :comment, :reviewed_at)";

        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            ':pilot_id'   => $review->reviewer_id, // Assuming hex string
            ':company_id' => $review->business_id,  // Assuming hex string
            ':rating'     => $review->rating,
            ':comment'    => $review->comment,
            ':reviewed_at'=> $review->review_date->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Finds all reviews for a specific company.
     */
    public function findByBusinessId(string $businessHexId): array
    {
        $sql = "SELECT 
                HEX(pilot_id) as reviewer_id, 
                HEX(company_id) as business_id, 
                rating, comment, reviewed_at as review_date
                FROM business_review 
                WHERE company_id = UNHEX(?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$businessHexId]);
        
        $reviews = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reviews[] = BusinessReviewModel::fromArray($row);
        }
        
        return $reviews;
    }

    /**
     * Deletes a specific review based on the composite primary key.
     */
    public function delete(string $pilotHexId, string $companyHexId): bool
    {
        $sql = "DELETE FROM business_review 
                WHERE pilot_id = UNHEX(?) AND company_id = UNHEX(?)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$pilotHexId, $companyHexId]);
    }

    /**
     * Updates an existing review's rating and comment.
     */
    public function update(BusinessReviewModel $review): bool
    {
        $sql = "UPDATE business_review 
                SET rating = :rating, comment = :comment 
                WHERE pilot_id = UNHEX(:pilot_id) AND company_id = UNHEX(:company_id)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':rating'     => $review->rating,
            ':comment'    => $review->comment,
            ':pilot_id'   => $review->reviewer_id,
            ':company_id' => $review->business_id
        ]);
    }
}