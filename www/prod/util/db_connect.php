<?php
// prod/db_connect.php

$host = 'db';
$db   = 'sql_db';
$user = 'user';
$pass = 'password';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // If the DB is down, we stop everything here
    die("Database Connection Error: " . $e->getMessage());
}