<?php
// 1. Connection
$host = 'db'; $db = 'sql_db'; $user = 'user'; $pass = 'password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 2. Check for the ID in the URL
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];

        // 3. Prepare the DELETE statement (Prevents SQL Injection)
        $stmt = $pdo->prepare("DELETE FROM offers WHERE id = ?");
        $stmt->execute([$id]);
    }

} catch (Exception $e) {
    // In a real app, maybe log the error
}

// 4. Redirect back to the home page immediately
header("Location: home.php");
exit();