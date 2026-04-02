<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use App\Models\BuisnessReviewModel;

class ReviewRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new business review.
     * Composite primary key:
     * (pilot_id_business_review, company_id_business_review)
     */
    public function push(BuisnessReviewModel $review): bool
    {
        $sql = "INSERT INTO business_review (
                    pilot_id_business_review,
                    company_id_business_review,
                    rating_business_review,
                    comment_business_review,
                    reviewed_at_business_review
                ) VALUES (
                    :pilot_id,
                    :company_id,
                    :rating,
                    :comment,
                    :reviewed_at
                )";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':pilot_id'    => $review->pilot_id_business_review,
            ':company_id'  => $review->company_id_business_review,
            ':rating'      => $review->rating_business_review,
            ':comment'     => $review->comment_business_review,
            ':reviewed_at' => $review->reviewed_at_business_review->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Finds all reviews for a specific company.
     */
    public function findByBusinessId(int|string $companyId): array
    {
        $sql = "SELECT
                    pilot_id_business_review,
                    pilot_id_business_review AS reviewer_id,
                    company_id_business_review,
                    company_id_business_review AS business_id,
                    rating_business_review,
                    rating_business_review AS rating,
                    comment_business_review,
                    comment_business_review AS comment,
                    reviewed_at_business_review,
                    reviewed_at_business_review AS review_date
                FROM business_review
                WHERE company_id_business_review = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int) $companyId]);

        $reviews = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $reviews[] = BuisnessReviewModel::fromArray($row);
        }

        return $reviews;
    }

    /**
     * Deletes a specific review based on the composite primary key.
     */
    public function delete(int|string $pilotId, int|string $companyId): bool
    {
        $sql = "DELETE FROM business_review
                WHERE pilot_id_business_review = ?
                  AND company_id_business_review = ?";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([(int) $pilotId, (int) $companyId]);
    }

    /**
     * Updates an existing review's rating and comment.
     */
    public function update(BuisnessReviewModel $review): bool
    {
        $sql = "UPDATE business_review
                SET
                    rating_business_review = :rating,
                    comment_business_review = :comment,
                    reviewed_at_business_review = :reviewed_at
                WHERE pilot_id_business_review = :pilot_id
                  AND company_id_business_review = :company_id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':rating'      => $review->rating_business_review,
            ':comment'     => $review->comment_business_review,
            ':reviewed_at' => $review->reviewed_at_business_review->format('Y-m-d H:i:s'),
            ':pilot_id'    => $review->pilot_id_business_review,
            ':company_id'  => $review->company_id_business_review
        ]);
    }
}
