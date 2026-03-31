<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\CampusModel;
use App\Models\RoleEnum;
use App\Repository\CampusRepository;
use Twig\Environment;

class CampusController extends BaseController
{
    public function __construct(
        Environment $twig,
        private CampusRepository $campusRepository
    ) {
        parent::__construct($twig);
    }

    /**
     * Retourne tous les campus.
     * Réservé aux utilisateurs privilégiés (Admin ou Pilote).
     *
     * @return CampusModel[]
     */
    public function index(): array
    {
        $this->abortIfNotPriv();

        return $this->campusRepository->findAll();
    }

    /**
     * Retourne un campus par son identifiant.
     */
    public function getById(string $id): CampusModel
    {
        $this->abortIfNotPriv();

        $campus = $this->campusRepository->getById($id);

        if ($campus === null) {
            $this->abort(404, "Campus not found.");
        }

        return $campus;
    }

    /**
     * Crée ou met à jour un campus depuis un tableau d'attributs.
     * $data['id'] est optionnel — absent ou vide déclenche un INSERT, sinon UPDATE.
     *
     * Clés attendues : name, address, (optionnel) id
     */
    public function store(array $data): void
    {
        $this->abortIfNotPriv();

        if (empty($data['name']) || empty($data['address'])) {
            $this->jsonError('Fields "name" and "address" are required', 422);
        }

        $campus = CampusModel::fromArray([
            'id'      => $data['id'] ?? '',
            'name'    => $data['name'],
            'address' => $data['address'],
        ]);

        $success = $this->campusRepository->save($campus);

        if (!$success) {
            $this->jsonError('Failed to save campus', 500);
        }
    }

    /**
     * Supprime un campus par son identifiant.
     * Réservé aux Admin uniquement.
     */
    public function deleteById(string $id): void
    {
        $this->checkRole([RoleEnum::Admin]);

        $campus = $this->campusRepository->getById($id);

        if ($campus === null) {
            $this->abort(404, "Campus not found.");
        }

        $this->campusRepository->deleteById($id);
    }
}
