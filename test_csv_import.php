<?php
require_once 'vendor/autoload.php';

use App\Database\Database;
use App\Services\QRCodeService;
use App\Services\ClassCompletionTokenService;

echo "=== CSV Import Test ===\n";

try {
    // テストCSVファイルの処理をシミュレート
    $csvFile = '/workspace/test_import.csv';
    
    if (!file_exists($csvFile)) {
        throw new Exception('Test CSV file not found');
    }
    
    $csvData = [];
    $handle = fopen($csvFile, 'r');
    
    if ($handle !== false) {
        // ヘッダー行をスキップ
        $headerRow = fgetcsv($handle);
        echo "CSV Headers: " . implode(', ', $headerRow) . "\n";
        
        $rowNumber = 2;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 6 && !empty(trim($row[0]))) {
                $csvData[] = [
                    'row_number' => $rowNumber,
                    'class_name' => trim($row[0]),
                    'description' => trim($row[1]),
                    'organizer' => trim($row[2]),
                    'event_date' => trim($row[3]),
                    'event_time' => trim($row[4]),
                    'duration_minutes' => trim($row[5]),
                    'credit_codes' => isset($row[6]) ? trim($row[6]) : ''
                ];
            }
            $rowNumber++;
        }
        fclose($handle);
        
        echo "Found " . count($csvData) . " data rows\n\n";
        
        // データベースに登録をテスト
        $db = Database::getInstance();
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($csvData as $data) {
            try {
                echo "Processing row {$data['row_number']}: {$data['class_name']}\n";
                
                // バリデーション
                $errors = [];
                if (empty($data['class_name'])) {
                    $errors[] = 'クラス名が空です';
                }
                if (empty($data['organizer'])) {
                    $errors[] = '開催団体が空です';
                }
                if (empty($data['event_date']) || !strtotime($data['event_date'])) {
                    $errors[] = '開催日の形式が正しくありません';
                }
                if (empty($data['event_time']) || !preg_match('/^\d{1,2}:\d{2}$/', $data['event_time'])) {
                    $errors[] = '開催時刻の形式が正しくありません（HH:MM）';
                }
                if (!is_numeric($data['duration_minutes']) || (int)$data['duration_minutes'] <= 0) {
                    $errors[] = '時間は正の数値で入力してください';
                }
                
                if (!empty($errors)) {
                    throw new Exception(implode(', ', $errors));
                }
                
                // 日時の結合
                $eventDatetime = $data['event_date'] . ' ' . $data['event_time'];
                echo "  Event datetime: $eventDatetime\n";
                
                // クラスを登録
                $query = "INSERT INTO classes (class_name, description, organizer, event_datetime, duration_minutes) 
                         VALUES (?, ?, ?, ?, ?) RETURNING id";
                
                $stmt = $db->query($query, [
                    $data['class_name'],
                    $data['description'],
                    $data['organizer'],
                    $eventDatetime,
                    (int)$data['duration_minutes']
                ]);
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $classId = $result['id'];
                echo "  Class created with ID: $classId\n";
                
                // 単位コードの処理
                if (!empty($data['credit_codes'])) {
                    $creditCodes = array_map('trim', explode(',', $data['credit_codes']));
                    echo "  Processing credit codes: " . implode(', ', $creditCodes) . "\n";
                    
                    foreach ($creditCodes as $creditCode) {
                        if (!empty($creditCode)) {
                            // creditsテーブルからIDを取得
                            $creditQuery = "SELECT id FROM credits WHERE code = ?";
                            $creditStmt = $db->query($creditQuery, [$creditCode]);
                            $creditResult = $creditStmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($creditResult) {
                                $creditId = $creditResult['id'];
                                
                                // class_creditsテーブルに関連を登録
                                $relationQuery = "INSERT INTO class_credits (class_id, credit_id) VALUES (?, ?)";
                                $db->query($relationQuery, [$classId, $creditId]);
                                echo "    Linked credit code: $creditCode (ID: $creditId)\n";
                            } else {
                                echo "    WARNING: Credit code not found: $creditCode\n";
                            }
                        }
                    }
                }
                
                $successCount++;
                echo "  SUCCESS: Class registered\n\n";
                
            } catch (Exception $e) {
                $errorCount++;
                echo "  ERROR: " . $e->getMessage() . "\n\n";
            }
        }
        
        echo "=== Import Results ===\n";
        echo "Successful: $successCount\n";
        echo "Errors: $errorCount\n";
        
        if ($successCount > 0) {
            echo "\nImport completed successfully!\n";
        }
        
    } else {
        throw new Exception('CSVファイルを読み込めませんでした。');
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>