<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Seeder;
use App\Database\Database;

echo "<h1>シードデータ投入</h1>";

try {
    $seeder = new Seeder();
    
    // シードデータを実行
    echo "<p>シードデータを実行中...</p>";
    $results = $seeder->run();
    
    echo "<h2>シード実行結果</h2>";
    echo "<ul>";
    
    foreach ($results as $result) {
        $statusIcon = match($result['status']) {
            'success' => '✓',
            'error' => '❌',
            'skipped' => '-',
            default => '?'
        };
        
        echo "<li>" . $statusIcon . " " . htmlspecialchars($result['seed']) . ": " . htmlspecialchars($result['message']) . "</li>";
    }
    
    echo "</ul>";
    
    // データ確認
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM classes");
    $result = $stmt->fetch();
    
    echo "<p>✓ classesテーブルに " . $result['count'] . " 件のデータが登録されました</p>";
    
    // データ表示
    $stmt = $conn->query("SELECT class_name, organizer, event_datetime FROM classes ORDER BY event_datetime");
    $classes = $stmt->fetchAll();
    
    echo "<h3>登録されたクラス:</h3>";
    echo "<ul>";
    foreach ($classes as $class) {
        echo "<li>" . htmlspecialchars($class['class_name']) . " - " . htmlspecialchars($class['organizer']) . " (" . htmlspecialchars($class['event_datetime']) . ")</li>";
    }
    echo "</ul>";
    
    // 利用可能なシード一覧
    echo "<h3>利用可能なシード:</h3>";
    $seeds = $seeder->list();
    echo "<ul>";
    foreach ($seeds as $seed) {
        echo "<li>" . htmlspecialchars($seed['seed']) . " (" . $seed['size'] . " bytes)</li>";
    }
    echo "</ul>";
    
    echo "<p><a href='classes.php'>クラス一覧ページへ</a></p>";
    echo "<p><a href='migrate.php'>マイグレーション実行</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ エラーが発生しました</h2>";
    echo "<p>エラー内容: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . $e->getFile() . " (行: " . $e->getLine() . ")</p>";
}
?>