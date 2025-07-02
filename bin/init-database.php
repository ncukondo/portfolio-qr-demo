#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Migration;
use App\Database\Seeder;

echo "Initializing database...\n";

try {
    // Run migrations
    echo "Running migrations...\n";
    $migration = new Migration();
    $migration->runAll();
    echo "Migrations completed.\n";

    // Run seeds
    echo "Running seeds...\n";
    $seeder = new Seeder();
    $seeder->runAll();
    echo "Seeds completed.\n";

    echo "Database initialization completed successfully!\n";
} catch (Exception $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
    exit(1);
}