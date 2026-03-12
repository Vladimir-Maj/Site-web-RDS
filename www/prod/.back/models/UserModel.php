<?php
// .back/models/UserModel.php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class UserModel extends BaseModel 
{
    public int $id;
    public string $username;
    public string $email;
    public string $password;
    public ?string $first_name;
    public ?string $last_name;
    public string $role; 
    public ?string $cv_path;
    public string $created_at;
    public string $updated_at; // Kept for compatibility

    public static function fromArray(array $data): self
    {
        // Pass null because Repo handles the DB connection
        $user = new self(null); 
        
        $user->id         = (int)($data['id'] ?? 0);
        $user->username   = $data['username'] ?? '';
        $user->email      = $data['email'] ?? '';
        $user->password   = $data['password'] ?? '';
        $user->first_name = $data['first_name'] ?? null;
        $user->last_name  = $data['last_name'] ?? null;
        $user->role       = $data['role'] ?? 'candidate';
        $user->cv_path    = $data['cv_path'] ?? null;
        $user->created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
        $user->updated_at = $data['updated_at'] ?? date('Y-m-d H:i:s');
        
        return $user;
    }

    public function getFullName(): string
    {
        return ($this->first_name && $this->last_name) 
            ? "{$this->first_name} {$this->last_name}" 
            : $this->username;
    }
}