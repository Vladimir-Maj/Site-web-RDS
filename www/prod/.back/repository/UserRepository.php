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
}
