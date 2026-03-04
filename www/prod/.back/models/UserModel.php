<?php
// models/User.php

class User extends BaseModel {
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO users (username, email, password, first_name, last_name, role) 
                VALUES (:username, :email, :password, :first_name, :last_name, :role)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
}