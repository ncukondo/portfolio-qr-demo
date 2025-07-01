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
$importResults = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $uploadedFile = $_FILES['csv_file'];
    
    // ファイルアップロードのバリデーション
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        $error = 'ファイルのアップロードに失敗しました。';
    } elseif ($uploadedFile['size'] > 5 * 1024 * 1024) { // 5MB制限
        $error = 'ファイルサイズが大きすぎます（最大5MB）。';
    } elseif (pathinfo($uploadedFile['name'], PATHINFO_EXTENSION) !== 'csv') {
        $error = 'CSVファイルのみアップロード可能です。';
    } else {
        try {
            $csvData = [];
            $handle = fopen($uploadedFile['tmp_name'], 'r');
            
            if ($handle !== false) {
                // BOM除去
                $bom = fread($handle, 3);
                if ($bom !== "\xEF\xBB\xBF") {
                    rewind($handle);
                }
                
                $headerRow = fgetcsv($handle);
                $expectedHeaders = ['クラス名', '説明', '開催団体', '開催日', '開催時刻', '時間（分）', '単位コード（カンマ区切り）'];
                
                // ヘッダー検証
                if (!$headerRow || count($headerRow) !== count($expectedHeaders)) {
                    throw new Exception('CSVヘッダーが正しくありません。テンプレートを使用してください。');
                }
                
                $rowNumber = 2; // ヘッダー行の次から
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) === count($expectedHeaders) && !empty(trim($row[0]))) {
                        $csvData[] = [
                            'row_number' => $rowNumber,
                            'class_name' => trim($row[0]),
                            'description' => trim($row[1]),
                            'organizer' => trim($row[2]),
                            'event_date' => trim($row[3]),
                            'event_time' => trim($row[4]),
                            'duration_minutes' => trim($row[5]),
                            'credit_codes' => trim($row[6])
                        ];
                    }
                    $rowNumber++;
                }
                
                fclose($handle);
                
                if (empty($csvData)) {
                    throw new Exception('有効なデータが見つかりませんでした。');
                }
                
                // データベースに一括登録
                $db = Database::getInstance();
                $successCount = 0;
                $errorCount = 0;
                
                foreach ($csvData as $data) {
                    try {
                        // バリデーション
                        $errors = [];
                        if (empty($data['class_name'])) {
                            $errors[] = 'クラス名が空です';
                        }
                        if (empty($data['organizer'])) {
                            $errors[] = '開催団体が空です';
                        }
                        if (empty($data['event_date']) || !strtotime($data['event_date'])) {
                            $errors[] = '開催日の形式が正しくありません';
                        }
                        if (empty($data['event_time']) || !preg_match('/^\d{1,2}:\d{2}$/', $data['event_time'])) {
                            $errors[] = '開催時刻の形式が正しくありません（HH:MM）';
                        }
                        if (!is_numeric($data['duration_minutes']) || (int)$data['duration_minutes'] <= 0) {
                            $errors[] = '時間は正の数値で入力してください';
                        }
                        
                        if (!empty($errors)) {
                            throw new Exception(implode(', ', $errors));
                        }
                        
                        // 日時の結合
                        $eventDatetime = $data['event_date'] . ' ' . $data['event_time'];
                        
                        // クラスを登録
                        $query = "INSERT INTO classes (class_name, description, organizer, event_datetime, duration_minutes) 
                                 VALUES (?, ?, ?, ?, ?) RETURNING id";
                        
                        $stmt = $db->query($query, [
                            $data['class_name'],
                            $data['description'],
                            $data['organizer'],
                            $eventDatetime,
                            (int)$data['duration_minutes']
                        ]);
                        
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $classId = $result['id'];
                        
                        // 単位コードの処理
                        if (!empty($data['credit_codes'])) {
                            $creditCodes = array_map('trim', explode(',', $data['credit_codes']));
                            
                            foreach ($creditCodes as $creditCode) {
                                if (!empty($creditCode)) {
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
                        }
                        
                        $importResults[] = [
                            'row' => $data['row_number'],
                            'status' => 'success',
                            'message' => 'クラス「' . $data['class_name'] . '」を登録しました',
                            'class_name' => $data['class_name']
                        ];
                        $successCount++;
                        
                    } catch (Exception $e) {
                        $importResults[] = [
                            'row' => $data['row_number'],
                            'status' => 'error',
                            'message' => $e->getMessage(),
                            'class_name' => $data['class_name'] ?? '不明'
                        ];
                        $errorCount++;
                    }
                }
                
                if ($successCount > 0) {
                    $success = "{$successCount}件のクラスを正常に登録しました。";
                    if ($errorCount > 0) {
                        $success .= " {$errorCount}件のエラーがありました。";
                    }
                } else {
                    $error = "すべての行でエラーが発生しました。";
                }
                
            } else {
                throw new Exception('CSVファイルを読み込めませんでした。');
            }
            
        } catch (Exception $e) {
            $error = 'インポート中にエラーが発生しました: ' . $e->getMessage();
        }
    }
}

$user = Auth::user();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV一括インポート - Electronic Portfolio System</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .import-container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .upload-section {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .upload-section.dragover {
            border-color: #007bff;
            background: #e7f3ff;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            margin-top: 15px;
        }
        
        .file-input {
            opacity: 0;
            position: absolute;
            z-index: -1;
        }
        
        .file-input-label {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .file-input-label:hover {
            background: #0056b3;
        }
        
        .file-info {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .template-download {
            background: #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .template-download h3 {
            margin-bottom: 10px;
            color: #495057;
        }
        
        .btn-download {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        
        .btn-download:hover {
            background: #218838;
            color: white;
        }
        
        .import-results {
            margin-top: 30px;
        }
        
        .result-item {
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .result-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .result-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .csv-format-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .csv-format-info h4 {
            margin-bottom: 10px;
            color: #856404;
        }
        
        .csv-format-info ul {
            margin-left: 20px;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>CSV一括インポート</h1>
            <nav>
                <a href="index.php">ホーム</a>
                <a href="classes.php">クラス一覧</a>
                <a href="register-class.php">クラス登録</a>
                <a href="bulk-import-classes.php" class="active">CSV一括インポート</a>
                <a href="logout.php">ログアウト</a>
            </nav>
        </header>

        <div class="import-container">
            <h2>クラス一括登録</h2>
            <p class="mb-3">ログイン中: <strong><?= htmlspecialchars($user['name']) ?></strong></p>

            <div class="template-download">
                <h3>1. CSVテンプレートをダウンロード</h3>
                <p>まず、CSVテンプレートをダウンロードして、必要なクラス情報を入力してください。</p>
                <a href="download-csv-template.php" class="btn-download">CSVテンプレートをダウンロード</a>
            </div>

            <div class="csv-format-info">
                <h4>CSVフォーマット情報</h4>
                <ul>
                    <li>ファイル形式: CSV（UTF-8 with BOM）</li>
                    <li>最大ファイルサイズ: 5MB</li>
                    <li>開催日形式: YYYY-MM-DD（例: 2024-12-01）</li>
                    <li>開催時刻形式: HH:MM（例: 14:30）</li>
                    <li>単位コード: IT001, IT002, BZ001, BZ002, LG001, LG002, SK001, SK002 から選択（カンマ区切り）</li>
                </ul>
            </div>

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

            <div class="upload-section" id="uploadSection">
                <h3>2. CSVファイルをアップロード</h3>
                <p>編集したCSVファイルをここにドラッグ&ドロップするか、ファイルを選択してください。</p>
                
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="file-input-wrapper">
                        <input type="file" id="csvFile" name="csv_file" accept=".csv" class="file-input" required>
                        <label for="csvFile" class="file-input-label">ファイルを選択</label>
                    </div>
                    <div class="file-info" id="fileInfo"></div>
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn-submit" id="submitBtn" disabled>CSVをインポート</button>
                    </div>
                </form>
            </div>

            <?php if (!empty($importResults)): ?>
                <div class="import-results">
                    <h3>インポート結果</h3>
                    <?php foreach ($importResults as $result): ?>
                        <div class="result-item result-<?= $result['status'] ?>">
                            <strong>行<?= $result['row'] ?>:</strong> 
                            <?= htmlspecialchars($result['message']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const uploadSection = document.getElementById('uploadSection');
        const fileInput = document.getElementById('csvFile');
        const fileInfo = document.getElementById('fileInfo');
        const submitBtn = document.getElementById('submitBtn');

        // ドラッグ&ドロップ機能
        uploadSection.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadSection.classList.add('dragover');
        });

        uploadSection.addEventListener('dragleave', () => {
            uploadSection.classList.remove('dragover');
        });

        uploadSection.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadSection.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileInfo(files[0]);
            }
        });

        // ファイル選択時の処理
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                updateFileInfo(e.target.files[0]);
            }
        });

        function updateFileInfo(file) {
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (file.size > maxSize) {
                fileInfo.innerHTML = '<span style="color: #dc3545;">ファイルサイズが大きすぎます（最大5MB）</span>';
                submitBtn.disabled = true;
            } else if (!file.name.toLowerCase().endsWith('.csv')) {
                fileInfo.innerHTML = '<span style="color: #dc3545;">CSVファイルを選択してください</span>';
                submitBtn.disabled = true;
            } else {
                const sizeKB = (file.size / 1024).toFixed(1);
                fileInfo.innerHTML = `ファイル名: ${file.name}<br>サイズ: ${sizeKB} KB`;
                submitBtn.disabled = false;
            }
        }
    </script>
</body>
</html>