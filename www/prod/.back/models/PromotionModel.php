<?php
declare(strict_types=1);

namespace App\Models;

// .back/models/PromotionModel.php
class PromotionModel extends BaseModel
{
    public ?int $id_promotion = null;
    public string $label_promotion = '';
    public ?string $academic_year_promotion = null;
    public int $campus_id_promotion = 0;

    public static function fromArray(array $data): self
    {
        $inst = new self(null);

        $inst->id_promotion = isset($data['id_promotion']) && $data['id_promotion'] !== ''
            ? (int) $data['id_promotion']
            : (isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null);

        $inst->label_promotion = $data['label_promotion'] ?? ($data['label'] ?? '');
        $inst->academic_year_promotion = $data['academic_year_promotion'] ?? ($data['academic_year'] ?? null);
        $inst->campus_id_promotion = isset($data['campus_id_promotion'])
            ? (int) $data['campus_id_promotion']
            : (int) ($data['campus_id'] ?? 0);

        return $inst;
    }
}
