<?php

// Railway provides DATABASE_URL, parse it if available
if (isset($_ENV['DATABASE_URL'])) {
    $databaseUrl = parse_url($_ENV['DATABASE_URL']);
    $config = [
        'host' => $databaseUrl['host'],
        'dbname' => ltrim($databaseUrl['path'], '/'),
        'username' => $databaseUrl['user'],
        'password' => $databaseUrl['pass'],
        'port' => $databaseUrl['port'] ?? '5432',
        'charset' => 'utf8',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ];
} else {
    // Fallback to individual environment variables
    $config = [
        'host' => $_ENV['DB_HOST'] ?? 'postgres',
        'dbname' => $_ENV['DB_NAME'] ?? 'portfolio_db',
        'username' => $_ENV['DB_USER'] ?? 'portfolio_user',
        'password' => $_ENV['DB_PASSWORD'] ?? 'portfolio_password',
        'port' => $_ENV['DB_PORT'] ?? '5432',
        'charset' => 'utf8',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ];
}

return $config;
?>