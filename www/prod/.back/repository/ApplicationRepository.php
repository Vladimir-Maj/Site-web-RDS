<?php
// .back/repository/ApplicationRepository.php
declare(strict_types=1);

namespace App\Repository;

use PDO;

use App\Models\ApplicationModel;

class ApplicationRepository
{
    /**
     * TABLE
     * mysql> describe application;
     * +-------------------+---------------------------------------+------+-----+-------------------+-------------------+
     * | Field             | Type                                  | Null | Key | Default           | Extra             |
     * +-------------------+---------------------------------------+------+-----+-------------------+-------------------+
     * | id                | binary(16)                            | NO   | PRI | NULL              |                   |
     * | student_id        | binary(16)                            | NO   | MUL | NULL              |                   |
     * | offer_id          | binary(16)                            | NO   | MUL | NULL              |                   |
     * | cv_path           | varchar(255)                          | YES  |     | NULL              |                   |
     * | cover_letter_path | varchar(255)                          | YES  |     | NULL              |                   |
     * | status            | enum('pending','accepted','rejected') | YES  |     | pending           |                   |
     * | applied_at        | datetime                              | YES  |     | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
     * +-------------------+---------------------------------------+------+-----+-------------------+-------------------+
     * 
     * MODEL
     * 
     *     public string $id;
    public string $student_id;
    public string $offer_id;
    public ?string $cv_path;
    public ?string $cover_letter_path;
    public string $status; // pending, accepted, rejected
    public string $applied_at;

    public static function fromArray(array $data): self {
        $inst = new self(null);
        $inst->id = $data['id'] ?? '';
        $inst->student_id = $data['student_id'] ?? '';
        $inst->offer_id = $data['offer_id'] ?? '';
        $inst->cv_path = $data['cv_path'] ?? null;
        $inst->cover_letter_path = $data['cover_letter_path'] ?? null;
        $inst->status = $data['status'] ?? 'pending';
        $inst->applied_at = $data['applied_at'] ?? '';
        return $inst;
    }
     */
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Centralisation de la sauvegarde (Create ou Update)
     */
    public function push(ApplicationModel $app): bool
    {
        // Si l'ID est vide, on en génère un (ou on laisse MySQL le faire)
        if (empty($app->id)) {
            $sql = "INSERT INTO application (id, student_id, offer_id, cv_path, cover_letter_path, status, applied_at) 
                    VALUES (UNHEX(REPLACE(UUID(), '-', '')), UNHEX(:sID), UNHEX(:oID), :cv, :cl, :status, NOW())";
        } else {
            $sql = "UPDATE application SET status = :status, cv_path = :cv, cover_letter_path = :cl 
                    WHERE id = UNHEX(:id)";
        }

        $stmt = $this->pdo->prepare($sql);
        
        $params = [
            ':sID'    => $app->student_id,
            ':oID'    => $app->offer_id,
            ':cv'     => $app->cv_path,
            ':cl'     => $app->cover_letter_path,
            ':status' => $app->status
        ];

        if (!empty($app->id)) {
            $params[':id'] = $app->id;
        }

        return $stmt->execute($params);
    }

    public function isOwner(string $appId, string $uid) {
        $app = $this->findById($appId);
        return $app && $app->student_id === $uid;
    }

    public function findById(string $id): ?ApplicationModel
    {
        $sql = "SELECT HEX(id) as id, HEX(student_id) as student_id, HEX(offer_id) as offer_id, 
                cv_path, cover_letter_path, status, applied_at 
                FROM application WHERE id = UNHEX(?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? ApplicationModel::fromArray($row) : null;
    }

    public function findByStudent(string $studentId): array
    {
        $sql = "SELECT HEX(id) as id, HEX(student_id) as student_id, HEX(offer_id) as offer_id, 
                cv_path, cover_letter_path, status, applied_at 
                FROM application WHERE student_id = UNHEX(?) ORDER BY applied_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$studentId]);
        
        return array_map(fn($row) => ApplicationModel::fromArray($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function delete(string $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM application WHERE id = UNHEX(?)");
        return $stmt->execute([$id]);
    }
}