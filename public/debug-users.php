<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\UserModel;

try {
    $userModel = new UserModel();
    
    echo "<h1>ユーザーデバッグ情報</h1>";
    
    // 全ユーザーを取得
    $users = $userModel->findAll();
    echo "<h2>登録ユーザー一覧 (" . count($users) . "件)</h2>";
    
    if (empty($users)) {
        echo "<p>ユーザーが登録されていません。</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>名前</th><th>メール</th><th>ロール</th><th>作成日時</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . implode(', ', $user['roles']) . "</td>";
            echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 特定のユーザーでログインテスト
    echo "<h2>ログインテスト</h2>";
    $testEmail = 'owner@example.com';
    $testPassword = 'password';
    
    echo "<p>テスト対象: {$testEmail} / {$testPassword}</p>";
    
    // メールでユーザー検索
    $user = $userModel->findByEmail($testEmail);
    if ($user) {
        echo "<p>✓ ユーザーが見つかりました</p>";
        echo "<p>ユーザー名: " . htmlspecialchars($user['name']) . "</p>";
        echo "<p>ロール: " . implode(', ', $user['roles']) . "</p>";
        echo "<p>パスワードハッシュ: " . substr($user['password_hash'], 0, 20) . "...</p>";
        
        // パスワード検証
        if (password_verify($testPassword, $user['password_hash'])) {
            echo "<p style='color: green;'>✓ パスワード検証成功</p>";
        } else {
            echo "<p style='color: red;'>✗ パスワード検証失敗</p>";
            
            // 新しいハッシュを生成してみる
            $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
            echo "<p>新しいハッシュ例: " . $newHash . "</p>";
            echo "<p>新しいハッシュで検証: " . (password_verify($testPassword, $newHash) ? '成功' : '失敗') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ ユーザーが見つかりません</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<p><a href="login.php">← ログイン画面に戻る</a></p>