<?php
/**
 * User Repository
 * Path: /prod/.back/repository/UserRepository.php
 */

class UserRepository {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Find a user by ID
     */
    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Find a user by Email
     */
    public function findByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Check if email or username is already taken
     */
    public function exists(string $email, string $username): bool {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        return (bool)$stmt->fetch();
    }

    /**
     * Authentication logic
     */
    public function authenticate(string $email, string $password): ?array {
        $user = $this->findByEmail($email);
        
        if ($user && password_verify($password, $user['password'])) {
            // Remove password from the returned array for security
            unset($user['password']);
            return $user;
        }
        
        return null;
    }

    /**
     * Create a new user (Registration)
     */
    public function create(array $data): bool {
        $sql = "INSERT INTO users (username, email, password, role, created_at) 
                VALUES (:username, :email, :password, :role, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':username' => $data['username'],
            ':email'    => $data['email'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':role'     => $data['role'] ?? 'candidate'
        ]);
    }

    /**
     * Update CV path (Utility for upload_cv.php)
     */
    public function updateCvPath(int $userId, string $path): bool {
        $stmt = $this->pdo->prepare("UPDATE users SET cv_path = ? WHERE id = ?");
        return $stmt->execute([$path, $userId]);
    }

    /**
     * Utility: Role validation
     */
    public function isRecruiter(int $userId): bool {
        $user = $this->findById($userId);
        return $user && $user['role'] === 'recruiter';
    }
}