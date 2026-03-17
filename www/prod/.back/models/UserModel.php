<?php
declare(strict_types=1);
namespace App\Models;

use PharIo\Manifest\Email;
use App\Models\RoleEnum;

class UserModel extends BaseModel
{
    public string $id = '';
    public Email $email;
    public string $password = '';
    public ?string $first_name = null; // Defaulting to null initializes the property
    public ?string $last_name = null;  // Defaulting to null initializes the property
    public bool $is_active = true;
    public string $created_at = '';
    public ?string $cv_path = null
    ;
    public RoleEnum $role = RoleEnum::Student;

    /**
     * Ensure the constructor initializes critical objects
     */
    public function __construct(?string $id = null)
    {
        if ($id)
            $this->id = $id;
        // We don't initialize $email here because it requires a string for the PharIo object
    }

    public static function fromArray(array $data): self
    {
        $user = new self($data['id'] ?? null);
        $user->email = new Email($data['email'] ?? 'temp@temp.com');
        $user->password = $data['password'] ?? '';
        $user->first_name = $data['first_name'] ?? null;
        $user->last_name = $data['last_name'] ?? null;
        $user->is_active = (bool) ($data['is_active'] ?? true);
        $user->created_at = $data['created_at'] ?? '';
        $user->role = isset($data['role']) ? RoleEnum::from($data['role']) : RoleEnum::Student;
        $user ->cv_path = $data['cv_path'] ??'';
        return $user;
    }
}