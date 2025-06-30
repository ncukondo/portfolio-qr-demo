<?php

namespace App\Models;

use App\Database\Database;
use PDO;
use Exception;

/**
 * UserClassCompletionModel
 * ユーザークラス受講完了管理モデル
 */
class UserClassCompletionModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Register class completion for a user
     * ユーザーのクラス受講完了を登録
     * 
     * @param string $userId UUID of the user
     * @param int $classId ID of the class
     * @param string|null $completedAt Completion datetime (optional, defaults to current time)
     * @return bool Success status
     */
    public function registerCompletion(string $userId, int $classId, ?string $completedAt = null): bool
    {
        try {
            $sql = "INSERT INTO user_class_completions (user_id, class_id, completed_at) 
                    VALUES (:user_id, :class_id, :completed_at)
                    ON CONFLICT (user_id, class_id) 
                    DO UPDATE SET 
                        completed_at = EXCLUDED.completed_at,
                        updated_at = CURRENT_TIMESTAMP";

            $params = [
                ':user_id' => $userId,
                ':class_id' => $classId,
                ':completed_at' => $completedAt ?? date('Y-m-d H:i:s')
            ];

            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error registering class completion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all completions for a specific user
     * 特定ユーザーの全受講完了記録を取得
     * 
     * @param string $userId UUID of the user
     * @return array List of completions with class details
     */
    public function getUserCompletions(string $userId): array
    {
        try {
            $sql = "SELECT 
                        ucc.id,
                        ucc.user_id,
                        ucc.class_id,
                        ucc.completed_at,
                        ucc.created_at,
                        c.class_name,
                        c.description,
                        c.organizer,
                        c.event_datetime,
                        c.duration_minutes,
                        c.credit_code
                    FROM user_class_completions ucc
                    JOIN classes c ON ucc.class_id = c.id
                    WHERE ucc.user_id = :user_id
                    ORDER BY ucc.completed_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("Error getting user completions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all users who completed a specific class
     * 特定クラスを受講完了した全ユーザーを取得
     * 
     * @param int $classId ID of the class
     * @return array List of users who completed the class
     */
    public function getClassCompletions(int $classId): array
    {
        try {
            $sql = "SELECT 
                        ucc.id,
                        ucc.user_id,
                        ucc.class_id,
                        ucc.completed_at,
                        ucc.created_at,
                        u.name as user_name,
                        u.email as user_email
                    FROM user_class_completions ucc
                    JOIN users u ON ucc.user_id = u.id
                    WHERE ucc.class_id = :class_id
                    ORDER BY ucc.completed_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':class_id' => $classId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("Error getting class completions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if user has completed a specific class
     * ユーザーが特定のクラスを受講完了しているかチェック
     * 
     * @param string $userId UUID of the user
     * @param int $classId ID of the class
     * @return bool Whether the user has completed the class
     */
    public function hasUserCompletedClass(string $userId, int $classId): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM user_class_completions 
                    WHERE user_id = :user_id AND class_id = :class_id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':class_id' => $classId
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result && $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Error checking class completion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get completion statistics for a user
     * ユーザーの受講完了統計を取得
     * 
     * @param string $userId UUID of the user
     * @return array Statistics including total classes, completed classes, completion rate
     */
    public function getUserCompletionStats(string $userId): array
    {
        try {
            $sql = "SELECT 
                        (SELECT COUNT(*) FROM classes) as total_classes,
                        (SELECT COUNT(*) FROM user_class_completions WHERE user_id = :user_id) as completed_classes,
                        CASE 
                            WHEN (SELECT COUNT(*) FROM classes) = 0 THEN 0
                            ELSE ROUND(
                                (SELECT COUNT(*) FROM user_class_completions WHERE user_id = :user_id) * 100.0 / 
                                (SELECT COUNT(*) FROM classes), 2
                            )
                        END as completion_rate";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result : ['total_classes' => 0, 'completed_classes' => 0, 'completion_rate' => 0];
        } catch (Exception $e) {
            error_log("Error getting user completion stats: " . $e->getMessage());
            return ['total_classes' => 0, 'completed_classes' => 0, 'completion_rate' => 0];
        }
    }

    /**
     * Delete a completion record
     * 受講完了記録を削除
     * 
     * @param string $userId UUID of the user
     * @param int $classId ID of the class
     * @return bool Success status
     */
    public function deleteCompletion(string $userId, int $classId): bool
    {
        try {
            $sql = "DELETE FROM user_class_completions 
                    WHERE user_id = :user_id AND class_id = :class_id";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':user_id' => $userId,
                ':class_id' => $classId
            ]);
        } catch (Exception $e) {
            error_log("Error deleting class completion: " . $e->getMessage());
            return false;
        }
    }
}