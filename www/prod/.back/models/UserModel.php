<?php
declare(strict_types=1);

namespace App\Models;

use PharIo\Manifest\Email;
use App\Models\RoleEnum;

class UserModel extends BaseModel
{
    // Compat ancienne version
    public int|string|null $id = null;
    public Email $email;
    public string $password = '';
    public ?string $first_name = null;
    public ?string $last_name = null;
    public bool $is_active = true;
    public string $created_at = '';
    public ?string $cv_path = null;
    public RoleEnum $role = RoleEnum::Student;

    // Compat nouvelle BDD
    public int|string|null $id_user = null;
    public ?string $email_user = null;
    public ?string $first_name_user = null;
    public ?string $last_name_user = null;
    public bool $is_active_user = true;
    public string $created_at_user = '';
    public ?string $cv_path_user = null;

    public function __construct(int|string|null $id = null)
    {
        parent::__construct(null);

        if ($id !== null && $id !== '') {
            $this->id = $id;
            $this->id_user = $id;
        }
    }

    public static function fromArray(array $data): self
    {
        $resolvedId = $data['id_user'] ?? ($data['id'] ?? null);

        $resolvedEmail = $data['email_user'] ?? ($data['email'] ?? 'temp@temp.com');
        $resolvedFirstName = $data['first_name_user'] ?? ($data['first_name'] ?? null);
        $resolvedLastName = $data['last_name_user'] ?? ($data['last_name'] ?? null);
        $resolvedIsActive = (bool) ($data['is_active_user'] ?? ($data['is_active'] ?? true));
        $resolvedCreatedAt = $data['created_at_user'] ?? ($data['created_at'] ?? '');
        $resolvedCvPath = $data['cv_path_user'] ?? ($data['cv_path'] ?? null);

        $roleValue = $data['role'] ?? null;
        $resolvedRole = $roleValue instanceof RoleEnum
            ? $roleValue
            : (is_string($roleValue) ? (RoleEnum::tryFrom($roleValue) ?? RoleEnum::Student) : RoleEnum::Student);

        $user = new self($resolvedId);

        $user->email = new Email($resolvedEmail);
        $user->password = $data['password'] ?? '';

        // Ancienne version
        $user->first_name = $resolvedFirstName;
        $user->last_name = $resolvedLastName;
        $user->is_active = $resolvedIsActive;
        $user->created_at = $resolvedCreatedAt;
        $user->cv_path = $resolvedCvPath;
        $user->role = $resolvedRole;

        // Nouvelle BDD
        $user->id_user = $resolvedId;
        $user->email_user = $resolvedEmail;
        $user->first_name_user = $resolvedFirstName;
        $user->last_name_user = $resolvedLastName;
        $user->is_active_user = $resolvedIsActive;
        $user->created_at_user = $resolvedCreatedAt;
        $user->cv_path_user = $resolvedCvPath;

        return $user;
    }
}
