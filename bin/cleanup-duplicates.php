#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

echo "Cleaning up duplicate data...\n";

// Railway CLI用のデータベース設定
function createRemoteConnection() {
    $publicUrl = $_ENV['DATABASE_PUBLIC_URL'] ?? null;
    
    if (!$publicUrl) {
        throw new Exception("DATABASE_PUBLIC_URL not found.");
    }
    
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
    $connection = createRemoteConnection();
    echo "✓ Database connected\n";
    
    // class_creditsテーブルをクリア
    echo "Clearing class_credits table...\n";
    $connection->exec("DELETE FROM class_credits");
    echo "✓ class_credits cleared\n";
    
    // 重複したclassesを削除（IDの大きい方を削除）
    echo "Removing duplicate classes...\n";
    
    // 重複を確認
    $stmt = $connection->query("
        SELECT class_name, COUNT(*) as count 
        FROM classes 
        GROUP BY class_name 
        HAVING COUNT(*) > 1
    ");
    
    $duplicates = $stmt->fetchAll();
    echo "Found " . count($duplicates) . " duplicate class names\n";
    
    foreach ($duplicates as $duplicate) {
        echo "Removing duplicates for: {$duplicate['class_name']}\n";
        
        // 各クラス名で最小IDを残して他を削除
        $connection->exec("
            DELETE FROM classes 
            WHERE class_name = '{$duplicate['class_name']}' 
            AND id NOT IN (
                SELECT MIN(id) 
                FROM classes 
                WHERE class_name = '{$duplicate['class_name']}'
            )
        ");
    }
    
    // 最終状態確認
    echo "\n=== Final State ===\n";
    $stmt = $connection->query("SELECT id, class_name FROM classes ORDER BY id");
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Name: {$row['class_name']}\n";
    }
    
    echo "\n✓ Cleanup completed!\n";
    
} catch (Exception $e) {
    echo "❌ Cleanup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>