<?php
// models/Company.php

class Company extends BaseModel {
    public function getAll() {
        return $this->db->query("SELECT * FROM companies")->fetchAll();
    }

    public function getWithOffers($companyId) {
        $sql = "SELECT c.*, o.title, o.location 
                FROM companies c 
                LEFT JOIN offers o ON c.id = o.company_id 
                WHERE c.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$companyId]);
        return $stmt->fetchAll();
    }
}