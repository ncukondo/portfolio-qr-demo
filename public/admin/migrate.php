<?php
// セキュリティ: 特定のIPまたはトークンでのみアクセス可能にする
$allowedToken = $_ENV['ADMIN_TOKEN'] ?? 'your-secret-admin-token';
$providedToken = $_GET['token'] ?? '';

if ($providedToken !== $allowedToken) {
    http_response_code(403);
    die('Access denied');
}

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Database\Migration;
use App\Database\Seeder;

header('Content-Type: text/plain');

echo "=== Database Migration and Seeding ===\n\n";

try {
    // Run migrations
    echo "Starting migrations...\n";
    $migration = new Migration();
    $migration->runAll();
    echo "✓ Migrations completed successfully\n\n";

    // Ask if seeds should be run
    $runSeeds = $_GET['seeds'] ?? 'yes';
    if ($runSeeds === 'yes') {
        echo "Starting seeds...\n";
        $seeder = new Seeder();
        $seeder->runAll();
        echo "✓ Seeds completed successfully\n\n";
    } else {
        echo "⚠ Seeds skipped (add &seeds=yes to run seeds)\n\n";
    }

    echo "=== Database initialization completed! ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    http_response_code(500);
}
?>