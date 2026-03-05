<?php
// .back/repository/UserRepository.php

declare(strict_types=1);

class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?UserModel
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? UserModel::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?UserModel
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? UserModel::fromArray($row) : null;
    }

    /**
     * Authentication: Returns the User object if password is valid
     */
    public function authenticate(string $email, string $password): ?UserModel
    {
        $user = $this->findByEmail($email);
        
        if ($user && password_verify($password, $user->password)) {
            return $user;
        }
        
        return null;
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO users (username, email, password, first_name, last_name, role, created_at) 
                VALUES (:username, :email, :password, :first_name, :last_name, :role, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':username'   => $data['username'],
            ':email'      => $data['email'],
            ':password'   => password_hash($data['password'], PASSWORD_DEFAULT),
            ':first_name' => $data['first_name'] ?? null,
            ':last_name'  => $data['last_name'] ?? null,
            ':role'       => $data['role'] ?? 'candidate'
        ]);
    }

    public function updateCvPath(int $userId, string $path): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET cv_path = ? WHERE id = ?");
        return $stmt->execute([$path, $userId]);
    }

    public function exists(string $email, string $username): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM users WHERE email = ? OR username = ? LIMIT 1");
        $stmt->execute([$email, $username]);
        return (bool)$stmt->fetchColumn();
    }
}