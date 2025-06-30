<?php
require_once '../vendor/autoload.php';

use App\Services\AuthService;
use App\Controllers\ClassCompletionController;
use App\Models\ClassModel;
use App\Services\QRCodeService;

$authService = new AuthService();
$completionController = new ClassCompletionController();
$classModel = new ClassModel();
$qrCodeService = new QRCodeService();

// Check if user is logged in and has appropriate role
if (!$authService->isLoggedIn()) {
    $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
    $redirectUrl = '/login';
    if ($currentUrl && $currentUrl !== '/') {
        $redirectUrl .= '?redirect=' . urlencode($currentUrl);
    }
    header('Location: ' . $redirectUrl);
    exit;
}

if (!$authService->hasRole('administrator') && !$authService->hasRole('class-owner')) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Access denied. Only administrators and class owners can generate completion URLs.';
    exit;
}

$message = '';
$messageType = '';
$generatedUrl = '';
$qrCodeDataUrl = '';
$selectedClassNames = [];
$classes = $classModel->findAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedClassIds = $_POST['class_ids'] ?? [];
    $expirationHours = (int)($_POST['expiration_hours'] ?? 24);
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!$authService->verifyCsrfToken($csrfToken)) {
        $message = 'Invalid CSRF token';
        $messageType = 'error';
    } elseif (empty($selectedClassIds)) {
        $message = 'クラスを選択してください';
        $messageType = 'error';
    } elseif ($expirationHours < 1 || $expirationHours > 8760) { // Max 1 year
        $message = '有効期限は1時間から8760時間（1年）の間で設定してください';
        $messageType = 'error';
    } else {
        try {
            $classIds = array_map('intval', $selectedClassIds);
            $baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
            
            $generatedUrl = $completionController->generateCompletionUrl($classIds, $baseUrl, $expirationHours);
            
            // Get selected class names for QR code label
            $selectedClassNames = [];
            foreach ($classes as $class) {
                if (in_array($class['id'], $classIds)) {
                    $selectedClassNames[] = $class['class_name'];
                }
            }
            
            // Generate QR code
            $qrLabel = 'クラス受講完了: ' . (count($selectedClassNames) > 2 ? 
                implode(', ', array_slice($selectedClassNames, 0, 2)) . '他' . (count($selectedClassNames) - 2) . '件' : 
                implode(', ', $selectedClassNames));
            $qrCodeDataUrl = $qrCodeService->generateQRCodeDataUrl($generatedUrl, $qrLabel);
            
            $message = 'クラス受講完了URLとQRコードを生成しました';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'URL生成中にエラーが発生しました: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$csrfToken = $authService->generateCsrfToken();
$currentUser = $authService->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>クラス受講完了URL生成 - Electronic Portfolio System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #333;
            margin: 0;
        }
        .user-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .class-selection {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
        }
        .class-item {
            margin-bottom: 10px;
        }
        .class-item label {
            font-weight: normal;
            display: flex;
            align-items: flex-start;
            cursor: pointer;
        }
        .class-item input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
            margin-top: 2px;
        }
        .class-details {
            flex: 1;
        }
        .class-name {
            font-weight: bold;
            color: #007bff;
        }
        .class-meta {
            font-size: 14px;
            color: #666;
            margin-top: 2px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            margin-right: 10px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .generated-url {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        .generated-url h3 {
            margin: 0 0 15px 0;
            color: #333;
        }
        .url-box {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            font-family: monospace;
            word-break: break-all;
            margin-bottom: 15px;
        }
        .copy-btn {
            background: #28a745;
            font-size: 14px;
            padding: 8px 16px;
        }
        .copy-btn:hover {
            background: #218838;
        }
        .select-all-btn {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .select-all-btn:hover {
            background: #138496;
        }
        .qr-section {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: flex-start;
            margin-top: 20px;
        }
        .url-section {
            flex: 1;
            min-width: 300px;
        }
        .qr-code-section {
            flex: 0 0 auto;
            text-align: center;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
        }
        .qr-code-section h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 16px;
        }
        .qr-code-image {
            display: block;
            max-width: 250px;
            height: auto;
            margin: 0 auto 10px;
            border: 1px solid #eee;
            border-radius: 4px;
        }
        .qr-actions {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .qr-actions .btn {
            margin: 0;
            font-size: 12px;
            padding: 6px 12px;
        }
        @media (max-width: 768px) {
            .qr-section {
                flex-direction: column;
            }
            .qr-code-section {
                align-self: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>クラス受講完了URL生成</h1>
        </div>

        <div class="user-info">
            <strong>ユーザー:</strong> <?= htmlspecialchars($currentUser['name']) ?> 
            <strong>役割:</strong> <?= implode(', ', $currentUser['roles']) ?>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($generatedUrl): ?>
            <div class="generated-url">
                <h3>生成されたURL・QRコード</h3>
                
                <div class="qr-section">
                    <div class="url-section">
                        <h4>URL</h4>
                        <div class="url-box" id="generatedUrl"><?= htmlspecialchars($generatedUrl) ?></div>
                        <button class="btn copy-btn" onclick="copyToClipboard()">URLをコピー</button>
                        <a href="<?= htmlspecialchars($generatedUrl) ?>" class="btn" target="_blank">URLをテスト</a>
                        
                        <?php if (!empty($selectedClassNames)): ?>
                        <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                            <strong>対象クラス:</strong><br>
                            <?php foreach ($selectedClassNames as $className): ?>
                                <span style="display: inline-block; background: #007bff; color: white; padding: 2px 8px; border-radius: 12px; margin: 2px; font-size: 12px;">
                                    <?= htmlspecialchars($className) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($qrCodeDataUrl): ?>
                    <div class="qr-code-section">
                        <h4>QRコード</h4>
                        <img src="<?= $qrCodeDataUrl ?>" alt="QR Code" class="qr-code-image" id="qrCodeImage">
                        <div class="qr-actions">
                            <button class="btn copy-btn" onclick="copyQRCodeToClipboard()">QRコードをコピー</button>
                            <button class="btn" onclick="downloadQRCode()">QRコードをダウンロード</button>
                            <button class="btn btn-secondary" onclick="printQRCode()">QRコードを印刷</button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <div class="form-group">
                <label>クラス選択</label>
                <button type="button" class="select-all-btn" onclick="toggleSelectAll()">全選択 / 全解除</button>
                <div class="class-selection">
                    <?php foreach ($classes as $class): ?>
                        <div class="class-item">
                            <label>
                                <input type="checkbox" name="class_ids[]" value="<?= $class['id'] ?>">
                                <div class="class-details">
                                    <div class="class-name"><?= htmlspecialchars($class['class_name']) ?></div>
                                    <div class="class-meta">
                                        講師: <?= htmlspecialchars($class['organizer']) ?> | 
                                        開催日: <?= htmlspecialchars($class['event_datetime']) ?> | 
                                        時間: <?= $class['duration_minutes'] ?>分
                                    </div>
                                </div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="expiration_hours">有効期限（時間）</label>
                <input type="number" id="expiration_hours" name="expiration_hours" value="24" min="1" max="8760" required>
                <small>1時間から8760時間（1年）まで設定可能</small>
            </div>

            <button type="submit" class="btn">URL生成</button>
            <a href="/" class="btn btn-secondary">戻る</a>
        </form>
    </div>

    <script>
        function copyToClipboard() {
            const urlText = document.getElementById('generatedUrl').textContent;
            navigator.clipboard.writeText(urlText).then(function() {
                alert('URLをクリップボードにコピーしました');
            }, function(err) {
                console.error('コピーに失敗しました: ', err);
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = urlText;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('URLをクリップボードにコピーしました');
            });
        }

        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('input[name="class_ids[]"]');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(cb => {
                cb.checked = !allChecked;
            });
        }

        function copyQRCodeToClipboard() {
            const qrImage = document.getElementById('qrCodeImage');
            if (!qrImage) {
                alert('QRコードが見つかりません');
                return;
            }

            // Create canvas to convert image to blob
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = qrImage.naturalWidth;
            canvas.height = qrImage.naturalHeight;
            ctx.drawImage(qrImage, 0, 0);

            canvas.toBlob(function(blob) {
                const item = new ClipboardItem({ 'image/png': blob });
                navigator.clipboard.write([item]).then(function() {
                    alert('QRコードをクリップボードにコピーしました');
                }).catch(function(err) {
                    console.error('QRコードのコピーに失敗しました: ', err);
                    alert('QRコードのコピーに失敗しました');
                });
            });
        }

        function downloadQRCode() {
            const qrImage = document.getElementById('qrCodeImage');
            if (!qrImage) {
                alert('QRコードが見つかりません');
                return;
            }

            const link = document.createElement('a');
            link.download = 'class-completion-qr-code.png';
            link.href = qrImage.src;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

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
                    <title>QRコード印刷</title>
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
                    <h1>クラス受講完了QRコード</h1>
                    <img src="${qrImage.src}" alt="QR Code">
                    <div class="info">
                        <p>スマートフォンでQRコードを読み取ってアクセスしてください</p>
                        <p>生成日時: ${new Date().toLocaleString('ja-JP')}</p>
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