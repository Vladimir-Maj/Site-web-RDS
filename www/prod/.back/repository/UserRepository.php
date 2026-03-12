<?php
// .back/repository/UserRepository.php

declare(strict_types=1);

require_once __DIR__ . '/../models/UserModel.php';

class UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Find a user by their primary ID
     */
    public function findById(int $id): ?UserModel
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Map array row to the UserModel object
        return $row ? UserModel::fromArray($row) : null;
    }

    /**
     * Find a user by email (useful for login/registration checks)
     */
    public function findByEmail(string $email): ?UserModel
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? UserModel::fromArray($row) : null;
    }

    /**
     * Handles the credential check. 
     * Separates the "finding" from the "verifying".
     */
    public function authenticate(string $email, string $password): ?UserModel
    {
        $user = $this->findByEmail($email);
        
        // Use the property from our UserModel
        if ($user && password_verify($password, $user->password)) {
            return $user;
        }
        
        return null;
    }

    /**
     * Create a new user entry. 
     * Expects an associative array of data.
     */
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

    /**
     * Specific update for CV uploads
     */
    public function updateCvPath(int $userId, string $path): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET cv_path = ? WHERE id = ?");
        return $stmt->execute([$path, $userId]);
    }

    /**
     * Validation check for uniqueness before registration
     */
    public function exists(string $email, string $username): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM users WHERE email = :email OR username = :username LIMIT 1"
        );
        $stmt->execute([
            ':email'    => $email,
            ':username' => $username
        ]);
        
        return (bool)$stmt->fetchColumn();
    }
}