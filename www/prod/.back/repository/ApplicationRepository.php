<?php
// .back/repository/ApplicationRepository.php
declare(strict_types=1);

namespace App\Repository;

use PDO;

use App\Models\ApplicationModel;

class ApplicationRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Admin/Pilote Utility: List all applications for a specific offer
     */
    public function findByOffer(string $offerId): array
    {
        $sql = "SELECT 
                HEX(a.id) as id, 
                HEX(a.student_id) as student_id, 
                u.first_name, 
                u.last_name, 
                a.cv_path, 
                a.cover_letter_path, 
                a.status, 
                a.applied_at 
            FROM application a
            JOIN user u ON a.student_id = u.id
            WHERE a.offer_id = UNHEX(?)
            ORDER BY a.applied_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$offerId]);

        // Using your existing fromArray mapper
        return array_map(fn($row) => ApplicationModel::fromArray($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function push(ApplicationModel $application): void
    {
        $this->postApplication(
            $application->getStudentId(),
            $application->offer_id,
            $application->cover_letter_path,
            $application->cv_path
        );
    }

    /**
     * Utility: Submit a new internship application
     */
    public function postApplication(string $userId, string $offerId, string $motivationPath, string $cvPath): bool
    {
        $sql = "INSERT INTO application (student_id, offer_id, cv_path, cover_letter_path, status) 
                VALUES (UNHEX(:uID), UNHEX(:oID), :cv, :ml, 'pending')";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':uID' => $userId,
            ':oID' => $offerId,
            ':cv' => $cvPath,
            ':ml' => $motivationPath
        ]);
    }

    public function findByStudent(string $studentId): array
    {
        $stmt = $this->pdo->prepare("SELECT HEX(id) as id, HEX(student_id) as student_id, HEX(offer_id) as offer_id, cv_path, cover_letter_path, status, applied_at 
                                     FROM application WHERE student_id = UNHEX(?)");
        $stmt->execute([$studentId]);
        return array_map(fn($row) => ApplicationModel::fromArray($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Fetch a single application by its ID
     */
    public function findById(string $id): ?ApplicationModel
    {
        $sql = "SELECT 
                HEX(id) as id, 
                HEX(student_id) as student_id, 
                HEX(offer_id) as offer_id, 
                cv_path, 
                cover_letter_path, 
                status, 
                applied_at 
            FROM application 
            WHERE id = UNHEX(?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return ApplicationModel::fromArray($row);
    }

    /**
     * Permanently remove an application from the database
     */
    public function delete(string $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM application WHERE id = UNHEX(?)");
        return $stmt->execute([$id]);
    }
}