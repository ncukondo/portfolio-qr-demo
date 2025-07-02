#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

echo "=== Seed Debug Tool ===\n";

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
    echo "✓ Database connected\n\n";
    
    // creditsテーブルの確認
    echo "=== Credits Table ===\n";
    $stmt = $connection->query("SELECT id, code, label FROM credits ORDER BY id");
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Code: {$row['code']}, Label: {$row['label']}\n";
    }
    
    // classesテーブルの確認
    echo "\n=== Classes Table ===\n";
    $stmt = $connection->query("SELECT id, class_name, credit_code FROM classes ORDER BY id");
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Name: {$row['class_name']}, Credit Code: {$row['credit_code']}\n";
    }
    
    // class_creditsテーブルの確認
    echo "\n=== Class Credits Table ===\n";
    $stmt = $connection->query("SELECT * FROM class_credits ORDER BY class_id, credit_id");
    while ($row = $stmt->fetch()) {
        echo "Class ID: {$row['class_id']}, Credit ID: {$row['credit_id']}, Amount: {$row['credit_amount']}\n";
    }
    
    // テーブル構造確認
    echo "\n=== Classes Table Structure ===\n";
    $stmt = $connection->query("
        SELECT column_name, data_type, is_nullable, column_default 
        FROM information_schema.columns 
        WHERE table_name = 'classes' 
        ORDER BY ordinal_position
    ");
    while ($row = $stmt->fetch()) {
        echo "Column: {$row['column_name']}, Type: {$row['data_type']}, Nullable: {$row['is_nullable']}, Default: {$row['column_default']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>