<?php

namespace App\Models;

/**
 * TASK-26: Permissions/Role Enum
 * Changed to string-backed enum to support tryFrom()
 */
enum RoleEnum: string
{
    case Admin   = 'admin';
    case Student = 'student';
    case Pilote  = 'pilote';
}