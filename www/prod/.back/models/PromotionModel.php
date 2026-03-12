<?php
declare(strict_types=1);
namespace App\Models;
// .back/models/PromotionModel.php
class PromotionModel extends BaseModel {
    public string $id;
    public string $label;
    public ?string $academic_year;
    public string $campus_id;

    public static function fromArray(array $data): self {
        $inst = new self(null);
        $inst->id = $data['id'] ?? '';
        $inst->label = $data['label'] ?? '';
        $inst->academic_year = $data['academic_year'] ?? null;
        $inst->campus_id = $data['campus_id'] ?? '';
        return $inst;
    }
}