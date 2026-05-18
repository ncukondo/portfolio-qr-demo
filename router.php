<?php
/**
 * Router for PHP built-in development server
 * This file handles URL rewriting for clean URLs without .php extension
 * 
 * Usage: php -S localhost:8001 -t public/ router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Remove leading slash
$uri = ltrim($uri, '/');

// 本番では公開すべきでないデバッグ/テスト用エンドポイントをブロック
// (ファイル自体は残すが外部からアクセス不可にする)
$blockedEndpoints = [
    'debug-users',
    'inspect-database',
    'test-db',
    'test-redirect',
    'test',
    'apache-test',
];
$uriBase = preg_replace('/\.php$/', '', $uri);
if (in_array($uriBase, $blockedEndpoints, true)) {
    http_response_code(404);
    echo "404 Not Found";
    return true;
}

// If the request is for a static file that exists, serve it directly
if ($uri !== '' && file_exists(__DIR__ . '/public/' . $uri)) {
    return false; // Let the built-in server handle static files
}

// Handle root request
if ($uri === '' || $uri === 'index') {
    include __DIR__ . '/public/index.php';
    return true;
}

// Check if the requested file (with .php extension) exists
$phpFile = __DIR__ . '/public/' . $uri . '.php';
if (file_exists($phpFile)) {
    include $phpFile;
    return true;
}

// If no matching file found, return 404
http_response_code(404);
echo "404 Not Found";
return true;