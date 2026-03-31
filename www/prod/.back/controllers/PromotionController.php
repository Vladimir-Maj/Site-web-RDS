<?php

declare (strict_types= 1);

use App\Repository\PromotionRepository;
use App\Models\PromotionModel;
use App\Models\RoleEnum;

class PromotionController extends BaseController {



/**
 * 
 * @return array of Every Promotion
 */
public function index(string $campusId) : array {
}

/**
 * 
 * 
 * 
 * 
 */

public function push(PromotionModel $model) : void {
}

/**
 * array $model a map of attributes to store.
 * $model->id is nullable
 */
public function store(array $model) : void {
}

public function getById(string $id) : PromotionModel {
}

public function deleteById(int $id) : void {
}



}