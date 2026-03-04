<?php
// src/Helpers/EnumProvider.php

class EnumProvider {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getJobTypes() {
        return ['full-time', 'part-time', 'contract', 'internship', 'apprenticeship', 'remote', 'hybrid'];
    }

    public function getLocations() {
        return $this->pdo->query("SELECT DISTINCT location FROM offers ORDER BY location")->fetchAll(PDO::FETCH_COLUMN);
    }
}