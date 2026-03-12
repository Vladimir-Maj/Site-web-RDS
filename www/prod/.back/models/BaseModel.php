<?php
// models/BaseModel.php
declare(strict_types=1);
abstract class BaseModel {
    protected $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }
}