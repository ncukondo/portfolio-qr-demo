<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\ClassCompletionTokenService;
use App\Models\UserClassCompletionModel;
use App\Models\ClassModel;

/**
 * ClassCompletionController
 * クラス受講完了URL処理コントローラー
 */
class ClassCompletionController
{
    private AuthService $authService;
    private ClassCompletionTokenService $tokenService;
    private UserClassCompletionModel $completionModel;
    private ClassModel $classModel;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->tokenService = new ClassCompletionTokenService();
        $this->completionModel = new UserClassCompletionModel();
        $this->classModel = new ClassModel();
    }

    /**
     * Handle class completion URL access
     * クラス受講完了URL アクセス処理
     */
    public function handleCompletionUrl(): void
    {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            $this->showError('Invalid completion URL - missing token');
            return;
        }

        // Decode and validate token
        $tokenInfo = $this->tokenService->getTokenInfo($token);
        
        if (!$tokenInfo['valid']) {
            $this->showError('Invalid or malformed completion token');
            return;
        }

        if ($tokenInfo['is_expired']) {
            $this->showError('Completion token has expired');
            return;
        }

        // Check if user is logged in
        if (!$this->authService->isLoggedIn()) {
            // Store the current URL for redirect after login
            $currentUrl = $_SERVER['REQUEST_URI'];
            $this->authService->requireLogin($currentUrl);
            return;
        }

        // Check if user is a learner
        if (!$this->authService->isLearner()) {
            $this->showError('Only learners can complete classes');
            return;
        }

        // Get classes to complete
        $classIds = $tokenInfo['class_ids'];
        $classes = $this->getValidClasses($classIds);

        if (empty($classes)) {
            $this->showError('No valid classes found for completion');
            return;
        }

        // Process completion
        $this->processClassCompletions($classes);
    }

    /**
     * Process class completions for the current user
     * 現在のユーザーのクラス受講完了を処理
     * 
     * @param array $classes Array of class data
     */
    private function processClassCompletions(array $classes): void
    {
        $userId = $this->authService->getUserId();
        $successCount = 0;
        $alreadyCompleted = [];
        $newCompletions = [];
        $errors = [];

        foreach ($classes as $class) {
            $classId = $class['id'];
            
            // Check if already completed
            if ($this->completionModel->hasUserCompletedClass($userId, $classId)) {
                $alreadyCompleted[] = $class;
                continue;
            }

            // Register completion
            if ($this->completionModel->registerCompletion($userId, $classId)) {
                $newCompletions[] = $class;
                $successCount++;
            } else {
                $errors[] = "Failed to register completion for: " . $class['class_name'];
            }
        }

        // Show results
        $this->showCompletionResults($newCompletions, $alreadyCompleted, $errors);
    }

    /**
     * Get valid classes from class IDs
     * クラスIDから有効なクラスを取得
     * 
     * @param array $classIds Array of class IDs
     * @return array Array of valid class data
     */
    private function getValidClasses(array $classIds): array
    {
        $validClasses = [];
        
        foreach ($classIds as $classId) {
            $class = $this->classModel->findById($classId);
            if ($class) {
                $validClasses[] = $class;
            }
        }

        return $validClasses;
    }

    /**
     * Show completion results page
     * 受講完了結果ページを表示
     * 
     * @param array $newCompletions New completions registered
     * @param array $alreadyCompleted Already completed classes
     * @param array $errors Error messages
     */
    private function showCompletionResults(array $newCompletions, array $alreadyCompleted, array $errors): void
    {
        $user = $this->authService->getCurrentUser();
        
        ?>
        <!DOCTYPE html>
        <html lang="ja">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>クラス受講完了 - Electronic Portfolio System</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
                .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .success { background-color: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0; }
                .info { background-color: #d1ecf1; color: #0c5460; padding: 10px; border: 1px solid #bee5eb; border-radius: 4px; margin: 10px 0; }
                .error { background-color: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0; }
                .class-list { list-style: none; padding: 0; }
                .class-item { background: #f8f9fa; padding: 10px; margin: 5px 0; border-left: 4px solid #007bff; }
                .user-info { background: #e9ecef; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
                .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px 0 0; }
                .btn:hover { background: #0056b3; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>クラス受講完了結果</h1>
                
                <div class="user-info">
                    <strong>ユーザー:</strong> <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                </div>

                <?php if (!empty($newCompletions)): ?>
                    <div class="success">
                        <h3>✓ 新規受講完了登録 (<?= count($newCompletions) ?>件)</h3>
                        <ul class="class-list">
                            <?php foreach ($newCompletions as $class): ?>
                                <li class="class-item">
                                    <strong><?= htmlspecialchars($class['class_name']) ?></strong><br>
                                    <small>講師: <?= htmlspecialchars($class['organizer']) ?> | 開催日: <?= htmlspecialchars($class['event_datetime']) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($alreadyCompleted)): ?>
                    <div class="info">
                        <h3>ℹ 既に受講完了済み (<?= count($alreadyCompleted) ?>件)</h3>
                        <ul class="class-list">
                            <?php foreach ($alreadyCompleted as $class): ?>
                                <li class="class-item">
                                    <strong><?= htmlspecialchars($class['class_name']) ?></strong><br>
                                    <small>講師: <?= htmlspecialchars($class['organizer']) ?> | 開催日: <?= htmlspecialchars($class['event_datetime']) ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="error">
                        <h3>⚠ エラー</h3>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 30px;">
                    <a href="/dashboard" class="btn">ダッシュボードに戻る</a>
                    <a href="/my-completions" class="btn">受講履歴を見る</a>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Show error page
     * エラーページを表示
     * 
     * @param string $message Error message
     */
    private function showError(string $message): void
    {
        http_response_code(400);
        ?>
        <!DOCTYPE html>
        <html lang="ja">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>エラー - Electronic Portfolio System</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .error { background-color: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px 0; }
                .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px 0 0; }
                .btn:hover { background: #0056b3; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>エラー</h1>
                <div class="error">
                    <?= htmlspecialchars($message) ?>
                </div>
                <a href="/" class="btn">ホームに戻る</a>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Generate completion URLs for admin/class-owner use
     * 管理者・クラス主催者用の受講完了URL生成
     * 
     * @param array $classIds Array of class IDs
     * @param string $baseUrl Base URL
     * @param int|null $expirationHours Token expiration hours
     * @return string Generated URL
     */
    public function generateCompletionUrl(array $classIds, string $baseUrl = '', ?int $expirationHours = null): string
    {
        if (empty($baseUrl)) {
            $baseUrl = $this->getBaseUrl();
        }
        
        return $this->tokenService->generateCompletionUrl($classIds, $baseUrl, $expirationHours);
    }

    /**
     * Get base URL of the application
     * アプリケーションのベースURLを取得
     * 
     * @return string Base URL
     */
    private function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        return $protocol . $host;
    }
}