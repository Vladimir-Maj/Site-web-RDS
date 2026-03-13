<?php
declare(strict_types=1);
namespace App\Models;

use PharIo\Manifest\Email;

class UserModel extends BaseModel 
{
    // UUIDs are strings in PHP context (Hex representation)
    public string $id; 
    public Email $email;
    public string $password;
    public ?string $first_name;
    public ?string $last_name;
    public bool $is_active;
    public string $created_at;

    public static function fromArray(array $data): self
    {
        $user = new self(null); 
        $user->id         = $data['id'] ?? ''; 
        $user->email      = $data['email'] ?? '';
        $user->password   = $data['password'] ?? '';
        $user->first_name = $data['first_name'] ?? null;
        $user->last_name  = $data['last_name'] ?? null;
        $user->is_active  = (bool)($data['is_active'] ?? true);
        $user->created_at = $data['created_at'] ?? '';
        
        return $user;
    }
}