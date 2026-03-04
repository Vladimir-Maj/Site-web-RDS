<?php
// models/BaseModel.php

abstract class BaseModel {
    protected $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }
}