<?php
namespace App\Models;

use App\Database\Database;
use PDO;

class ClassModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO classes (class_name, description, organizer, event_datetime, duration_minutes, credit_code) 
                VALUES (:class_name, :description, :organizer, :event_datetime, :duration_minutes, :credit_code)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':class_name' => $data['class_name'],
            ':description' => $data['description'],
            ':organizer' => $data['organizer'],
            ':event_datetime' => $data['event_datetime'],
            ':duration_minutes' => $data['duration_minutes'],
            ':credit_code' => json_encode($data['credit_code'])
        ]);

        return $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT c.*,
                       COALESCE(json_agg(
                           json_build_object(
                               'credit_id', cr.id,
                               'code', cr.code,
                               'label', cr.label,
                               'category', cr.category,
                               'amount', cc.credit_amount
                           )
                       ) FILTER (WHERE cr.id IS NOT NULL), '[]'::json) as credits
                FROM classes c
                LEFT JOIN class_credits cc ON c.id = cc.class_id
                LEFT JOIN credits cr ON cc.credit_id = cr.id
                WHERE c.id = :id
                GROUP BY c.id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $result['credits'] = json_decode($result['credits'], true);
        }
        
        return $result ?: null;
    }

    public function findAll(): array
    {
        $sql = "SELECT c.*, 
                       COALESCE(json_agg(
                           json_build_object(
                               'credit_id', cr.id,
                               'code', cr.code,
                               'label', cr.label,
                               'category', cr.category,
                               'amount', cc.credit_amount
                           )
                       ) FILTER (WHERE cr.id IS NOT NULL), '[]'::json) as credits
                FROM classes c
                LEFT JOIN class_credits cc ON c.id = cc.class_id
                LEFT JOIN credits cr ON cc.credit_id = cr.id
                GROUP BY c.id
                ORDER BY c.event_datetime ASC";
        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as &$result) {
            $result['credits'] = json_decode($result['credits'], true);
        }
        
        return $results;
    }

    public function findByOrganizer(string $organizer): array
    {
        $sql = "SELECT c.*,
                       COALESCE(json_agg(
                           json_build_object(
                               'credit_id', cr.id,
                               'code', cr.code,
                               'label', cr.label,
                               'category', cr.category,
                               'amount', cc.credit_amount
                           )
                       ) FILTER (WHERE cr.id IS NOT NULL), '[]'::json) as credits
                FROM classes c
                LEFT JOIN class_credits cc ON c.id = cc.class_id
                LEFT JOIN credits cr ON cc.credit_id = cr.id
                WHERE c.organizer = :organizer
                GROUP BY c.id
                ORDER BY c.event_datetime ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':organizer' => $organizer]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as &$result) {
            $result['credits'] = json_decode($result['credits'], true);
        }
        
        return $results;
    }

    public function findByDateRange(string $startDate, string $endDate): array
    {
        $sql = "SELECT * FROM classes 
                WHERE event_datetime >= :start_date AND event_datetime <= :end_date 
                ORDER BY event_datetime ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as &$result) {
            $result['credit_code'] = json_decode($result['credit_code'], true);
        }
        
        return $results;
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE classes 
                SET class_name = :class_name, description = :description, organizer = :organizer,
                    event_datetime = :event_datetime, duration_minutes = :duration_minutes, 
                    credit_code = :credit_code
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':class_name' => $data['class_name'],
            ':description' => $data['description'],
            ':organizer' => $data['organizer'],
            ':event_datetime' => $data['event_datetime'],
            ':duration_minutes' => $data['duration_minutes'],
            ':credit_code' => json_encode($data['credit_code'])
        ]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM classes WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}