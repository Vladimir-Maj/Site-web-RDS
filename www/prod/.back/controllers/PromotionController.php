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
    public function index(string $campusId) : array {
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
     * $model->id is nullable
     */
    public function store(array $model) : void {
      $this->abortIfNotPriv();
     
      if (empty($model['label']) || empty($model['academic_year']) || empty($model['campus_id'])) {
       $this->jsonError('Fields "label", "academic_year" and "campus_id" are required', 422);
      }
     
      $promotion = PromotionModel::fromArray([
         'id'            => $model['id'] ?? '',
         'label'         => $model['label'],
         'academic_year' => $model['academic_year'],
         'campus_id'     => $model['campus_id'],
      ]);
     
      $success = $this->promotionRepository->save($promotion);
     
      if (!$success) {
       $this->jsonError('Failed to store promotion', 500);
      }
      
    }
    
    public function getById(string $id) : PromotionModel {
      $this->abortIfNotPriv();
     
      $promotion = $this->promotionRepository->getById($id);
     
      if ($promotion === null) {
        $this->abort(404, "Promotion not found.");
      }
     
      return $promotion;
    }
    
    public function deleteById(int $id) : void {
      $this->checkRole([RoleEnum::Admin]);
 
      $stringId = (string) $id;
 
      $promotion = $this->promotionRepository->getById($stringId);
 
      if ($promotion === null) {
        $this->abort(404, "Promotion not found.");
      }
 
      $this->promotionRepository->deleteById($stringId);
    }
}
