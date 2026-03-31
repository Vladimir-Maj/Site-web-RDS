<?php

declare(strict_types=1);
namespace App\Controllers;
use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Repository\UserRepository;
use PharIo\Manifest\Email;

class UserController extends BaseController
{
    private UserRepository $repo;

    public function __construct(UserRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getUserFromMail(Email $mail): ?UserModel
    {
        if ($this->isTargetOrPrivileged($mail) == false) {
            $this->abort(403, "Unauthorized access.");
        }
        return $this->repo->findByEmail($mail->asString());
    }

    

    public function getUserById(string $id, callable $isAuthorized): ?UserModel
    {
        if ($this->isTargetOrPrivileged($id) == false) {
            $this->abort(403, "Unauthorized access.");
        }

        return $this->repo->findById($id);
    }

    public function getUserByIdJson(string $id): void
    {
        if (!$this->isTargetOrPrivileged($id)) {
            $this->jsonError("Forbidden", 403);
        }
    
        $user = $this->repo->findById($id);
    
        if (!$user) {
            $this->jsonError("User not found", 404);
        }
    
        $this->jsonResponse([
            'id'         => $user->id,
            'email'      => $user->email->asString(),
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'role'       => $user->role->value,
        ]);
    }

    public function makeUser(UserModel $user): UserModel
    {
        //TODO: add user creation limit to prevent filling the DB.
        if ($this->isPrivileged() == false) {
            $this->abort(403, "Unauthorized access.");
        }

        if ($this->repo->findById($user->id) != null || $this->repo->findByEmail($user->email) != null) {
            $this->abort(400, "User with this ID or email already exists.");
        } else {
            $this->repo->push($user);
        }
        return $this->repo->findByEmail($user->email);
    }
}
