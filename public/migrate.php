<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Migration;

echo "<h1>データベースマイグレーション</h1>";

try {
    $migration = new Migration();
    
    // マイグレーションを実行
    echo "<p>マイグレーションを実行中...</p>";
    $results = $migration->run();
    
    echo "<h2>マイグレーション結果</h2>";
    echo "<ul>";
    
    foreach ($results as $result) {
        $statusIcon = match($result['status']) {
            'success' => '✓',
            'error' => '❌',
            'skipped' => '-',
            default => '?'
        };
        
        echo "<li>" . $statusIcon . " " . htmlspecialchars($result['migration']) . ": " . htmlspecialchars($result['message']) . "</li>";
    }
    
    echo "</ul>";
    
    // ステータス表示
    echo "<h3>マイグレーションステータス:</h3>";
    $status = $migration->status();
    echo "<ul>";
    
    foreach ($status as $item) {
        $statusIcon = $item['status'] === 'executed' ? '✓' : '○';
        echo "<li>" . $statusIcon . " " . htmlspecialchars($item['migration']) . " (" . htmlspecialchars($item['status']) . ")</li>";
    }
    
    echo "</ul>";
    
    echo "<p><a href='classes.php'>クラス一覧ページへ</a></p>";
    echo "<p><a href='seed.php'>シードデータを実行</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ エラーが発生しました</h2>";
    echo "<p>エラー内容: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . $e->getFile() . " (行: " . $e->getLine() . ")</p>";
}
?>