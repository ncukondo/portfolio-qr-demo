<?php
namespace App\Database;

use PDO;
use PDOException;

class Seeder
{
    private Database $database;
    private string $seedPath;

    public function __construct(string $seedPath = null)
    {
        $this->database = Database::getInstance();
        $this->seedPath = $seedPath ?? __DIR__ . '/../../database/seeds';
    }

    public function run(string $seedName = null): array
    {
        $results = [];
        
        if ($seedName) {
            $results[] = $this->runSingleSeed($seedName);
        } else {
            $results = $this->runAllSeeds();
        }
        
        return $results;
    }

    private function runAllSeeds(): array
    {
        $results = [];
        $seedFiles = $this->getSeedFiles();
        
        foreach ($seedFiles as $file) {
            $seedName = basename($file, '.sql');
            $results[] = $this->runSingleSeed($seedName);
        }
        
        return $results;
    }

    private function runSingleSeed(string $seedName): array
    {
        $filePath = $this->seedPath . '/' . $seedName . '.sql';
        
        if (!file_exists($filePath)) {
            return [
                'seed' => $seedName,
                'status' => 'error',
                'message' => 'Seed file not found: ' . $filePath
            ];
        }

        $sql = file_get_contents($filePath);
        $connection = $this->database->getConnection();
        
        try {
            $connection->beginTransaction();
            
            // Execute the seed SQL
            $connection->exec($sql);
            
            $connection->commit();
            
            return [
                'seed' => $seedName,
                'status' => 'success',
                'message' => 'Seed executed successfully'
            ];
            
        } catch (PDOException $e) {
            $connection->rollback();
            return [
                'seed' => $seedName,
                'status' => 'error',
                'message' => 'Seed failed: ' . $e->getMessage()
            ];
        }
    }

    private function getSeedFiles(): array
    {
        if (!is_dir($this->seedPath)) {
            return [];
        }
        
        $files = glob($this->seedPath . '/*.sql');
        sort($files);
        
        return $files;
    }

    public function truncate(array $tables): array
    {
        $results = [];
        $connection = $this->database->getConnection();
        
        try {
            $connection->beginTransaction();
            
            // Disable foreign key checks
            $connection->exec('SET FOREIGN_KEY_CHECKS = 0');
            
            foreach ($tables as $table) {
                try {
                    $connection->exec("TRUNCATE TABLE {$table} RESTART IDENTITY CASCADE");
                    $results[] = [
                        'table' => $table,
                        'status' => 'success',
                        'message' => 'Table truncated successfully'
                    ];
                } catch (PDOException $e) {
                    $results[] = [
                        'table' => $table,
                        'status' => 'error',
                        'message' => 'Truncate failed: ' . $e->getMessage()
                    ];
                }
            }
            
            // Re-enable foreign key checks
            $connection->exec('SET FOREIGN_KEY_CHECKS = 1');
            
            $connection->commit();
            
        } catch (PDOException $e) {
            $connection->rollback();
            $results[] = [
                'operation' => 'truncate',
                'status' => 'error',
                'message' => 'Transaction failed: ' . $e->getMessage()
            ];
        }
        
        return $results;
    }

    public function refresh(array $tables = [], string $seedName = null): array
    {
        $results = [];
        
        // If no tables specified, get all tables from seeds
        if (empty($tables)) {
            $tables = $this->getTablesFromSeeds();
        }
        
        // Truncate tables
        if (!empty($tables)) {
            $truncateResults = $this->truncate($tables);
            $results = array_merge($results, $truncateResults);
        }
        
        // Run seeds
        $seedResults = $this->run($seedName);
        $results = array_merge($results, $seedResults);
        
        return $results;
    }

    private function getTablesFromSeeds(): array
    {
        $tables = [];
        $seedFiles = $this->getSeedFiles();
        
        foreach ($seedFiles as $file) {
            $content = file_get_contents($file);
            
            // Extract table names from INSERT INTO statements
            preg_match_all('/INSERT\s+INTO\s+([a-zA-Z_][a-zA-Z0-9_]*)/i', $content, $matches);
            
            if (!empty($matches[1])) {
                $tables = array_merge($tables, $matches[1]);
            }
        }
        
        return array_unique($tables);
    }

    public function list(): array
    {
        $seedFiles = $this->getSeedFiles();
        $seeds = [];
        
        foreach ($seedFiles as $file) {
            $seedName = basename($file, '.sql');
            $seeds[] = [
                'seed' => $seedName,
                'file' => $file,
                'size' => filesize($file)
            ];
        }
        
        return $seeds;
    }
}