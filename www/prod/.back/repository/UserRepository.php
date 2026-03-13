<?php
declare(strict_types=1);
namespace App\Repository;

use PDO;
use App\Models\UserModel;

class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function findById(string $hexId): ?UserModel
    {
        // CRITICAL: Must use UNHEX for BINARY(16) lookup
        $stmt = $this->pdo->prepare("SELECT HEX(id) as id, email, password, first_name, last_name, is_active, created_at FROM user WHERE id = UNHEX(?)");
        $stmt->execute([$hexId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? UserModel::fromArray($row) : null;
    }

    public function push(UserModel $user): bool
    {
        // We let the Trigger generate the UUID, or we pass it in if we have one
        $sql = "INSERT INTO user (email, password, first_name, last_name) 
                VALUES (:email, :password, :first_name, :last_name)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':email'      => $user->email,
            ':password'   => $user->password, // Ensure this is pre-hashed!
            ':first_name' => $user->first_name,
            ':last_name'  => $user->last_name
        ]);
    }

    /**
     * Harsh fix: Links a user to the student table
     */
    public function makeStudent(string $userHexId, string $status = 'Searching'): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO student (user_id, status) VALUES (UNHEX(?), ?)");
        return $stmt->execute([$userHexId, $status]);
    }
}