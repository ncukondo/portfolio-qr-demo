#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Migration;
use App\Database\Seeder;

$action = $argv[1] ?? 'migrate';

echo "=== Railway Deploy Hook: $action ===\n";

try {
    switch ($action) {
        case 'migrate':
            echo "Running migrations...\n";
            $migration = new Migration();
            $migration->runAll();
            echo "✓ Migrations completed\n";
            break;
            
        case 'seed':
            echo "Running seeds...\n";
            $seeder = new Seeder();
            $seeder->runAll();
            echo "✓ Seeds completed\n";
            break;
            
        case 'migrate-seed':
            echo "Running migrations...\n";
            $migration = new Migration();
            $migration->runAll();
            echo "✓ Migrations completed\n";
            
            echo "Running seeds...\n";
            $seeder = new Seeder();
            $seeder->runAll();
            echo "✓ Seeds completed\n";
            break;
            
        default:
            echo "Unknown action: $action\n";
            echo "Available actions: migrate, seed, migrate-seed\n";
            exit(1);
    }
    
    echo "=== Deploy hook completed successfully ===\n";
    
} catch (Exception $e) {
    echo "❌ Deploy hook failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>