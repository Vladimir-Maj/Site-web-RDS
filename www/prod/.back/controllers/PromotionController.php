<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repository\CampusRepository;
use App\Repository\PromotionRepository;
use App\Models\PromotionModel;
use App\Models\RoleEnum;
use Twig\Environment;

class PromotionController extends BaseController 
{
    public function __construct(
        Environment $twig,
        private readonly PromotionRepository $promotionRepository,
        private readonly CampusRepository $campusRepository
    ) {
        parent::__construct($twig);
    }

    /**
     * Get all promotions for a specific campus.
     * * @return array Array of Promotion objects
     */
    public function index(int $campusId): array 
    {
        $this->abortIfNotPriv();
        return $this->promotionRepository->getByCampus($campusId);
    }

    /**
     * Persist a Promotion model directly.
     */
    public function push(PromotionModel $model): void 
    {
        $this->abortIfNotPriv();

        $success = $this->promotionRepository->save($model);

        if (!$success) {
            $this->jsonError('Failed to save promotion', 500);
        }
    }

    /**
     * Validate and store promotion data from an associative array.
     * Uses the updated schema: {attribute}_{table_name}
     */
    public function store(array $model): void 
    {
        $this->abortIfNotPriv();

        // Validation using new schema naming
        if (empty($model['label_promotion']) || 
            empty($model['academic_year_promotion']) || 
            empty($model['campus_id_promotion'])) {
            $this->jsonError('Fields "label_promotion", "academic_year_promotion" and "campus_id_promotion" are required', 422);
        }

        $promotion = PromotionModel::fromArray([
            'id_promotion'            => $model['id_promotion'] ?? null,
            'label_promotion'         => $model['label_promotion'],
            'academic_year_promotion' => $model['academic_year_promotion'],
            'campus_id_promotion'     => (int) $model['campus_id_promotion'],
        ]);

        $success = $this->promotionRepository->save($promotion);

        if (!$success) {
            $this->jsonError('Failed to store promotion', 500);
        }
    }

    /**
     * Retrieve a single promotion by its ID.
     */
    public function getById(int $id): PromotionModel 
    {
        $this->abortIfNotPriv();

        $promotion = $this->promotionRepository->getById($id);

        if ($promotion === null) {
            $this->abort(404, "Promotion not found.");
        }

        return $promotion;
    }

    /**
     * Delete a promotion. Restricted to Admin role.
     */
    public function deleteById(int $id): void 
    {
        $this->checkRole([RoleEnum::Admin]);

        $promotion = $this->promotionRepository->getById($id);

        if ($promotion === null) {
            $this->abort(404, "Promotion not found.");
        }

        $this->promotionRepository->deleteById($id);
    }

    public function getAllAjax(int|string $campusId): void 
    {
        $this->abortIfNotPriv();

        $promotions = $this->promotionRepository->getByCampus($campusId);

        header('Content-Type: application/json');
        echo json_encode($promotions);
    }

    /**
     * Render the promotions index page.
     */
    public function renderIndex(): void
    {
        $this->abortIfNotPriv();

        // Fetch all campuses for the campus selector
        $campusCtl = new CampusController($this->twig, $this->campusRepository);
        $allCampuses = $campusCtl->findAllAsAjax(); // Returns array of campuses with id & label

        echo $this->twig->render('promotions/index.html.twig', [
            'all_campuses' => $allCampuses,
            'sidebar_active' => 'promotions',
        ]);
    }
}
