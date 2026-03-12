<?php
// Global Configuration
// Fix the typo: stageflow (not stagrflow) and use https
const CDN_URL = 'https://cdn.stageflow.fr';
const APP_ENV = 'development';

require_once __DIR__ . '/../../vendor/autoload.php';

// prod/.back/util/config.php
require_once 'db_connect.php';

spl_autoload_register(function ($class_name) {
    // On définit la racine du projet (www/prod/)
    $root = realpath(__DIR__ . '/../../');
    
    // Liste des dossiers où tes classes (Models, Repos, Utils) sont rangées
    $sources = [
        $root . '/.back/models/',
        $root . '/.back/repository/',
        $root . '/.back/util/'
    ];

    foreach ($sources as $source) {
        $file = $source . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// You can add more here later (DB names, etc.)