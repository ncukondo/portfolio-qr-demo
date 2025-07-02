#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;

echo "=== Reset Migrations ===\n";

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // migrations テーブルをクリア
    echo "Clearing migrations table...\n";
    $connection->exec("DROP TABLE IF EXISTS migrations");
    echo "✓ Migrations table cleared\n";
    
    // 失敗したテーブルを削除
    echo "Dropping failed tables...\n";
    $connection->exec("DROP TABLE IF EXISTS user_class_completions CASCADE");
    $connection->exec("DROP TABLE IF EXISTS user_roles CASCADE");
    $connection->exec("DROP TABLE IF EXISTS users CASCADE");
    echo "✓ Failed tables dropped\n";
    
    echo "=== Reset completed ===\n";
    echo "Now run: railway run php bin/migrate-only.php\n";
    
} catch (Exception $e) {
    echo "❌ Reset failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>