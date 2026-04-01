<?php

namespace App\Models;

class SkillModel
{
    public ?int $id_skill = null;
    public ?string $label_skill = '';

    public static function fromArray(array $data): self
    {
        $skill = new self();

        $skill->id_skill = isset($data['id_skill']) && $data['id_skill'] !== ''
            ? (int) $data['id_skill']
            : (isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null);

        $skill->label_skill = $data['label_skill'] ?? ($data['label'] ?? '');

        return $skill;
    }
}
