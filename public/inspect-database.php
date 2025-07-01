<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;
use App\Database\Migration;

echo "<h1>データベース詳細検査</h1>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "<p>✓ データベース接続成功</p>";
    
    // 1. Migration status check
    echo "<h2>1. マイグレーション状況</h2>";
    $migration = new Migration();
    $status = $migration->status();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Migration</th><th>Status</th><th>File Path</th></tr>";
    foreach ($status as $item) {
        $statusIcon = $item['status'] === 'executed' ? '✓' : '❌';
        echo "<tr>";
        echo "<td>" . $statusIcon . " " . htmlspecialchars($item['migration']) . "</td>";
        echo "<td>" . htmlspecialchars($item['status']) . "</td>";
        echo "<td>" . htmlspecialchars($item['file']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Check executed migrations from database
    echo "<h2>2. 実行済みマイグレーション記録</h2>";
    $stmt = $conn->query("SELECT migration, executed_at FROM migrations ORDER BY executed_at");
    $executedMigrations = $stmt->fetchAll();
    
    if ($executedMigrations) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Migration</th><th>Executed At</th></tr>";
        foreach ($executedMigrations as $migration) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($migration['migration']) . "</td>";
            echo "<td>" . htmlspecialchars($migration['executed_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ 実行済みマイグレーションが見つかりません</p>";
    }
    
    // 3. Check classes table structure in detail
    echo "<h2>3. classesテーブル詳細構造</h2>";
    $stmt = $conn->query("
        SELECT 
            column_name, 
            data_type, 
            is_nullable, 
            column_default,
            character_maximum_length,
            numeric_precision,
            numeric_scale
        FROM information_schema.columns 
        WHERE table_name = 'classes' 
        ORDER BY ordinal_position
    ");
    $columns = $stmt->fetchAll();
    
    if ($columns) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Nullable</th><th>Default</th><th>Length/Precision</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($column['column_name']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($column['data_type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['is_nullable']) . "</td>";
            echo "<td>" . htmlspecialchars($column['column_default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($column['character_maximum_length'] ?? $column['numeric_precision'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ classesテーブルが存在しません</p>";
    }
    
    // 4. Check for credit_code column specifically
    echo "<h2>4. credit_code列の存在確認</h2>";
    $stmt = $conn->query("
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns 
        WHERE table_name = 'classes' AND column_name = 'credit_code'
    ");
    $creditCodeColumn = $stmt->fetch();
    
    if ($creditCodeColumn) {
        echo "<p>❌ <strong>credit_code列が存在します！</strong></p>";
        echo "<ul>";
        echo "<li>Type: " . htmlspecialchars($creditCodeColumn['data_type']) . "</li>";
        echo "<li>Nullable: " . htmlspecialchars($creditCodeColumn['is_nullable']) . "</li>";
        echo "<li>Default: " . htmlspecialchars($creditCodeColumn['column_default'] ?? 'NULL') . "</li>";
        echo "</ul>";
        echo "<p><strong>⚠️ Migration 006 has not been executed or failed!</strong></p>";
    } else {
        echo "<p>✓ credit_code列は存在しません（正常）</p>";
    }
    
    // 5. Check table constraints
    echo "<h2>5. テーブル制約</h2>";
    $stmt = $conn->query("
        SELECT 
            tc.constraint_name,
            tc.constraint_type,
            kcu.column_name
        FROM information_schema.table_constraints tc
        JOIN information_schema.key_column_usage kcu 
            ON tc.constraint_name = kcu.constraint_name
        WHERE tc.table_name = 'classes'
        ORDER BY tc.constraint_type, tc.constraint_name
    ");
    $constraints = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Constraint Name</th><th>Type</th><th>Column</th></tr>";
    foreach ($constraints as $constraint) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($constraint['constraint_name']) . "</td>";
        echo "<td>" . htmlspecialchars($constraint['constraint_type']) . "</td>";
        echo "<td>" . htmlspecialchars($constraint['column_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 6. Check indexes
    echo "<h2>6. インデックス</h2>";
    $stmt = $conn->query("
        SELECT 
            indexname,
            tablename,
            indexdef
        FROM pg_indexes 
        WHERE tablename = 'classes'
        ORDER BY indexname
    ");
    $indexes = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Index Name</th><th>Definition</th></tr>";
    foreach ($indexes as $index) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($index['indexname']) . "</td>";
        echo "<td><code>" . htmlspecialchars($index['indexdef']) . "</code></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 7. Sample data from classes table
    echo "<h2>7. classesテーブルサンプルデータ（最初の5件）</h2>";
    $stmt = $conn->query("SELECT * FROM classes LIMIT 5");
    $sampleData = $stmt->fetchAll();
    
    if ($sampleData) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
        
        // Header
        $headers = array_keys($sampleData[0]);
        echo "<tr>";
        foreach ($headers as $header) {
            echo "<th>" . htmlspecialchars($header) . "</th>";
        }
        echo "</tr>";
        
        // Data rows
        foreach ($sampleData as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>テーブルにデータがありません</p>";
    }
    
    // 8. Action recommendations
    echo "<h2>8. 推奨アクション</h2>";
    if ($creditCodeColumn) {
        echo "<div style='background-color: #ffe6e6; padding: 10px; border: 1px solid #ff9999;'>";
        echo "<h3>🔧 Migration 006を実行する必要があります</h3>";
        echo "<p>以下のいずれかの方法で修正できます:</p>";
        echo "<ol>";
        echo "<li><strong>マイグレーションを直接実行:</strong> <a href='migrate.php'>migrate.php</a> にアクセス</li>";
        echo "<li><strong>手動SQL実行:</strong> データベースに直接接続して<br><code>ALTER TABLE classes DROP COLUMN IF EXISTS credit_code;</code></li>";
        echo "<li><strong>Migration 006を強制実行:</strong> 下記のリンクをクリック</li>";
        echo "</ol>";
        echo "<p><a href='?force_migration=006' style='background-color: #ff6666; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>⚠️ Migration 006を強制実行</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background-color: #e6ffe6; padding: 10px; border: 1px solid #99ff99;'>";
        echo "<h3>✅ データベース構造は正常です</h3>";
        echo "<p>credit_code列は正常に削除されています。</p>";
        echo "</div>";
    }
    
    // Handle force migration
    if (isset($_GET['force_migration']) && $_GET['force_migration'] === '006') {
        echo "<h2>9. Migration 006 強制実行</h2>";
        try {
            // Execute migration 006 directly
            $migrationSql = file_get_contents(__DIR__ . '/../database/migrations/006_modify_classes_table.sql');
            $conn->exec($migrationSql);
            
            // Mark as executed
            $migration = new Migration();
            $result = $migration->markExecuted('006_modify_classes_table');
            
            echo "<div style='background-color: #e6ffe6; padding: 10px; border: 1px solid #99ff99;'>";
            echo "<p>✅ Migration 006 を実行しました</p>";
            echo "<p>Status: " . htmlspecialchars($result['status']) . "</p>";
            echo "<p>Message: " . htmlspecialchars($result['message']) . "</p>";
            echo "<p><a href='?'>ページを再読み込みして確認</a></p>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div style='background-color: #ffe6e6; padding: 10px; border: 1px solid #ff9999;'>";
            echo "<p>❌ Migration 006 の実行に失敗しました</p>";
            echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ エラーが発生しました</h2>";
    echo "<p>エラー内容: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . $e->getFile() . " (行: " . $e->getLine() . ")</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<p><a href='test-db.php'>基本DB接続テスト</a> | <a href='migrate.php'>マイグレーション実行</a> | <a href='classes.php'>クラス一覧</a></p>";
?>