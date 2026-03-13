<?php

declare(strict_types=1);
namespace App\Controller;
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
        if ($this->isTargetOrPrivileged() == false) {
            $this->abort(403, "Unauthorized access.");
        }
        return $this->repo->findByMail($mail->asString());
    }

    public function getUserById(string $id): UserModel
    {
        if ($this->isTargetOrPrivileged($id) == false) {
            $this->abort(403, "Unauthorized access.");
        }

        return $this->repo->findById($id);
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