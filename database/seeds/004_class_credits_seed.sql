-- クラスと単位の関連テーブル初期データ
-- class_id は classes テーブルの id を参照
-- credit_id は credits テーブルの id を参照

-- クラスと単位の関連データ（class_nameで参照、LIMIT 1で重複回避）
INSERT INTO class_credits (class_id, credit_id, credit_amount) VALUES
-- Web開発入門 → プログラミング基礎
((SELECT id FROM classes WHERE class_name = 'Web開発入門' LIMIT 1), (SELECT id FROM credits WHERE code = 'IT001'), 2.0),
-- データベース設計 → データベース設計  
((SELECT id FROM classes WHERE class_name = 'データベース設計' LIMIT 1), (SELECT id FROM credits WHERE code = 'IT002'), 3.0),
-- PHP開発実践 → プログラミング基礎
((SELECT id FROM classes WHERE class_name = 'PHP開発実践' LIMIT 1), (SELECT id FROM credits WHERE code = 'IT001'), 3.0),
-- セキュリティ基礎 → プログラミング基礎
((SELECT id FROM classes WHERE class_name = 'セキュリティ基礎' LIMIT 1), (SELECT id FROM credits WHERE code = 'IT001'), 2.0),
-- プロジェクト管理 → プロジェクト管理
((SELECT id FROM classes WHERE class_name = 'プロジェクト管理' LIMIT 1), (SELECT id FROM credits WHERE code = 'BZ002'), 2.0),

-- 追加の関連データ
-- Web開発入門に追加単位
((SELECT id FROM classes WHERE class_name = 'Web開発入門' LIMIT 1), (SELECT id FROM credits WHERE code = 'BZ002'), 1.0), -- プロジェクト管理 1単位
-- PHP開発実践に追加単位  
((SELECT id FROM classes WHERE class_name = 'PHP開発実践' LIMIT 1), (SELECT id FROM credits WHERE code = 'SK001'), 1.0), -- チームワーク 1単位
-- プロジェクト管理に追加単位
((SELECT id FROM classes WHERE class_name = 'プロジェクト管理' LIMIT 1), (SELECT id FROM credits WHERE code = 'SK002'), 1.0)  -- リーダーシップ 1単位
ON CONFLICT DO NOTHING;