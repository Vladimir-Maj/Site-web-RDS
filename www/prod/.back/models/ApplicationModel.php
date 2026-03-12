<?php
declare (strict_types= 1);
// .back/models/ApplicationModel.php
namespace App\Models;
class ApplicationModel extends BaseModel {
    public string $id;
    public string $student_id;
    public string $offer_id;
    public ?string $cv_path;
    public ?string $cover_letter_path;
    public string $status; // pending, accepted, rejected
    public string $applied_at;

    public static function fromArray(array $data): self {
        $inst = new self(null);
        $inst->id = $data['id'] ?? '';
        $inst->student_id = $data['student_id'] ?? '';
        $inst->offer_id = $data['offer_id'] ?? '';
        $inst->cv_path = $data['cv_path'] ?? null;
        $inst->cover_letter_path = $data['cover_letter_path'] ?? null;
        $inst->status = $data['status'] ?? 'pending';
        $inst->applied_at = $data['applied_at'] ?? '';
        return $inst;
    }

    public function isPending(): bool { return $this->status === 'pending'; }
}