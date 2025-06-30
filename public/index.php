<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;
use App\Auth\Auth;

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electronic Portfolio System</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>電子ポートフォリオシステム</h1>
            <nav>
                <a href="index.php" class="active">ホーム</a>
                <a href="classes.php">クラス一覧</a>
                <?php if ($user && (in_array('class-owner', $user['roles']) || in_array('administrator', $user['roles']))): ?>
                    <a href="generate-completion-url.php">完了URL生成</a>
                <?php endif; ?>
                <?php if ($user): ?>
                    <span style="margin-left: auto; display: flex; align-items: center; gap: 15px;">
                        <span style="color: #6c757d;">
                            ようこそ、<strong><?= htmlspecialchars($user['name']) ?></strong>さん
                            <?php if (!empty($user['roles'])): ?>
                                <span style="margin-left: 10px;">
                                    <?php foreach ($user['roles'] as $role): ?>
                                        <span class="organizer" style="margin-left: 5px; font-size: 0.7rem;"><?= htmlspecialchars($role) ?></span>
                                    <?php endforeach; ?>
                                </span>
                            <?php endif; ?>
                        </span>
                        <a href="logout.php">ログアウト</a>
                    </span>
                <?php else: ?>
                    <a href="login.php">ログイン</a>
                <?php endif; ?>
            </nav>
        </header>

        <main>
            <?php
            try {
                $db = Database::getInstance();
                echo "<div style='background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
                echo "<h2>システム情報</h2>";
                echo "<p>データベース接続: <strong style='color: #28a745;'>成功</strong></p>";
                echo "<p>PHP Version: " . phpversion() . "</p>";
                echo "<p>現在時刻: " . date('Y-m-d H:i:s') . "</p>";
                
                if ($user) {
                    echo "<h3 style='margin-top: 20px;'>ユーザー情報</h3>";
                    echo "<p>ユーザーID: " . htmlspecialchars($user['id']) . "</p>";
                    echo "<p>メールアドレス: " . htmlspecialchars($user['email']) . "</p>";
                    echo "<p>ロール: " . implode(', ', $user['roles']) . "</p>";
                }
                
                echo "</div>";
            } catch (Exception $e) {
                echo "<div style='background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
                echo "<h2>システム情報</h2>";
                echo "<p>データベース接続: <strong style='color: #dc3545;'>エラー</strong></p>";
                echo "<p>エラー内容: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p>PHP Version: " . phpversion() . "</p>";
                echo "</div>";
            }
            ?>
        </main>
    </div>
</body>
</html>