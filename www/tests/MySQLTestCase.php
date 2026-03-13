<?php
declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use PDO;

abstract class MySQLTestCase extends TestCase {
    protected static ?PDO $pdo = null;

    protected function setUp(): void {
        if (self::$pdo === null) {
            // Adjust these to match your docker-compose env variables
            $dsn = "mysql:host=lamp-db;dbname=test_db;charset=utf8mb4";
            $username = "root";
            $password = "password";

            self::$pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        }

        $this->truncateTables();
    }

    private function truncateTables(): void {
        // Disable FK checks to clear tables in any order
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $tables = ['application', 'internship_offer', 'student', 'user', 'company_site', 'company'];
        foreach ($tables as $table) {
            self::$pdo->exec("TRUNCATE TABLE $table");
        }
        self::$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
}