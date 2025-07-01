<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Auth\Auth;
use App\Models\ClassModel;
use App\Services\QRCodeService;
use App\Services\ClassCompletionTokenService;

// Check if user is logged in and has appropriate role
if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

if (!Auth::hasRole('administrator') && !Auth::hasRole('class-owner')) {
    header('HTTP/1.0 403 Forbidden');
    echo '<h1>アクセス拒否</h1><p>この機能を使用する権限がありません。</p>';
    exit;
}

$error = '';
$qrCodeDataUrl = '';
$completionUrl = '';
$class = null;

// Get class ID from URL parameter
$classId = (int)($_GET['class_id'] ?? 0);

if ($classId <= 0) {
    $error = 'クラスIDが指定されていません。';
} else {
    try {
        // Validate class exists
        $classModel = new ClassModel();
        $class = $classModel->findById($classId);
        
        if (!$class) {
            $error = '指定されたクラスが見つかりません。';
        } else {
            // Generate completion URL
            $tokenService = new ClassCompletionTokenService();
            $baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
            $completionUrl = $tokenService->generateCompletionUrl([$classId], $baseUrl, 24); // 24 hours expiry

            // Generate QR code
            $qrCodeService = new QRCodeService();
            $qrLabel = 'クラス受講完了: ' . $class['class_name'];
            $qrCodeDataUrl = $qrCodeService->generateQRCodeDataUrl($completionUrl, $qrLabel);
        }
    } catch (Exception $e) {
        $error = 'QRコードの生成中にエラーが発生しました: ' . $e->getMessage();
    }
}

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QRコード生成 - 電子ポートフォリオシステム</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .qr-page {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .qr-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .qr-code-image {
            max-width: 400px;
            height: auto;
            margin: 20px auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            display: block;
        }
        
        .url-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
            word-break: break-all;
            font-family: monospace;
            text-align: left;
        }
        
        .qr-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .qr-actions a,
        .qr-actions button {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: inline-block;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
        }
        
        .btn-back:hover {
            background: #545b62;
            color: white;
        }
        
        .btn-copy {
            background: #007bff;
            color: white;
        }
        
        .btn-copy:hover {
            background: #0056b3;
        }
        
        .btn-download {
            background: #28a745;
            color: white;
        }
        
        .btn-download:hover {
            background: #218838;
        }
        
        .btn-print {
            background: #17a2b8;
            color: white;
        }
        
        .btn-print:hover {
            background: #138496;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .class-info {
            background: #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
        }
        
        .class-info h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .class-meta {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .qr-actions {
                flex-direction: column;
            }
            
            .qr-actions a,
            .qr-actions button {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>QRコード生成</h1>
            <nav>
                <a href="index.php">ホーム</a>
                <a href="classes.php">クラス一覧</a>
                <?php if ($user && (Auth::hasRole('class-owner') || Auth::hasRole('administrator'))): ?>
                    <a href="register-class.php">クラス登録</a>
                    <a href="bulk-import-classes.php">CSV一括インポート</a>
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

        <main class="qr-page">
            <?php if ($error): ?>
                <div class="error-message">
                    <strong>エラー:</strong> <?= htmlspecialchars($error) ?>
                </div>
                <div class="qr-actions">
                    <a href="classes.php" class="btn-back">クラス一覧に戻る</a>
                </div>
            <?php elseif ($class && $qrCodeDataUrl): ?>
                <div class="qr-container">
                    <h2>受講完了QRコード</h2>
                    
                    <div class="class-info">
                        <h3><?= htmlspecialchars($class['class_name']) ?></h3>
                        <div class="class-meta">
                            <strong>講師:</strong> <?= htmlspecialchars($class['organizer']) ?><br>
                            <strong>開催日時:</strong> <?= date('Y年m月d日 H:i', strtotime($class['event_datetime'])) ?><br>
                            <strong>時間:</strong> <?= $class['duration_minutes'] ?>分
                        </div>
                    </div>
                    
                    <img src="<?= $qrCodeDataUrl ?>" alt="QR Code" class="qr-code-image" id="qrCodeImage">
                    
                    <div class="url-info">
                        <strong>受講完了URL:</strong><br>
                        <span id="completionUrl"><?= htmlspecialchars($completionUrl) ?></span>
                    </div>
                    
                    <p style="color: #6c757d; font-size: 14px;">
                        このQRコードは24時間有効です。受講者がスマートフォンでQRコードを読み取ると、受講完了ページに移動します。
                    </p>
                    
                    <div class="qr-actions">
                        <a href="classes.php" class="btn-back">クラス一覧に戻る</a>
                        <button onclick="copyUrlToClipboard()" class="btn-copy">URLをコピー</button>
                        <button onclick="downloadQRCode()" class="btn-download">QRコードをダウンロード</button>
                        <button onclick="printQRCode()" class="btn-print">印刷</button>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // URLをクリップボードにコピー
        function copyUrlToClipboard() {
            const urlText = document.getElementById('completionUrl').textContent;
            navigator.clipboard.writeText(urlText).then(function() {
                alert('URLをクリップボードにコピーしました');
            }, function(err) {
                console.error('コピーに失敗しました: ', err);
                // フォールバック
                const textArea = document.createElement('textarea');
                textArea.value = urlText;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('URLをクリップボードにコピーしました');
            });
        }
        
        // QRコードをダウンロード
        function downloadQRCode() {
            const qrImage = document.getElementById('qrCodeImage');
            if (!qrImage) {
                alert('QRコードが見つかりません');
                return;
            }
            
            const link = document.createElement('a');
            link.download = '<?= htmlspecialchars($class['class_name'] ?? 'class') ?>-completion-qr.png';
            link.href = qrImage.src;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // QRコードを印刷
        function printQRCode() {
            const qrImage = document.getElementById('qrCodeImage');
            if (!qrImage) {
                alert('QRコードが見つかりません');
                return;
            }
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>QRコード印刷 - <?= htmlspecialchars($class['class_name'] ?? 'クラス') ?></title>
                    <style>
                        body { 
                            margin: 0; 
                            padding: 20px; 
                            text-align: center; 
                            font-family: Arial, sans-serif;
                        }
                        h1 { 
                            color: #333; 
                            margin-bottom: 20px;
                        }
                        img { 
                            max-width: 100%; 
                            height: auto; 
                        }
                        .info {
                            margin-top: 20px;
                            font-size: 14px;
                            color: #666;
                        }
                        @media print {
                            body { margin: 0; padding: 10px; }
                        }
                    </style>
                </head>
                <body>
                    <h1><?= htmlspecialchars($class['class_name'] ?? 'クラス') ?></h1>
                    <h2>受講完了QRコード</h2>
                    <img src="${qrImage.src}" alt="QR Code">
                    <div class="info">
                        <p>スマートフォンでQRコードを読み取ってアクセスしてください</p>
                        <p>生成日時: ${new Date().toLocaleString('ja-JP')}</p>
                        <p>有効期限: 24時間</p>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 250);
        }
    </script>
</body>
</html>