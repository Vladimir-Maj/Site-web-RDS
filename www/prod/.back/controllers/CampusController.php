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
    public function getById(int $id): CampusModel
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
     * $data['id_campus'] est optionnel — absent ou vide déclenche un INSERT, sinon UPDATE.
     *
     * Clés attendues : name_campus, address_campus, (optionnel) id_campus
     */
    public function store(array $data): void
    {
        $this->abortIfNotPriv();

        if (empty($data['name_campus']) || empty($data['address_campus'])) {
            $this->jsonError('Fields "name_campus" and "address_campus" are required', 422);
        }

        $campus = CampusModel::fromArray([
            'id_campus'      => $data['id_campus'] ?? null,
            'name_campus'    => $data['name_campus'],
            'address_campus' => $data['address_campus'],
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
    public function deleteById(int $id): void
    {
        $this->checkRole([RoleEnum::Admin]);

        $campus = $this->campusRepository->getById($id);

        if ($campus === null) {
            $this->abort(404, "Campus not found.");
        }

        $this->campusRepository->deleteById($id);
    }
}
