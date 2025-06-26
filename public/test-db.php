<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;

echo "<h1>データベース接続テスト</h1>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "<p>✓ データベース接続成功</p>";
    
    // テーブル存在確認
    $stmt = $conn->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'classes'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "<p>✓ classesテーブルが存在します</p>";
        
        // テーブル構造確認
        $stmt = $conn->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'classes' ORDER BY ordinal_position");
        $columns = $stmt->fetchAll();
        
        echo "<h3>テーブル構造:</h3>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>" . htmlspecialchars($column['column_name']) . " (" . htmlspecialchars($column['data_type']) . ")</li>";
        }
        echo "</ul>";
        
        // レコード数確認
        $stmt = $conn->query("SELECT COUNT(*) as count FROM classes");
        $count = $stmt->fetch();
        echo "<p>登録済みクラス数: " . $count['count'] . "件</p>";
        
    } else {
        echo "<p>❌ classesテーブルが存在しません</p>";
        echo "<p>マイグレーションを実行してください:</p>";
        echo "<pre>psql -d portfolio_db -f database/migrations/create_classes_table.sql</pre>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . $e->getFile() . " (行: " . $e->getLine() . ")</p>";
}
?>