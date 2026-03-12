<?php
// models/BaseModel.php
declare(strict_types=1);
namespace App\Models;
abstract class BaseModel {
    protected $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }
}