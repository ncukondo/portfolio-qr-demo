<?php

namespace App\Services;

use App\Models\UserModel;

/**
 * AuthService
 * 認証サービス
 */
class AuthService
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        
        // Start session if not already started and headers not sent
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }

    /**
     * Login user with email and password
     * メールアドレスとパスワードでログイン
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array|null User data if login successful, null otherwise
     */
    public function login(string $email, string $password): ?array
    {
        $user = $this->userModel->verifyPassword($email, $password);
        
        if ($user) {
            $this->setUserSession($user);
            return $user;
        }
        
        return null;
    }

    /**
     * Logout current user
     * 現在のユーザーをログアウト
     */
    public function logout(): void
    {
        session_destroy();
        session_start();
    }

    /**
     * Check if user is logged in
     * ユーザーがログインしているかチェック
     * 
     * @return bool True if logged in
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Get current logged in user
     * 現在ログイン中のユーザーを取得
     * 
     * @return array|null User data if logged in, null otherwise
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->userModel->findById($_SESSION['user_id']);
    }

    /**
     * Check if current user has specific role
     * 現在のユーザーが特定の役割を持っているかチェック
     * 
     * @param string $roleName Role name to check
     * @return bool True if user has the role
     */
    public function hasRole(string $roleName): bool
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return false;
        }

        return in_array($roleName, $user['roles']);
    }

    /**
     * Check if current user is a learner
     * 現在のユーザーが学習者かチェック
     * 
     * @return bool True if user is a learner
     */
    public function isLearner(): bool
    {
        return $this->hasRole('learner');
    }

    /**
     * Require login - redirect to login if not logged in
     * ログイン必須 - ログインしていない場合はログインページにリダイレクト
     * 
     * @param string|null $redirectUrl URL to redirect after login
     */
    public function requireLogin(?string $redirectUrl = null): void
    {
        if (!$this->isLoggedIn()) {
            $loginUrl = '/login';
            if ($redirectUrl) {
                $loginUrl .= '?redirect=' . urlencode($redirectUrl);
            }
            
            header('Location: ' . $loginUrl);
            exit;
        }
    }

    /**
     * Require specific role - redirect or show error if user doesn't have role
     * 特定の役割が必要 - ユーザーが役割を持っていない場合はリダイレクトまたはエラー表示
     * 
     * @param string $roleName Required role name
     * @param string $errorMessage Error message to display
     */
    public function requireRole(string $roleName, string $errorMessage = 'Access denied'): void
    {
        $this->requireLogin();
        
        if (!$this->hasRole($roleName)) {
            header('HTTP/1.0 403 Forbidden');
            echo $errorMessage;
            exit;
        }
    }

    /**
     * Set user session data
     * ユーザーセッションデータを設定
     * 
     * @param array $user User data
     */
    private function setUserSession(array $user): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_roles'] = $user['roles'];
        $_SESSION['login_time'] = time();
    }

    /**
     * Get redirect URL after login
     * ログイン後のリダイレクトURLを取得
     * 
     * @param string $defaultUrl Default URL if no redirect is set
     * @return string Redirect URL
     */
    public function getRedirectAfterLogin(string $defaultUrl = '/'): string
    {
        $redirectUrl = $_SESSION['redirect_after_login'] ?? $defaultUrl;
        unset($_SESSION['redirect_after_login']);
        return $redirectUrl;
    }

    /**
     * Generate CSRF token
     * CSRFトークンを生成
     * 
     * @return string CSRF token
     */
    public function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     * CSRFトークンを検証
     * 
     * @param string $token Token to verify
     * @return bool True if token is valid
     */
    public function verifyCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get user ID from session
     * セッションからユーザーIDを取得
     * 
     * @return string|null User ID if logged in
     */
    public function getUserId(): ?string
    {
        return $_SESSION['user_id'] ?? null;
    }
}