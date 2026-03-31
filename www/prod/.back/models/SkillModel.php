<?php

namespace App\Models;

class SkillModel
{
    public ?string $id = null;
    public ?string $label = '';

    public static function fromArray(array $data): self
    {
        $skill = new self();
        foreach ($data as $key => $value) {
            if (property_exists($skill, $key)) {
                $skill->$key = $value;
            }
        }
        return $skill;
    }
}