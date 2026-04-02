<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\PromotionModel;
use App\Models\RoleEnum;
use App\Models\UserModel;
use App\Repository\UserRepository;
use App\Util;
use PharIo\Manifest\Email;
use Twig\Environment;

class UserController extends BaseController
{
    public function __construct(
        private readonly UserRepository $repo, 
        Environment $twig
    ) {
        parent::__construct($twig);
    }

    /**
     * Retrieves a user by email, ensuring the requester is authorized.
     */
    public function getUserFromMail(Email $mail): ?UserModel
    {
        if ($this->isTargetOrPrivileged($mail) === false) {
            $this->abort(403, "Unauthorized access.");
        }

        return $this->repo->findByEmail($mail->asString());
    }

    /**
     * Retrieves the promotion associated with a pilot ID.
     * Standardized to use int $pid for the updated schema.
     */
    public function getPromoByPilote(int $pid): ?PromotionModel
    {
        return $this->repo->getPromoByPilote($pid);
    }

    /**
     * Retrieves a user by ID with authorization callback.
     */
    public function getUserById(int $id, callable $isAuthorized): ?UserModel
    {
        if ($this->isTargetOrPrivileged($id) === false) {
            $this->abort(403, "Unauthorized access.");
        }

        return $this->repo->findById($id);
    }

    /**
     * API Endpoint: Returns user data in JSON format.
     */
    public function getUserByIdJson(int $id): void
    {
        if (!$this->isTargetOrPrivileged($id)) {
            $this->jsonError("Forbidden", 403);
        }

        $user = $this->repo->findById($id);

        if (!$user) {
            $this->jsonError("User not found", 404);
        }

        // Note: Repository attributes should map to {attribute}_{table} internally,
        // but we output clean keys for the JSON response.
        $this->jsonResponse([
            'id'         => $user->id,
            'email'      => $user->email->asString(),
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'role'       => $user->role->value,
        ]);
    }

    /**
     * Logic to persist a new user. Restricted to privileged roles.
     */
    public function makeUser(UserModel $user): UserModel
    {
        if ($this->isPrivileged() === false) {
            $this->abort(403, "Unauthorized access.");
        }

        if (!empty($user->id) && $this->repo->findById((int) $user->id) !== null) {
            $this->abort(400, "User with this ID already exists.");
        }

        if ($this->repo->findByEmail($user->email->asString()) !== null) {
            $this->abort(400, "User with this email already exists.");
        }

        $this->repo->push($user);

        return $this->repo->findByEmail($user->email->asString());
    }
}
