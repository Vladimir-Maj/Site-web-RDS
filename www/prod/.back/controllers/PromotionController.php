<?php

declare (strict_types= 1);

use App\Repository\PromotionRepository;
use App\Models\PromotionModel;
use App\Models\RoleEnum;

class PromotionController extends BaseController {
    public function __construct(
        Environment $twig,
        private PromotionRepository $promotionRepository) {
        parent::__construct($twig);
    }


    /**
     *
     * @return array of Every Promotion
     */
    public function index(int $campusId) : array {
        $this->abortIfNotPriv();

        return $this->promotionRepository->getByCampus($campusId);

    }

    /**
     *
     *
     *
     *
     */

    public function push(PromotionModel $model) : void {
        $this->abortIfNotPriv();

        $success = $this->promotionRepository->save($model);

        if (!$success) {
            $this->jsonError('Failed to save promotion', 500);
        }
    }

    /**
     * array $model a map of attributes to store.
     * $model->id_promotion is nullable
     */
    public function store(array $model) : void {
        $this->abortIfNotPriv();

        if (empty($model['label_promotion']) || empty($model['academic_year_promotion']) || empty($model['campus_id_promotion'])) {
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

    public function getById(int $id) : PromotionModel {
        $this->abortIfNotPriv();

        $promotion = $this->promotionRepository->getById($id);

        if ($promotion === null) {
            $this->abort(404, "Promotion not found.");
        }

        return $promotion;
    }

    public function deleteById(int $id) : void {
        $this->checkRole([RoleEnum::Admin]);

        $promotion = $this->promotionRepository->getById($id);

        if ($promotion === null) {
            $this->abort(404, "Promotion not found.");
        }

        $this->promotionRepository->deleteById($id);
    }
}
