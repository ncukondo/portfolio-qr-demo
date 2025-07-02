#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Seeder;

echo "Running database seeds only...\n";

try {
    $seeder = new Seeder();
    $seeder->runAll();
    echo "✓ Seeds completed successfully!\n";
} catch (Exception $e) {
    echo "❌ Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}