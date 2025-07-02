#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;
use App\Database\Migration;
use App\Database\Seeder;

echo "=== Railway Auto-Migration ===\n";

try {
    // データベース接続確認
    echo "Checking database connection...\n";
    $db = Database::getInstance();
    echo "✓ Database connected successfully\n";

    // マイグレーション実行
    echo "Running migrations...\n";
    $migration = new Migration();
    $migration->runAll();
    echo "✓ Migrations completed\n";

    // 初回デプロイの場合のみシード実行
    $runSeeds = $_ENV['AUTO_SEED'] ?? 'first_deploy_only';
    
    if ($runSeeds === 'always' || ($runSeeds === 'first_deploy_only' && !file_exists('/tmp/seeds_executed'))) {
        echo "Running seeds...\n";
        $seeder = new Seeder();
        $seeder->runAll();
        echo "✓ Seeds completed\n";
        
        // シード実行済みマークを作成
        file_put_contents('/tmp/seeds_executed', date('Y-m-d H:i:s'));
    } else {
        echo "⚠ Seeds skipped (already executed or disabled)\n";
    }

    echo "=== Migration completed successfully ===\n";
    
} catch (Exception $e) {
    echo "❌ Migration error: " . $e->getMessage() . "\n";
    echo "⚠ Continuing with server startup...\n";
}

// PHPサーバー起動
$port = $_ENV['PORT'] ?? '8000';
echo "Starting PHP server on port $port...\n";

$cmd = "php -S 0.0.0.0:$port -t public/ router.php";
echo "Command: $cmd\n";

// サーバー起動
passthru($cmd);
?>