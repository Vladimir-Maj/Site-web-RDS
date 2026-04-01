<?php
declare(strict_types=1);

namespace App\Models;

// .back/models/ApplicationModel.php
class ApplicationModel extends BaseModel
{
    public ?int $id_application = null;
    public int $student_id_application;
    public int $offer_id_application;
    public ?string $cv_path_application = null;
    public ?string $cover_letter_path_application = null;
    public string $status_application = 'pending'; // pending, accepted, rejected
    public ?string $applied_at_application = null;

    public static function fromArray(array $data): self
    {
        $inst = new self(null);

        $inst->id_application = isset($data['id_application']) && $data['id_application'] !== ''
            ? (int) $data['id_application']
            : (isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null);

        $inst->student_id_application = isset($data['student_id_application'])
            ? (int) $data['student_id_application']
            : (int) ($data['student_id'] ?? 0);

        $inst->offer_id_application = isset($data['offer_id_application'])
            ? (int) $data['offer_id_application']
            : (int) ($data['offer_id'] ?? 0);

        $inst->cv_path_application = $data['cv_path_application'] ?? ($data['cv_path'] ?? null);
        $inst->cover_letter_path_application = $data['cover_letter_path_application'] ?? ($data['cover_letter_path'] ?? null);
        $inst->status_application = $data['status_application'] ?? ($data['status'] ?? 'pending');
        $inst->applied_at_application = $data['applied_at_application'] ?? ($data['applied_at'] ?? null);

        return $inst;
    }

    public function getStudentId(): int
    {
        return $this->student_id_application;
    }

    public function isPending(): bool
    {
        return $this->status_application === 'pending';
    }
}
