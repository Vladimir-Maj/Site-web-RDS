<?php

declare(strict_types=1);

namespace App\Tests;

use PDO;
use PHPUnit\Framework\TestCase;

abstract class MySQLTestCase extends TestCase
{
    protected static ?PDO $pdo = null;

    /**
     * Initialise la connexion PDO une seule fois pour toute la classe de test.
     */
    public static function setUpBeforeClass(): void
    {
        if (self::$pdo !== null) {
            return;
        }

        $dsn      = getenv('DB_DSN')  ?: 'mysql:host=db;port=3306;dbname=sql_db;charset=utf8mb4';
        $username = getenv('DB_USER') ?: 'website-local';
        $password = getenv('DB_PASS') ?: '1234';

        self::$pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    /**
     * S'ex�cute avant chaque test individuel.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
    }

    /**
     * Vide les tables et r�initialise les compteurs d'auto-incr�ment.
     */
    protected function resetDatabase(): void
    {
        if (self::$pdo === null) {
            $this->fail('PDO connection is not initialized.');
        }

        // Ordre de suppression pour respecter l'int�grit� r�f�rentielle
        $tablesInDeleteOrder = [
            'wishlist',
            'business_review',
            'application',
            'offer_requirement',
            'student_enrollment',
            'promotion_assignment',
            'internship_offer',
            'skill',
            'company_site',
            'company',
            'business_sector',
            'promotion',
            'campus',
            'administrator',
            'student',
            'pilot',
            'user',
        ];

        // Tables dont on veut remettre l'ID � 1
        $autoIncrementTables = [
            'user',
            'campus',
            'promotion',
            'business_sector',
            'company',
            'company_site',
            'skill',
            'internship_offer',
            'application',
        ];

        // D�sactivation temporaire des cl�s �trang�res pour le nettoyage
        self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach ($tablesInDeleteOrder as $table) {
                self::$pdo->exec("DELETE FROM {$table}");
            }

            foreach ($autoIncrementTables as $table) {
                self::$pdo->exec("ALTER TABLE {$table} AUTO_INCREMENT = 1");
            }
        } finally {
            // R�activation syst�matique des cl�s �trang�res
            self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
    }
}
