#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Migration;

echo "Running database migrations only...\n";

try {
    $migration = new Migration();
    $migration->runAll();
    echo "✓ Migrations completed successfully!\n";
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}