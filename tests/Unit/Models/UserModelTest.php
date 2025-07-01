<?php

namespace Tests\Unit\Models;

use App\Models\UserModel;
use App\Database\Database;
use PHPUnit\Framework\TestCase;
use PDO;

class UserModelTest extends TestCase
{
    private UserModel $userModel;
    private PDO $pdo;

    protected function setUp(): void
    {
        Database::resetInstance();
        $this->userModel = new UserModel();
        $this->pdo = Database::getInstance()->getConnection();
        
        $this->createTestTables();
    }

    protected function tearDown(): void
    {
        $this->dropTestTables();
        Database::resetInstance();
    }

    private function createTestTables(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS roles (
                id SERIAL PRIMARY KEY,
                name VARCHAR(50) UNIQUE NOT NULL
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                email_verified_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS user_roles (
                user_id UUID REFERENCES users(id) ON DELETE CASCADE,
                role_id INTEGER REFERENCES roles(id) ON DELETE CASCADE,
                PRIMARY KEY (user_id, role_id)
            )
        ");

        $this->pdo->exec("INSERT INTO roles (name) VALUES ('admin'), ('user') ON CONFLICT (name) DO NOTHING");
    }

    private function dropTestTables(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS user_roles CASCADE");
        $this->pdo->exec("DROP TABLE IF EXISTS users CASCADE");
        $this->pdo->exec("DROP TABLE IF EXISTS roles CASCADE");
    }

    public function testCreateUser(): void
    {
        $userData = [
            'name' => 'Create Test User',
            'email' => 'create@example.com',
            'password' => 'password123'
        ];

        $userId = $this->userModel->create($userData);

        $this->assertIsString($userId);
        $this->assertNotEmpty($userId);

        $user = $this->userModel->findById($userId);
        $this->assertEquals('Create Test User', $user['name']);
        $this->assertEquals('create@example.com', $user['email']);
        $this->assertArrayNotHasKey('password_hash', $user);
    }

    public function testFindByEmail(): void
    {
        $userData = [
            'name' => 'Find Test User',
            'email' => 'find@example.com',
            'password' => 'password123'
        ];

        $this->userModel->create($userData);
        $user = $this->userModel->findByEmail('find@example.com');

        $this->assertNotNull($user);
        $this->assertEquals('Find Test User', $user['name']);
        $this->assertEquals('find@example.com', $user['email']);
    }

    public function testVerifyPassword(): void
    {
        $userData = [
            'name' => 'Verify Test User',
            'email' => 'verify@example.com',
            'password' => 'password123'
        ];

        $this->userModel->create($userData);

        $verifiedUser = $this->userModel->verifyPassword('verify@example.com', 'password123');
        $this->assertNotNull($verifiedUser);
        $this->assertEquals('Verify Test User', $verifiedUser['name']);

        $invalidUser = $this->userModel->verifyPassword('verify@example.com', 'wrongpassword');
        $this->assertNull($invalidUser);
    }

    public function testAssignAndRemoveRole(): void
    {
        $userData = [
            'name' => 'Role Test User',
            'email' => 'role@example.com',
            'password' => 'password123'
        ];

        $userId = $this->userModel->create($userData);

        $result = $this->userModel->assignRole($userId, 'admin');
        $this->assertTrue($result);

        $hasRole = $this->userModel->hasRole($userId, 'admin');
        $this->assertTrue($hasRole);

        $result = $this->userModel->removeRole($userId, 'admin');
        $this->assertTrue($result);

        $hasRole = $this->userModel->hasRole($userId, 'admin');
        $this->assertFalse($hasRole);
    }

    public function testUpdateUser(): void
    {
        $userData = [
            'name' => 'Update Test User',
            'email' => 'update@example.com',
            'password' => 'password123'
        ];

        $userId = $this->userModel->create($userData);

        $updateData = [
            'name' => 'Updated User',
            'email' => 'updated@example.com'
        ];

        $result = $this->userModel->update($userId, $updateData);
        $this->assertTrue($result);

        $user = $this->userModel->findById($userId);
        $this->assertEquals('Updated User', $user['name']);
        $this->assertEquals('updated@example.com', $user['email']);
    }

    public function testDeleteUser(): void
    {
        $userData = [
            'name' => 'Delete Test User',
            'email' => 'delete@example.com',
            'password' => 'password123'
        ];

        $userId = $this->userModel->create($userData);

        $result = $this->userModel->delete($userId);
        $this->assertTrue($result);

        $user = $this->userModel->findById($userId);
        $this->assertNull($user);
    }

    public function testMarkEmailAsVerified(): void
    {
        $userData = [
            'name' => 'Verify Email Test User',
            'email' => 'verifyemail@example.com',
            'password' => 'password123'
        ];

        $userId = $this->userModel->create($userData);

        $result = $this->userModel->markEmailAsVerified($userId);
        $this->assertTrue($result);

        $user = $this->userModel->findById($userId);
        $this->assertNotNull($user['email_verified_at']);
    }
}