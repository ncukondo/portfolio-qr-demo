-- クラスと単位の関連テーブル初期データ
-- class_id は classes テーブルの id を参照
-- credit_id は credits テーブルの id を参照

-- クラスと単位の関連データ（classesテーブル挿入後に実行）
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
(5, (SELECT id FROM credits WHERE code = 'BZ002'), 2.0),

-- 追加の関連データ
-- Web開発入門に追加単位
(1, (SELECT id FROM credits WHERE code = 'BZ002'), 1.0), -- プロジェクト管理 1単位
-- PHP開発実践に追加単位  
(3, (SELECT id FROM credits WHERE code = 'SK001'), 1.0), -- チームワーク 1単位
-- プロジェクト管理に追加単位
(5, (SELECT id FROM credits WHERE code = 'SK002'), 1.0)  -- リーダーシップ 1単位
ON CONFLICT DO NOTHING;