#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Seeder;

echo "Running database seeds only...\n";

try {
    $seeder = new Seeder();
    $results = $seeder->run();
    
    foreach ($results as $result) {
        echo "Seed {$result['seed']}: {$result['status']} - {$result['message']}\n";
    }
    
    echo "✓ Seeds completed successfully!\n";
} catch (Exception $e) {
    echo "❌ Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}