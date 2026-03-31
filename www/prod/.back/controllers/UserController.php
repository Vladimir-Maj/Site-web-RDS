<?php

declare(strict_types=1);
namespace App\Controllers;
use App\Controllers\BaseController;
use App\Models\PromotionModel;
use App\Models\RoleEnum;
use App\Models\UserModel;
use App\Repository\UserRepository;
use App\Util;
use PharIo\Manifest\Email;
use Twig\Environment;

class UserController extends BaseController
{
    private UserRepository $repo;

    public function __construct(UserRepository $repo, Environment $twig)
    {
        $this->repo = $repo;
        $this->twig = $twig;
    }

    public function getUserFromMail(Email $mail): ?UserModel
    {
        if ($this->isTargetOrPrivileged($mail) == false) {
            $this->abort(403, "Unauthorized access.");
        }
        return $this->repo->findByEmail($mail->asString());
    }

    public function getPromoByPilote(string $pid): ?PromotionModel
    {
        return $this->repo->getPromoByPilote($pid);
    }

    public function getUserById(string $id, callable $isAuthorized): ?UserModel
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