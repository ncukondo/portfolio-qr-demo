#!/usr/bin/env php
<?php

// 即座にPHPサーバーを起動
$port = $_ENV['PORT'] ?? '8000';
echo "Starting PHP server on port $port...\n";

// マイグレーションは初回アクセス時に実行されるよう設定
// またはバックグラウンドで実行
if (function_exists('pcntl_fork')) {
    $pid = pcntl_fork();
    if ($pid == 0) {
        // 子プロセス: マイグレーション実行
        sleep(5); // サーバー起動後に実行
        
        require_once __DIR__ . '/../vendor/autoload.php';
        
        try {
            echo "Background migration starting...\n";
            $migration = new App\Database\Migration();
            $migration->runAll();
            echo "Background migration completed\n";
        } catch (Exception $e) {
            echo "Background migration failed: " . $e->getMessage() . "\n";
        }
        
        exit(0);
    }
}

// PHPサーバー起動
$cmd = "php -S 0.0.0.0:$port -t public/ router.php";
passthru($cmd);
?>