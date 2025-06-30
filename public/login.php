<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\UserModel;

$error = '';
$success = '';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'メールアドレスとパスワードを入力してください。';
    } else {
        try {
            $userModel = new UserModel();
            $user = $userModel->verifyPassword($email, $password);
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_roles'] = $user['roles'];
                
                $redirectTo = $_GET['redirect'] ?? 'index.php';
                header('Location: ' . $redirectTo);
                exit;
            } else {
                $error = 'メールアドレスまたはパスワードが正しくありません。';
            }
        } catch (Exception $e) {
            $error = 'ログイン処理中にエラーが発生しました。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - Electronic Portfolio System</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 0 20px;
        }
        
        .login-card {
            background: #fff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #2c3e50;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #495057;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-login:hover {
            background: #0056b3;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .demo-users {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid #17a2b8;
        }
        
        .demo-users h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1rem;
        }
        
        .demo-users ul {
            list-style: none;
            font-size: 0.9rem;
        }
        
        .demo-users li {
            margin-bottom: 8px;
            padding: 8px;
            background: #fff;
            border-radius: 4px;
        }
        
        .demo-users strong {
            color: #495057;
        }
        
        .demo-users .role {
            display: inline-block;
            background: #17a2b8;
            color: white;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 0.8rem;
            margin-left: 10px;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #007bff;
            text-decoration: none;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>ログイン</h1>
                <p>Electronic Portfolio System</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">メールアドレス</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">パスワード</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-login">ログイン</button>
            </form>
            
            <div class="demo-users">
                <h3>🔧 テスト用アカウント</h3>
                <ul>
                    <li>
                        <strong>admin@example.com</strong> / password
                        <span class="role">administrator</span>
                    </li>
                    <li>
                        <strong>owner@example.com</strong> / password
                        <span class="role">class-owner</span>
                    </li>
                    <li>
                        <strong>learner1@example.com</strong> / password
                        <span class="role">learner</span>
                    </li>
                    <li>
                        <strong>multi@example.com</strong> / password
                        <span class="role">class-owner</span>
                        <span class="role">learner</span>
                    </li>
                </ul>
            </div>
            
            <div class="back-link">
                <a href="index.php">← トップページに戻る</a>
            </div>
        </div>
    </div>
</body>
</html>