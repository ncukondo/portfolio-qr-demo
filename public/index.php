<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;

try {
    $db = Database::getInstance();
    echo "<h1>電子ポートフォリオシステム</h1>";
    echo "<p>データベース接続: <strong>成功</strong></p>";
    echo "<p>PHP Version: " . phpversion() . "</p>";
    echo "<p>現在時刻: " . date('Y-m-d H:i:s') . "</p>";
} catch (Exception $e) {
    echo "<h1>電子ポートフォリオシステム</h1>";
    echo "<p>データベース接続: <strong>エラー</strong></p>";
    echo "<p>エラー内容: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>PHP Version: " . phpversion() . "</p>";
}
?>