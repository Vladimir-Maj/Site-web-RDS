<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use PharIo\Manifest\Email;
use App\Models\RoleEnum;
use App\Models\UserModel;
use App\Models\PromotionModel;

class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Finds a user and determines their role by checking specialized tables.
     */
    public function findById(int|string $id): ?UserModel
    {
        $sql = "SELECT
                    u.id_user,
                    u.id_user AS id,
                    u.email_user,
                    u.email_user AS email,
                    u.password,
                    u.first_name_user,
                    u.first_name_user AS first_name,
                    u.last_name_user,
                    u.last_name_user AS last_name,
                    u.is_active_user,
                    u.is_active_user AS is_active,
                    u.created_at_user,
                    u.created_at_user AS created_at,
                    s.status_student,
                    s.status_student AS status,
                    CASE
                        WHEN a.id_administrator IS NOT NULL THEN 'admin'
                        WHEN p.id_pilot IS NOT NULL THEN 'pilote'
                        WHEN s.id_student IS NOT NULL THEN 'student'
                        ELSE NULL
                    END AS role
                FROM user u
                LEFT JOIN administrator a ON u.id_user = a.id_administrator
                LEFT JOIN pilot p ON u.id_user = p.id_pilot
                LEFT JOIN student s ON u.id_user = s.id_student
                WHERE u.id_user = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? UserModel::fromArray($row) : null;
    }

    public function findByEmail(string|Email $email): ?UserModel
    {
        $emailString = ($email instanceof Email) ? $email->asString() : $email;

        $sql = "SELECT
                    u.id_user,
                    u.id_user AS id,
                    u.email_user,
                    u.email_user AS email,
                    u.password,
                    u.first_name_user,
                    u.first_name_user AS first_name,
                    u.last_name_user,
                    u.last_name_user AS last_name,
                    u.is_active_user,
                    u.is_active_user AS is_active,
                    u.created_at_user,
                    u.created_at_user AS created_at,
                    s.status_student,
                    s.status_student AS status,
                    CASE
                        WHEN a.id_administrator IS NOT NULL THEN 'admin'
                        WHEN p.id_pilot IS NOT NULL THEN 'pilote'
                        WHEN s.id_student IS NOT NULL THEN 'student'
                        ELSE NULL
                    END AS role
                FROM user u
                LEFT JOIN administrator a ON u.id_user = a.id_administrator
                LEFT JOIN pilot p ON u.id_user = p.id_pilot
                LEFT JOIN student s ON u.id_user = s.id_student
                WHERE u.email_user = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$emailString]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? UserModel::fromArray($row) : null;
    }

    /**
     * Inserts a user.
     * Returns the numeric ID of the newly created user.
     */
    public function push(UserModel $user): ?int
    {
        try {
            $this->pdo->beginTransaction();

            $sql = "INSERT INTO user (
                        email_user,
                        password,
                        first_name_user,
                        last_name_user,
                        is_active_user
                    ) VALUES (
                        :email,
                        :password,
                        :first_name,
                        :last_name,
                        :is_active
                    )";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':email' => $user->email->asString(),
                ':password' => $user->password,
                ':first_name' => $user->first_name,
                ':last_name' => $user->last_name,
                ':is_active' => $user->is_active ? 1 : 0,
            ]);

            $newUserId = (int) $this->pdo->lastInsertId();

            if ($user->role === RoleEnum::Admin) {
                $stmt = $this->pdo->prepare("INSERT INTO administrator (id_administrator) VALUES (?)");
                $stmt->execute([$newUserId]);
            } elseif ($user->role === RoleEnum::Pilote) {
                $stmt = $this->pdo->prepare("INSERT INTO pilot (id_pilot) VALUES (?)");
                $stmt->execute([$newUserId]);
            } elseif ($user->role === RoleEnum::Student) {
                $stmt = $this->pdo->prepare("INSERT INTO student (id_student, status_student) VALUES (?, 'searching')");
                $stmt->execute([$newUserId]);
            }

            $this->pdo->commit();
            return $newUserId;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return null;
        }
    }

    /**
     * La BDD fournie ne contient pas de colonne CV.
     * Méthode conservée pour compatibilité applicative.
     */
    public function updateCvPath(int|string $userId, string $path): bool
    {
        return false;
    }

    /**
     * Specializes a user as a student and enrolls them in a promotion.
     */
    public function makeStudent(int|string $userId, int|string $promoId, string $status = 'searching'): bool
    {
        try {
            $this->pdo->beginTransaction();

            $sqlStudent = "INSERT INTO student (id_student, status_student)
                           VALUES (?, ?)
                           ON DUPLICATE KEY UPDATE status_student = VALUES(status_student)";
            $stmtStudent = $this->pdo->prepare($sqlStudent);
            $stmtStudent->execute([(int) $userId, $status]);

            $sqlEnroll = "INSERT INTO student_enrollment (
                              promotion_id_student_enrollment,
                              student_id_student_enrollment,
                              enrolled_at
                          ) VALUES (?, ?, NOW())";
            $stmtEnroll = $this->pdo->prepare($sqlEnroll);
            $stmtEnroll->execute([(int) $promoId, (int) $userId]);

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function delete(int|string $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM user WHERE id_user = ?");
        return $stmt->execute([(int) $id]);
    }

    /**
     * Gets the most recently assigned promotion for a specific pilot.
     */
    public function getPromoByPilote(int|string $pilotId): ?PromotionModel
    {
        $sql = "SELECT
                    p.id_promotion,
                    p.id_promotion AS id,
                    p.label_promotion,
                    p.label_promotion AS label,
                    p.academic_year_promotion,
                    p.academic_year_promotion AS academic_year,
                    p.campus_id_promotion,
                    p.campus_id_promotion AS campus_id
                FROM promotion p
                INNER JOIN promotion_assignment pa
                    ON p.id_promotion = pa.promotion_assignment_id
                WHERE pa.pilot_assignment_id = ?
                ORDER BY pa.assigned_at DESC
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int) $pilotId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? PromotionModel::fromArray($row) : null;
    }

    /**
     * Gets all students belonging to the latest promotion assigned to a pilot.
     * Returns an array of UserModel objects.
     */
    public function getStudentsByPilote(int|string $pilotId): array
    {
        $sql = "SELECT
                    u.id_user,
                    u.id_user AS id,
                    u.email_user,
                    u.email_user AS email,
                    u.password,
                    u.first_name_user,
                    u.first_name_user AS first_name,
                    u.last_name_user,
                    u.last_name_user AS last_name,
                    u.is_active_user,
                    u.is_active_user AS is_active,
                    u.created_at_user,
                    u.created_at_user AS created_at,
                    'student' AS role,
                    s.status_student,
                    s.status_student AS status
                FROM user u
                INNER JOIN student s
                    ON u.id_user = s.id_student
                INNER JOIN student_enrollment se
                    ON s.id_student = se.student_id_student_enrollment
                INNER JOIN promotion_assignment pa
                    ON se.promotion_id_student_enrollment = pa.promotion_assignment_id
                WHERE pa.pilot_assignment_id = :pilot_id
                  AND pa.assigned_at = (
                      SELECT MAX(pa2.assigned_at)
                      FROM promotion_assignment pa2
                      WHERE pa2.pilot_assignment_id = :pilot_id_sub
                  )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'pilot_id' => (int) $pilotId,
            'pilot_id_sub' => (int) $pilotId,
        ]);

        $students = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $students[] = UserModel::fromArray($row);
        }

        return $students;
    }

    /**
     * Recherche les pilotes en fonction de critères de filtrage 
     * (nom, statut) et de pagination.
     */
    public function searchPilots(array $filters): array
    {
        $limit = (int) ($filters['limit'] ?? 10);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $sql = "SELECT
                u.id_user AS id,
                u.email_user AS email,
                u.first_name_user AS first_name,
                u.last_name_user AS last_name,
                u.is_active_user AS is_active,
                u.created_at_user AS created_at
            FROM user u
            INNER JOIN pilot p ON u.id_user = p.id_pilot
                WHERE 1=1";

        $params = [];
        if (!empty($filters['name'])) {
            $sql .= " AND (u.first_name_user LIKE :name OR u.last_name_user LIKE :name OR u.email_user LIKE :name)";
            $params['name'] = '%' . $filters['name'] . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= " AND u.is_active_user = :is_active";
            $params['is_active'] = ($filters['status'] === 'active') ? 1 : 0;
        }

        $sql .= " ORDER BY u.last_name_user ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) $stmt->bindValue($key, $val);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    /**
     * Met à jour les infos de base d'un utilisateur
     */
    public function updateUser(int|string $id, array $data): bool
    {
        $sql = "UPDATE user 
                SET first_name_user = :first_name, 
                    last_name_user = :last_name, 
                    is_active_user = :is_active 
                WHERE id_user = :id";
                
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':is_active' => $data['is_active'],
            ':id' => (int) $id
        ]);
    }

    /**
     * Met à jour le mot de passe d'un utilisateur
     */
    public function updatePassword(int|string $id, string $hashedPassword): bool
    {
        $sql = "UPDATE user SET password = :password WHERE id_user = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => (int) $id
        ]);
    }

    /**
     * Assigne une promotion à un pilote
     */
    public function assignPromotionToPilot(int|string $pilotId, int|string $promoId): bool
    {
        $sql = "INSERT INTO promotion_assignment (promotion_assignment_id, pilot_assignment_id, assigned_at) 
                VALUES (:promo, :pilot, NOW())";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':promo' => (int) $promoId,
            ':pilot' => (int) $pilotId
        ]);
    }

    /**
     * Recherche les étudiants avec pagination et filtres (Statut de recherche)
     */
    public function searchStudents(array $filters): array
    {
        $limit = (int) ($filters['limit'] ?? 10);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $sql = "SELECT u.id_user as id, u.email_user as email, u.first_name_user as first_name, u.last_name_user as last_name, u.is_active_user as is_active, u.created_at_user as created_at, 
                   s.status_student as status, pr.label_promotion as promo_label
                FROM user u
            INNER JOIN student s ON u.id_user = s.id_student
            LEFT JOIN student_enrollment se ON s.id_student = se.student_id_student_enrollment
            LEFT JOIN promotion pr ON se.promotion_id_student_enrollment = pr.id_promotion
                WHERE 1=1";

        $params = [];
        if (!empty($filters['name'])) {
            $sql .= " AND (u.first_name_user LIKE :name OR u.last_name_user LIKE :name OR u.email_user LIKE :name)";
            $params['name'] = '%' . $filters['name'] . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= " AND s.status_student = :status";
            $params['status'] = $filters['status'];
        }

        $sql .= " ORDER BY u.last_name_user ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) $stmt->bindValue($key, $val);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Met à jour le statut spécifique de l'étudiant
     */
    public function updateStudentStatus(int|string $id, string $status): bool
    {
        $sql = "UPDATE student SET status_student = :status WHERE id_student = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':status' => $status,
            ':id' => (int) $id
        ]);
    }

    /**
     * Récupère la promotion actuelle d'un étudiant
     */
    public function getPromoByStudent(int|string $studentId): ?array
    {
        $sql = "SELECT p.id_promotion as id, p.label_promotion as label, p.academic_year_promotion as academic_year 
                FROM promotion p
                INNER JOIN student_enrollment se ON p.id_promotion = se.promotion_id_student_enrollment
                WHERE se.student_id_student_enrollment = ?
                ORDER BY se.enrolled_at DESC LIMIT 1";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int) $studentId]);
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $res ?: null;
    }

    /**
     * Met à jour la promotion d'un étudiant (supprime l'ancienne et ajoute la nouvelle)
     */
    public function updateStudentEnrollment(int|string $studentId, int|string $promoId): bool
    {
        $stmtDelete = $this->pdo->prepare("DELETE FROM student_enrollment WHERE student_id_student_enrollment = ?");
        $stmtDelete->execute([(int) $studentId]);

        $sql = "INSERT INTO student_enrollment (promotion_id_student_enrollment, student_id_student_enrollment, enrolled_at) 
                VALUES (?, ?, NOW())";
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([(int) $promoId, (int) $studentId]);
    }
}
