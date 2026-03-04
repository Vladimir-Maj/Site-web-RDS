<?php
// models/Offer.php

class Offer extends BaseModel {
    public function getLatest($limit = 10) {
        $stmt = $this->db->prepare("SELECT * FROM offers WHERE state = 'open' ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createOffer($data) {
        $sql = "INSERT INTO offers (company_id, company_name, title, position, location, description, salary_min, salary_max, job_type) 
                VALUES (:company_id, :company_name, :title, :position, :location, :description, :salary_min, :salary_max, :job_type)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
}