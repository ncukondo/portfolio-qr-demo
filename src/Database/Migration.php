<?php
namespace App\Database;

use PDO;
use PDOException;
use Exception;

class Migration
{
    private Database $database;
    private string $migrationPath;
    private string $migrationTable = 'migrations';

    public function __construct(string $migrationPath = null)
    {
        $this->database = Database::getInstance();
        $this->migrationPath = $migrationPath ?? __DIR__ . '/../../database/migrations';
        $this->createMigrationsTable();
    }

    private function createMigrationsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS {$this->migrationTable} (
                id SERIAL PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        
        $this->database->getConnection()->exec($sql);
    }

    public function run(string $migrationName = null): array
    {
        $results = [];
        
        if ($migrationName) {
            $results[] = $this->runSingleMigration($migrationName);
        } else {
            $results = $this->runAllMigrations();
        }
        
        return $results;
    }

    private function runAllMigrations(): array
    {
        $results = [];
        $migrationFiles = $this->getMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        
        foreach ($migrationFiles as $file) {
            $migrationName = basename($file, '.sql');
            
            if (!in_array($migrationName, $executedMigrations)) {
                $results[] = $this->runSingleMigration($migrationName);
            } else {
                $results[] = [
                    'migration' => $migrationName,
                    'status' => 'skipped',
                    'message' => 'Already executed'
                ];
            }
        }
        
        return $results;
    }

    private function runSingleMigration(string $migrationName): array
    {
        $filePath = $this->migrationPath . '/' . $migrationName . '.sql';
        
        if (!file_exists($filePath)) {
            return [
                'migration' => $migrationName,
                'status' => 'error',
                'message' => 'Migration file not found: ' . $filePath
            ];
        }

        $sql = file_get_contents($filePath);
        $connection = $this->database->getConnection();
        
        try {
            $connection->beginTransaction();
            
            // Execute the migration SQL
            $connection->exec($sql);
            
            // Mark as executed
            $this->markAsExecuted($migrationName);
            
            $connection->commit();
            
            return [
                'migration' => $migrationName,
                'status' => 'success',
                'message' => 'Migration executed successfully'
            ];
            
        } catch (PDOException $e) {
            $connection->rollback();
            return [
                'migration' => $migrationName,
                'status' => 'error',
                'message' => 'Migration failed: ' . $e->getMessage()
            ];
        }
    }

    private function markAsExecuted(string $migrationName): void
    {
        $sql = "INSERT INTO {$this->migrationTable} (migration) VALUES (?)";
        $this->database->query($sql, [$migrationName]);
    }

    private function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationPath)) {
            return [];
        }
        
        $files = glob($this->migrationPath . '/*.sql');
        sort($files);
        
        return $files;
    }

    private function getExecutedMigrations(): array
    {
        $sql = "SELECT migration FROM {$this->migrationTable} ORDER BY executed_at";
        $stmt = $this->database->query($sql);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function rollback(string $migrationName = null): array
    {
        if ($migrationName) {
            return $this->rollbackSingle($migrationName);
        } else {
            return $this->rollbackLast();
        }
    }

    private function rollbackSingle(string $migrationName): array
    {
        $rollbackPath = $this->migrationPath . '/rollback/' . $migrationName . '.sql';
        
        if (!file_exists($rollbackPath)) {
            return [
                'migration' => $migrationName,
                'status' => 'error',
                'message' => 'Rollback file not found: ' . $rollbackPath
            ];
        }

        $sql = file_get_contents($rollbackPath);
        $connection = $this->database->getConnection();
        
        try {
            $connection->beginTransaction();
            
            $connection->exec($sql);
            
            // Remove from executed migrations
            $deleteSql = "DELETE FROM {$this->migrationTable} WHERE migration = ?";
            $this->database->query($deleteSql, [$migrationName]);
            
            $connection->commit();
            
            return [
                'migration' => $migrationName,
                'status' => 'success',
                'message' => 'Migration rolled back successfully'
            ];
            
        } catch (PDOException $e) {
            $connection->rollback();
            return [
                'migration' => $migrationName,
                'status' => 'error',
                'message' => 'Rollback failed: ' . $e->getMessage()
            ];
        }
    }

    private function rollbackLast(): array
    {
        $sql = "SELECT migration FROM {$this->migrationTable} ORDER BY executed_at DESC LIMIT 1";
        $stmt = $this->database->query($sql);
        $lastMigration = $stmt->fetchColumn();
        
        if (!$lastMigration) {
            return [
                'migration' => null,
                'status' => 'info',
                'message' => 'No migrations to rollback'
            ];
        }
        
        return $this->rollbackSingle($lastMigration);
    }

    public function status(): array
    {
        $migrationFiles = $this->getMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        
        $status = [];
        
        foreach ($migrationFiles as $file) {
            $migrationName = basename($file, '.sql');
            $status[] = [
                'migration' => $migrationName,
                'status' => in_array($migrationName, $executedMigrations) ? 'executed' : 'pending',
                'file' => $file
            ];
        }
        
        return $status;
    }

    public function markExecuted(string $migrationName): array
    {
        $executedMigrations = $this->getExecutedMigrations();
        
        if (in_array($migrationName, $executedMigrations)) {
            return [
                'migration' => $migrationName,
                'status' => 'info',
                'message' => 'Migration already marked as executed'
            ];
        }
        
        try {
            $this->markAsExecuted($migrationName);
            
            return [
                'migration' => $migrationName,
                'status' => 'success',
                'message' => 'Migration marked as executed'
            ];
            
        } catch (PDOException $e) {
            return [
                'migration' => $migrationName,
                'status' => 'error',
                'message' => 'Failed to mark migration as executed: ' . $e->getMessage()
            ];
        }
    }
}