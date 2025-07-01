<?php
session_start();
require_once '../vendor/autoload.php';

use App\Auth\Auth;

// class-ownerまたはadministratorロールが必要
Auth::requireAuth();
if (!Auth::hasRole('class-owner') && !Auth::hasRole('administrator')) {
    header('Location: index.php');
    exit;
}

// CSVヘッダー（UTF-8 with BOM）
$csvHeader = [
    'クラス名',
    '説明', 
    '開催団体',
    '開催日',
    '開催時刻',
    '時間（分）',
    '単位コード（カンマ区切り）'
];

// サンプルデータ
$sampleData = [
    [
        'Web開発入門サンプル',
        'HTML、CSS、JavaScriptの基礎を学ぶクラスのサンプル',
        '技術研修センター',
        '2024-12-01',
        '10:00',
        '120',
        'IT001,IT002'
    ],
    [
        'データベース設計サンプル',
        'PostgreSQLを使用したデータベース設計とSQL基礎のサンプル',
        'データベース研究室',
        '2024-12-05',
        '14:00',
        '90',
        'IT002,BZ002'
    ]
];

// CSVファイル名
$filename = 'class_template_' . date('Y-m-d') . '.csv';

// HTTPヘッダー設定
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// UTF-8 BOM追加
echo "\xEF\xBB\xBF";

// CSV出力
$output = fopen('php://output', 'w');

// ヘッダー行を出力
fputcsv($output, $csvHeader);

// サンプルデータを出力
foreach ($sampleData as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>