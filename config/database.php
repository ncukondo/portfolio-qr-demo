<?php

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// Railway は DATABASE_URL (postgres://user:pass@host:port/db) で接続情報を渡す
$databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL') ?: null;

if ($databaseUrl && strpos($databaseUrl, '${{') === false) {
    $parsed = parse_url($databaseUrl);
    if ($parsed !== false && isset($parsed['host'])) {
        return [
            'host' => $parsed['host'],
            'dbname' => isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'portfolio_db',
            'username' => $parsed['user'] ?? 'portfolio_user',
            'password' => $parsed['pass'] ?? 'portfolio_password',
            'port' => (string)($parsed['port'] ?? 5432),
            'charset' => 'utf8',
            'options' => $options,
        ];
    }
}

// フォールバック: 個別の環境変数 (ローカル/devcontainer 用)
return [
    'host' => $_ENV['DB_HOST'] ?? 'postgres',
    'dbname' => $_ENV['DB_NAME'] ?? 'portfolio_db',
    'username' => $_ENV['DB_USER'] ?? 'portfolio_user',
    'password' => $_ENV['DB_PASSWORD'] ?? 'portfolio_password',
    'port' => $_ENV['DB_PORT'] ?? '5432',
    'charset' => 'utf8',
    'options' => $options,
];
