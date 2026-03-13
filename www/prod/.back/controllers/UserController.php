<?php

declare(strict_types=1);
namespace App\Controller;
use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Repository\UserRepository;

class UserController extends BaseController
{
    private $repo;

    public function __construct(UserRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getUser($id): UserModel
    {
        if ($this->isPrivileged() == false && $_SESSION['user_id'] != $id) {
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