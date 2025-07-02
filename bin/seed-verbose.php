#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

echo "Running database seeds with verbose output...\n";

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
    echo "✓ Database connected successfully\n\n";
    
    // シードファイルディレクトリ
    $seedPath = __DIR__ . '/../database/seeds';
    $seedFiles = glob($seedPath . '/*.sql');
    sort($seedFiles);
    
    foreach ($seedFiles as $file) {
        $seedName = basename($file, '.sql');
        echo "=== Processing seed: $seedName ===\n";
        
        $sql = file_get_contents($file);
        echo "SQL content preview:\n" . substr($sql, 0, 200) . "...\n\n";
        
        try {
            // トランザクション開始
            $connection->beginTransaction();
            echo "Transaction started\n";
            
            // SQL実行
            $result = $connection->exec($sql);
            echo "SQL executed, affected rows: $result\n";
            
            // 実行後の確認
            if ($seedName === '003_classes_seed') {
                $stmt = $connection->query("SELECT COUNT(*) as count FROM classes");
                $count = $stmt->fetchColumn();
                echo "Classes count after insertion: $count\n";
                
                $stmt = $connection->query("SELECT id, class_name FROM classes ORDER BY id");
                echo "Classes in table:\n";
                while ($row = $stmt->fetch()) {
                    echo "  ID: {$row['id']}, Name: {$row['class_name']}\n";
                }
            }
            
            // コミット
            $connection->commit();
            echo "✓ Seed $seedName completed successfully\n\n";
            
        } catch (PDOException $e) {
            $connection->rollback();
            echo "❌ Seed $seedName failed: " . $e->getMessage() . "\n";
            echo "Error Code: " . $e->getCode() . "\n";
            echo "SQL State: " . $e->errorInfo[0] ?? 'Unknown' . "\n\n";
        }
    }
    
    echo "=== Final State Check ===\n";
    
    // 最終状態確認
    $stmt = $connection->query("SELECT COUNT(*) FROM classes");
    echo "Total classes: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $connection->query("SELECT COUNT(*) FROM class_credits");
    echo "Total class_credits: " . $stmt->fetchColumn() . "\n";
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
?>