#!/usr/bin/env php
<?php

echo "=== Railway Environment Variables ===\n\n";

$envVars = [
    'DATABASE_URL',
    'DATABASE_PUBLIC_URL',
    'PGHOST',
    'PGPORT',
    'PGDATABASE',
    'PGUSER',
    'PGPASSWORD'
];

foreach ($envVars as $var) {
    $value = $_ENV[$var] ?? 'NOT SET';
    if (in_array($var, ['PGPASSWORD', 'DATABASE_URL', 'DATABASE_PUBLIC_URL']) && $value !== 'NOT SET') {
        // パスワード部分をマスク
        $value = preg_replace('/(:)([^@]+)(@)/', '$1***$3', $value);
    }
    echo "$var = $value\n";
}

echo "\n=== Connection Test ===\n";

// PUBLIC_URL での接続テスト
if (isset($_ENV['DATABASE_PUBLIC_URL'])) {
    $parsed = parse_url($_ENV['DATABASE_PUBLIC_URL']);
    echo "PUBLIC_URL Host: " . ($parsed['host'] ?? 'N/A') . "\n";
    echo "PUBLIC_URL Port: " . ($parsed['port'] ?? 'N/A') . "\n";
    echo "PUBLIC_URL Database: " . (isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'N/A') . "\n";
} else {
    echo "DATABASE_PUBLIC_URL not available\n";
}
?>