<?php
// prod/.back/util/db_connect.php

// ===== LOCAL (Docker) =====
$host = '127.0.0.1';         
$port = 3306;             
$db   = 'sql_db';      
$user = 'root';             
$pass = 'Vlad123MotDePasse!';

/*
// ===== PRODUCTION =====
$host = '165.22.90.120';
$port = 3306;
$db   = 'sql_db';
$user = 'laptop-admin';
$pass = 'change_in_prod';
*/

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => true,
    ]);

} catch (PDOException $e) {
    // In production, log instead of exposing error details
    die("Database connection failed.");
}