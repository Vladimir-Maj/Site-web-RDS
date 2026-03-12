<?php
// .back/repository/ApplicationRepository.php
declare(strict_types=1);

class ApplicationRepository {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Utility: Submit a new internship application
     */
    public function postApplication(string $userId, string $offerId, string $motivationPath, string $cvPath): bool {
        $sql = "INSERT INTO application (student_id, offer_id, cv_path, cover_letter_path, status) 
                VALUES (UNHEX(:uID), UNHEX(:oID), :cv, :ml, 'pending')";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':uID' => $userId,
            ':oID' => $offerId,
            ':cv'  => $cvPath,
            ':ml'  => $motivationPath
        ]);
    }

    public function findByStudent(string $studentId): array {
        $stmt = $this->pdo->prepare("SELECT HEX(id) as id, HEX(student_id) as student_id, HEX(offer_id) as offer_id, cv_path, cover_letter_path, status, applied_at 
                                     FROM application WHERE student_id = UNHEX(?)");
        $stmt->execute([$studentId]);
        return array_map(fn($row) => ApplicationModel::fromArray($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}