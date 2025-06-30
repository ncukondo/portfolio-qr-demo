<?php
namespace App\Models;

use App\Database\Database;
use PDO;

class UserModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): string
    {
        $sql = "INSERT INTO users (name, email, password_hash) 
                VALUES (:name, :email, :password_hash) RETURNING id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT)
        ]);

        return $stmt->fetchColumn();
    }

    public function findById(string $id): ?array
    {
        $sql = "SELECT u.*, array_agg(r.name) as roles 
                FROM users u 
                LEFT JOIN user_roles ur ON u.id = ur.user_id 
                LEFT JOIN roles r ON ur.role_id = r.id 
                WHERE u.id = :id 
                GROUP BY u.id, u.name, u.email, u.password_hash, u.email_verified_at, u.created_at, u.updated_at";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $result['roles'] = $this->parseRolesArray($result['roles']);
            unset($result['password_hash']);
        }
        
        return $result ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT u.*, array_agg(r.name) as roles 
                FROM users u 
                LEFT JOIN user_roles ur ON u.id = ur.user_id 
                LEFT JOIN roles r ON ur.role_id = r.id 
                WHERE u.email = :email 
                GROUP BY u.id, u.name, u.email, u.password_hash, u.email_verified_at, u.created_at, u.updated_at";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $result['roles'] = $this->parseRolesArray($result['roles']);
        }
        
        return $result ?: null;
    }

    public function verifyPassword(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            return $user;
        }
        return null;
    }

    public function assignRole(string $userId, string $roleName): bool
    {
        $roleId = $this->getRoleIdByName($roleName);
        if (!$roleId) {
            return false;
        }

        $sql = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id) 
                ON CONFLICT (user_id, role_id) DO NOTHING";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':role_id' => $roleId
        ]);
    }

    public function removeRole(string $userId, string $roleName): bool
    {
        $roleId = $this->getRoleIdByName($roleName);
        if (!$roleId) {
            return false;
        }

        $sql = "DELETE FROM user_roles WHERE user_id = :user_id AND role_id = :role_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':role_id' => $roleId
        ]);
    }

    public function hasRole(string $userId, string $roleName): bool
    {
        $user = $this->findById($userId);
        return $user && in_array($roleName, $user['roles']);
    }

    public function findAll(): array
    {
        $sql = "SELECT u.id, u.name, u.email, u.email_verified_at, u.created_at, u.updated_at,
                       array_agg(r.name) as roles 
                FROM users u 
                LEFT JOIN user_roles ur ON u.id = ur.user_id 
                LEFT JOIN roles r ON ur.role_id = r.id 
                GROUP BY u.id, u.name, u.email, u.email_verified_at, u.created_at, u.updated_at
                ORDER BY u.created_at DESC";
        
        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as &$result) {
            $result['roles'] = $this->parseRolesArray($result['roles']);
        }
        
        return $results;
    }

    public function update(string $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['name'])) {
            $fields[] = 'name = :name';
            $params[':name'] = $data['name'];
        }
        
        if (isset($data['email'])) {
            $fields[] = 'email = :email';
            $params[':email'] = $data['email'];
        }
        
        if (isset($data['password'])) {
            $fields[] = 'password_hash = :password_hash';
            $params[':password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(string $id): bool
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function markEmailAsVerified(string $id): bool
    {
        $sql = "UPDATE users SET email_verified_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    private function getRoleIdByName(string $roleName): ?int
    {
        $sql = "SELECT id FROM roles WHERE name = :name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':name' => $roleName]);
        
        $result = $stmt->fetchColumn();
        return $result ?: null;
    }

    public function getAllRoles(): array
    {
        $sql = "SELECT * FROM roles ORDER BY name";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function parseRolesArray($rolesString): array
    {
        if (!$rolesString || $rolesString === '{NULL}') {
            return [];
        }
        
        if (is_array($rolesString)) {
            return array_filter($rolesString);
        }
        
        // PostgreSQLのarray_aggが返す文字列形式 '{role1,role2}' をパース
        $rolesString = trim($rolesString, '{}');
        if (empty($rolesString)) {
            return [];
        }
        
        $roles = explode(',', $rolesString);
        return array_filter(array_map('trim', $roles));
    }
}