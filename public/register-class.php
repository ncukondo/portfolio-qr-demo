<?php
session_start();
require_once '../vendor/autoload.php';

use App\Auth\Auth;
use App\Database\Database;

// class-ownerまたはadministratorロールが必要
Auth::requireAuth();
if (!Auth::hasRole('class-owner') && !Auth::hasRole('administrator')) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $className = trim($_POST['class_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $organizer = trim($_POST['organizer'] ?? '');
    $eventDate = $_POST['event_date'] ?? '';
    $eventTime = $_POST['event_time'] ?? '';
    $duration = (int)($_POST['duration_minutes'] ?? 0);
    $creditCodes = $_POST['credit_codes'] ?? [];

    // バリデーション
    if (empty($className)) {
        $error = 'クラス名は必須です。';
    } elseif (empty($organizer)) {
        $error = '開催団体は必須です。';
    } elseif (empty($eventDate) || empty($eventTime)) {
        $error = '開催日時は必須です。';
    } elseif ($duration <= 0) {
        $error = '時間は正の値で入力してください。';
    } else {
        try {
            $eventDatetime = $eventDate . ' ' . $eventTime;

            $db = Database::getInstance();
            
            // クラスを登録
            $query = "INSERT INTO classes (class_name, description, organizer, event_datetime, duration_minutes) 
                     VALUES (?, ?, ?, ?, ?) RETURNING id";
            
            $stmt = $db->query($query, [
                $className,
                $description,
                $organizer,
                $eventDatetime,
                $duration
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $classId = $result['id'];
            
            // 単位コードがある場合は関連テーブルに登録
            if (!empty($creditCodes)) {
                foreach ($creditCodes as $creditCode) {
                    // creditsテーブルからIDを取得
                    $creditQuery = "SELECT id FROM credits WHERE code = ?";
                    $creditStmt = $db->query($creditQuery, [$creditCode]);
                    $creditResult = $creditStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($creditResult) {
                        $creditId = $creditResult['id'];
                        
                        // class_creditsテーブルに関連を登録
                        $relationQuery = "INSERT INTO class_credits (class_id, credit_id) VALUES (?, ?)";
                        $db->query($relationQuery, [$classId, $creditId]);
                    }
                }
            }

            $success = 'クラスが正常に登録されました。';
            
            // フォームをクリア
            $className = $description = $organizer = $eventDate = $eventTime = '';
            $duration = 0;
            $creditCodes = [];
            
        } catch (Exception $e) {
            $error = 'クラス登録中にエラーが発生しました: ' . $e->getMessage();
        }
    }
}

$user = Auth::user();

// 利用可能な単位コードを取得
$availableCredits = [];
try {
    $db = Database::getInstance();
    $creditsQuery = "SELECT code, label, category FROM credits ORDER BY category, label";
    $stmt = $db->query($creditsQuery);
    $availableCredits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // エラーの場合はデフォルトの単位コードを使用
    $availableCredits = [
        ['code' => 'IT001', 'label' => 'プログラミング基礎', 'category' => 'IT・技術'],
        ['code' => 'IT002', 'label' => 'データベース設計', 'category' => 'IT・技術'],
        ['code' => 'BZ001', 'label' => 'ビジネスマナー', 'category' => 'ビジネス'],
        ['code' => 'BZ002', 'label' => 'プロジェクト管理', 'category' => 'ビジネス']
    ];
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>クラス登録 - Electronic Portfolio System</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #495057;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .credit-codes {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .credit-code-item {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        
        .credit-code-item input[type="checkbox"] {
            width: auto;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .btn-submit {
            background: #28a745;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-submit:hover {
            background: #218838;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>クラス登録</h1>
            <nav>
                <a href="index.php">ホーム</a>
                <a href="classes.php">クラス一覧</a>
                <a href="register-class.php" class="active">クラス登録</a>
                <a href="bulk-import-classes.php">CSV一括インポート</a>
                <a href="logout.php">ログアウト</a>
            </nav>
        </header>

        <div class="form-container">
            <h2>新しいクラスを登録</h2>
            <p class="mb-3">ログイン中: <strong><?= htmlspecialchars($user['name']) ?></strong></p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="class_name">クラス名 *</label>
                    <input type="text" id="class_name" name="class_name" value="<?= htmlspecialchars($className ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">説明</label>
                    <textarea id="description" name="description" placeholder="クラスの内容や学習目標を記入してください"><?= htmlspecialchars($description ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="organizer">開催団体 *</label>
                    <input type="text" id="organizer" name="organizer" value="<?= htmlspecialchars($organizer ?? '') ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="event_date">開催日 *</label>
                        <input type="date" id="event_date" name="event_date" value="<?= htmlspecialchars($eventDate ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="event_time">開催時刻 *</label>
                        <input type="time" id="event_time" name="event_time" value="<?= htmlspecialchars($eventTime ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="duration_minutes">時間（分） *</label>
                    <input type="number" id="duration_minutes" name="duration_minutes" value="<?= htmlspecialchars($duration ?? '') ?>" min="1" required>
                </div>

                <div class="form-group">
                    <label>単位コード</label>
                    <div class="credit-codes">
                        <?php foreach ($availableCredits as $credit): ?>
                            <div class="credit-code-item">
                                <input type="checkbox" 
                                       id="credit_<?= htmlspecialchars($credit['code']) ?>" 
                                       name="credit_codes[]" 
                                       value="<?= htmlspecialchars($credit['code']) ?>" 
                                       <?= in_array($credit['code'], $creditCodes ?? []) ? 'checked' : '' ?>>
                                <label for="credit_<?= htmlspecialchars($credit['code']) ?>">
                                    <?= htmlspecialchars($credit['label']) ?> 
                                    <span style="font-size: 0.8em; color: #6c757d;">(<?= htmlspecialchars($credit['category']) ?>)</span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">クラスを登録</button>
                    <a href="classes.php" class="btn-secondary">キャンセル</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>