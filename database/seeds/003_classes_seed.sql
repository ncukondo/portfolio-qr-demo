-- クラステーブルのサンプルデータ
INSERT INTO classes (class_name, description, organizer, event_datetime, duration_minutes) VALUES
('Web開発入門', 'HTML、CSS、JavaScriptの基礎を学ぶクラス', '技術研修センター', '2024-01-15 10:00:00', 120),
('データベース設計', 'PostgreSQLを使用したデータベース設計とSQL基礎', 'データベース研究室', '2024-01-20 14:00:00', 90),
('PHP開発実践', 'PHPフレームワークを使った実践的な開発', 'プログラミング部', '2024-01-25 09:30:00', 150),
('セキュリティ基礎', 'Webアプリケーションのセキュリティ対策', 'セキュリティ研究会', '2024-02-01 13:00:00', 100),
('プロジェクト管理', 'アジャイル開発とプロジェクト管理手法', 'PMO', '2024-02-05 10:30:00', 80)
ON CONFLICT DO NOTHING;

-- クラスと単位の関連データ（codeで参照）
INSERT INTO class_credits (class_id, credit_id, credit_amount) VALUES
-- Web開発入門 → プログラミング基礎
(1, (SELECT id FROM credits WHERE code = 'IT001'), 2.0),
-- データベース設計 → データベース設計  
(2, (SELECT id FROM credits WHERE code = 'IT002'), 3.0),
-- PHP開発実践 → プログラミング基礎
(3, (SELECT id FROM credits WHERE code = 'IT001'), 3.0),
-- セキュリティ基礎 → プログラミング基礎
(4, (SELECT id FROM credits WHERE code = 'IT001'), 2.0),
-- プロジェクト管理 → プロジェクト管理
(5, (SELECT id FROM credits WHERE code = 'BZ002'), 2.0)
ON CONFLICT DO NOTHING;