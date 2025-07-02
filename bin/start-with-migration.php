#!/usr/bin/env php
<?php

// PHPサーバーをバックグラウンドで先に起動
$port = $_ENV['PORT'] ?? '8000';
echo "Starting PHP server on port $port in background...\n";

$serverCmd = "php -S 0.0.0.0:$port -t public/ router.php";
$serverProcess = proc_open($serverCmd, [
    0 => ['pipe', 'r'],  // stdin
    1 => ['pipe', 'w'],  // stdout
    2 => ['pipe', 'w']   // stderr
], $pipes);

if (!is_resource($serverProcess)) {
    die("Failed to start PHP server\n");
}

// サーバー起動を少し待つ
sleep(2);
echo "✓ PHP server started\n";

// バックグラウンドでマイグレーション実行
$migrationPid = pcntl_fork();

if ($migrationPid == 0) {
    // 子プロセス: マイグレーション実行
    require_once __DIR__ . '/../vendor/autoload.php';
    
    use App\Database\Database;
    use App\Database\Migration;
    use App\Database\Seeder;
    
    echo "=== Background Migration Process ===\n";
    
    try {
        // データベース接続確認
        echo "Checking database connection...\n";
        $db = Database::getInstance();
        echo "✓ Database connected successfully\n";

        // マイグレーション実行
        echo "Running migrations...\n";
        $migration = new Migration();
        $migration->runAll();
        echo "✓ Migrations completed\n";

        // 初回デプロイの場合のみシード実行
        $runSeeds = $_ENV['AUTO_SEED'] ?? 'first_deploy_only';
        
        if ($runSeeds === 'always' || ($runSeeds === 'first_deploy_only' && !file_exists('/tmp/seeds_executed'))) {
            echo "Running seeds...\n";
            $seeder = new Seeder();
            $seeder->runAll();
            echo "✓ Seeds completed\n";
            
            // シード実行済みマークを作成
            file_put_contents('/tmp/seeds_executed', date('Y-m-d H:i:s'));
        } else {
            echo "⚠ Seeds skipped (already executed or disabled)\n";
        }

        echo "=== Migration completed successfully ===\n";
        
    } catch (Exception $e) {
        echo "❌ Migration error: " . $e->getMessage() . "\n";
    }
    
    exit(0);
} else if ($migrationPid > 0) {
    // 親プロセス: サーバーを監視
    echo "Migration running in background (PID: $migrationPid)\n";
    
    // サーバープロセスを監視
    while (proc_get_status($serverProcess)['running']) {
        sleep(1);
    }
    
    // 子プロセスが終了するまで待機
    pcntl_waitpid($migrationPid, $status);
    
} else {
    // fork失敗時は通常通り実行
    echo "Fork failed, running migration synchronously...\n";
    
    require_once __DIR__ . '/../vendor/autoload.php';
    
    use App\Database\Database;
    use App\Database\Migration;
    use App\Database\Seeder;
    
    try {
        $db = Database::getInstance();
        $migration = new Migration();
        $migration->runAll();
        echo "✓ Migrations completed\n";
        
        $runSeeds = $_ENV['AUTO_SEED'] ?? 'first_deploy_only';
        if ($runSeeds === 'always' || ($runSeeds === 'first_deploy_only' && !file_exists('/tmp/seeds_executed'))) {
            $seeder = new Seeder();
            $seeder->runAll();
            file_put_contents('/tmp/seeds_executed', date('Y-m-d H:i:s'));
        }
    } catch (Exception $e) {
        echo "❌ Migration error: " . $e->getMessage() . "\n";
    }
}

// クリーンアップ
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($serverProcess);
?>