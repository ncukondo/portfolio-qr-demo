<?php
// Railway環境変数デバッグ用スクリプト
echo "=== Environment Variables Debug ===\n";
echo "DATABASE_URL: " . ($_ENV['DATABASE_URL'] ?? 'NOT SET') . "\n";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NOT SET') . "\n";
echo "DB_NAME: " . ($_ENV['DB_NAME'] ?? 'NOT SET') . "\n";
echo "DB_USER: " . ($_ENV['DB_USER'] ?? 'NOT SET') . "\n";
echo "DB_PORT: " . ($_ENV['DB_PORT'] ?? 'NOT SET') . "\n";

if (isset($_ENV['DATABASE_URL'])) {
    $parsed = parse_url($_ENV['DATABASE_URL']);
    echo "\n=== Parsed DATABASE_URL ===\n";
    print_r($parsed);
}

// 実際の設定ファイルをテスト
try {
    $config = require __DIR__ . '/config/database.php';
    echo "\n=== Final Database Config ===\n";
    print_r(array_merge($config, ['password' => '***']));
} catch (Exception $e) {
    echo "\nConfig Error: " . $e->getMessage() . "\n";
}
?>