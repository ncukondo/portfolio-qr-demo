<?php

// Railway provides DATABASE_URL, parse it if available
if (isset($_ENV['DATABASE_URL']) && !empty($_ENV['DATABASE_URL'])) {
    $databaseUrl = parse_url($_ENV['DATABASE_URL']);
    
    // Debug: Log the parsed URL components (remove in production)
    error_log("DATABASE_URL parsed: " . print_r($databaseUrl, true));
    
    $config = [
        'host' => $databaseUrl['host'] ?? 'localhost',
        'dbname' => isset($databaseUrl['path']) ? ltrim($databaseUrl['path'], '/') : 'portfolio_db',
        'username' => $databaseUrl['user'] ?? 'portfolio_user',
        'password' => $databaseUrl['pass'] ?? 'portfolio_password',
        'port' => $databaseUrl['port'] ?? '5432',
        'charset' => 'utf8',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ];
    
    // Debug: Log the final config (remove in production)
    error_log("Final DB config: " . print_r(array_merge($config, ['password' => '***']), true));
    
} else {
    // Fallback to individual environment variables
    // Debug: Log that we're using fallback
    error_log("Using fallback database configuration (DATABASE_URL not available)");
    
    // Use localhost for local development if postgres host is not available
    $defaultHost = $_ENV['DB_HOST'] ?? 'postgres';
    if ($defaultHost === 'postgres') {
        // Check if we can resolve postgres hostname
        $resolvedHost = gethostbyname('postgres');
        if ($resolvedHost === 'postgres') {
            // gethostbyname returns the original string if it can't resolve
            $defaultHost = 'localhost';
            error_log("postgres hostname could not be resolved, using localhost");
        }
    }
    
    $config = [
        'host' => $defaultHost,
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
    
    // Debug: Log fallback config
    error_log("Fallback DB config: " . print_r(array_merge($config, ['password' => '***']), true));
}

return $config;
?>