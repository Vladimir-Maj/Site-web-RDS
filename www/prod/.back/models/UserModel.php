<?php
// .back/models/UserModel.php

declare(strict_types=1);

class UserModel
{
    public int $id;
    public string $username;
    public string $email;
    public string $password;
    public ?string $first_name;
    public ?string $last_name;
    public string $role; // enum('candidate','recruiter','admin')
    public ?string $cv_path;
    public string $created_at;
    public string $updated_at;

    public static function fromArray(array $data): self
    {
        $user = new self();
        $user->id = (int)$data['id'];
        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->password = $data['password'];
        $user->first_name = $data['first_name'] ?? null;
        $user->last_name = $data['last_name'] ?? null;
        $user->role = $data['role'] ?? 'candidate';
        $user->cv_path = $data['cv_path'] ?? null;
        $user->created_at = $data['created_at'];
        $user->updated_at = $data['updated_at'];
        
        return $user;
    }

    public function getFullName(): string
    {
        if ($this->first_name && $this->last_name) {
            return "{$this->first_name} {$this->last_name}";
        }
        return $this->username;
    }
}