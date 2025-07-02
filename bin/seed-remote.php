#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Seeder;
use PDO;

echo "Running database seeds via Railway CLI...\n";

// Railway CLI用のデータベース設定（PUBLIC_URLを使用）
function createRemoteConnection() {
    $publicUrl = $_ENV['DATABASE_PUBLIC_URL'] ?? null;
    
    if (!$publicUrl) {
        throw new Exception("DATABASE_PUBLIC_URL not found. Make sure you're using Railway CLI.");
    }
    
    echo "Using DATABASE_PUBLIC_URL for remote connection\n";
    $parsed = parse_url($publicUrl);
    
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $parsed['host'],
        $parsed['port'] ?? '5432',
        ltrim($parsed['path'], '/')
    );
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    return new PDO($dsn, $parsed['user'], $parsed['pass'], $options);
}

try {
    // 直接PDO接続でシード実行
    $connection = createRemoteConnection();
    echo "✓ Database connected successfully via public URL\n";
    
    // シードファイルディレクトリ
    $seedPath = __DIR__ . '/../database/seeds';
    $seedFiles = glob($seedPath . '/*.sql');
    sort($seedFiles);
    
    foreach ($seedFiles as $file) {
        $seedName = basename($file, '.sql');
        echo "Running seed: $seedName...\n";
        
        $sql = file_get_contents($file);
        
        try {
            $connection->beginTransaction();
            $connection->exec($sql);
            $connection->commit();
            echo "✓ Seed $seedName completed successfully\n";
        } catch (PDOException $e) {
            $connection->rollback();
            echo "❌ Seed $seedName failed: " . $e->getMessage() . "\n";
        }
    }
    
    echo "✓ All seeds completed!\n";
    
} catch (Exception $e) {
    echo "❌ Remote seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>