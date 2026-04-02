<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use App\Models\OfferModel;

class WishListRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function exists(int $studentId, int $offerId): bool
    {
        $sql = "SELECT COUNT(*)
                FROM wishlist
                WHERE student_id_wishlist = :student_id
                  AND offer_id_wishlist = :offer_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'student_id' => $studentId,
            'offer_id' => $offerId,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function add(int $studentId, int $offerId): bool
    {
        $sql = "INSERT INTO wishlist (
                    student_id_wishlist,
                    offer_id_wishlist,
                    saved_at_wishlist
                ) VALUES (
                    :student_id,
                    :offer_id,
                    NOW()
                )";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'student_id' => $studentId,
            'offer_id' => $offerId,
        ]);
    }

    public function remove(int $studentId, int $offerId): bool
    {
        $sql = "DELETE FROM wishlist
                WHERE student_id_wishlist = :student_id
                  AND offer_id_wishlist = :offer_id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'student_id' => $studentId,
            'offer_id' => $offerId,
        ]);
    }

    public function toggle(int $studentId, int $offerId): bool
    {
        if ($this->exists($studentId, $offerId)) {
            return $this->remove($studentId, $offerId);
        }

        return $this->add($studentId, $offerId);
    }

    /**
     * @return OfferModel[]
     */
    public function findOffersByStudent(int $studentId): array
    {
        $sql = "SELECT
                    o.id_internship_offer,
                    o.id_internship_offer AS id,
                    o.title_internship_offer,
                    o.title_internship_offer AS title,
                    o.description_internship_offer,
                    o.description_internship_offer AS description,
                    o.hourly_rate_internship_offer,
                    o.hourly_rate_internship_offer AS hourly_rate,
                    o.start_date_internship_offer,
                    o.start_date_internship_offer AS start_date,
                    o.duration_weeks_internship_offer,
                    o.duration_weeks_internship_offer AS duration_weeks,
                    o.is_active_internship_offer,
                    o.is_active_internship_offer AS is_active,
                    o.published_at_internship_offer,
                    o.published_at_internship_offer AS published_at,
                    o.company_site_id_internship_offer,
                    o.company_site_id_internship_offer AS site_id,
                    c.id_company,
                    c.id_company AS company_id,
                    c.name_company,
                    c.name_company AS company_name,
                    s.city_company_site,
                    s.city_company_site AS location,
                    s.address_company_site,
                    s.address_company_site AS address
                FROM wishlist w
                INNER JOIN internship_offer o
                    ON w.offer_id_wishlist = o.id_internship_offer
                INNER JOIN company_site s
                    ON o.company_site_id_internship_offer = s.id_company_site
               INNER JOIN company c
                    ON s.company_id_company_site = c.id_company
                WHERE w.student_id_wishlist = :student_id
                ORDER BY w.saved_at_wishlist DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['student_id' => $studentId]);

        return array_map(
            fn(array $row) => OfferModel::fromArray($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }
}

