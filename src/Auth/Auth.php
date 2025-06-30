<?php
namespace App\Auth;

class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'roles' => $_SESSION['user_roles'] ?? []
        ];
    }

    public static function hasRole(string $role): bool
    {
        if (!self::check()) {
            return false;
        }

        return in_array($role, $_SESSION['user_roles'] ?? []);
    }

    public static function requireAuth(string $redirectTo = 'login.php'): void
    {
        if (!self::check()) {
            $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
            $redirectUrl = $redirectTo;
            if ($currentUrl && $currentUrl !== '/') {
                $redirectUrl .= '?redirect=' . urlencode($currentUrl);
            }
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    public static function requireRole(string $role, string $redirectTo = 'index.php'): void
    {
        self::requireAuth();
        
        if (!self::hasRole($role)) {
            header('Location: ' . $redirectTo);
            exit;
        }
    }

    public static function logout(): void
    {
        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
    }
}