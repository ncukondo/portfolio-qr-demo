#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Migration;

echo "Running database migrations only...\n";

try {
    $migration = new Migration();
    $results = $migration->run();
    
    foreach ($results as $result) {
        echo "Migration {$result['migration']}: {$result['status']} - {$result['message']}\n";
    }
    
    echo "✓ Migrations completed successfully!\n";
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}