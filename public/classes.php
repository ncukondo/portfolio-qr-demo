<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\ClassModel;
use App\Auth\Auth;

$user = Auth::user();

try {
    $classModel = new ClassModel();
    $classes = $classModel->findAll();
} catch (Exception $e) {
    echo "<h1>エラーが発生しました</h1>";
    echo "<p>エラー内容: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . $e->getFile() . " (行: " . $e->getLine() . ")</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>クラス一覧 - 電子ポートフォリオシステム</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>クラス一覧</h1>
            <nav>
                <a href="index.php">ホーム</a>
                <a href="classes.php" class="active">クラス一覧</a>
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
            <?php if (empty($classes)): ?>
                <div class="no-classes">
                    <p>登録されているクラスはありません。</p>
                </div>
            <?php else: ?>
                <div class="classes-grid">
                    <?php foreach ($classes as $class): ?>
                        <div class="class-card">
                            <div class="class-header">
                                <h2 class="class-name"><?= htmlspecialchars($class['class_name']) ?></h2>
                                <span class="organizer"><?= htmlspecialchars($class['organizer']) ?></span>
                            </div>
                            
                            <div class="class-details">
                                <p class="description"><?= nl2br(htmlspecialchars($class['description'])) ?></p>
                                
                                <div class="meta-info">
                                    <div class="datetime">
                                        <strong>開催日時:</strong>
                                        <?= date('Y年m月d日 H:i', strtotime($class['event_datetime'])) ?>
                                    </div>
                                    
                                    <div class="duration">
                                        <strong>時間:</strong>
                                        <?= $class['duration_minutes'] ?>分
                                    </div>
                                    
                                    <div class="credits">
                                        <strong>単位:</strong>
                                        <?php 
                                        $creditCode = $class['credit_code'];
                                        if (is_array($creditCode)) {
                                            $type = $creditCode['type'] ?? '不明';
                                            echo htmlspecialchars($type);
                                            if (!empty($creditCode['credits']) && is_array($creditCode['credits'])) {
                                                echo ' (' . implode(', ', array_map('htmlspecialchars', $creditCode['credits'])) . ')';
                                            }
                                        } else {
                                            echo '単位情報なし';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="class-actions">
                                <button class="btn-primary">詳細を見る</button>
                                <button class="btn-secondary">登録する</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>