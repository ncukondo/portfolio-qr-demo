-- クラスと単位の関連テーブル初期データ
-- class_id は classes テーブルの id を参照
-- credit_id は credits テーブルの id を参照

-- このファイルは003_classes_seed.sqlに統合されたため不要
-- 以下は参考用のデータ（実際には003で挿入される）

-- 追加のクラス-単位関連データ（必要に応じて）
INSERT INTO class_credits (class_id, credit_id, credit_amount) VALUES
-- Web開発入門に追加単位
(1, (SELECT id FROM credits WHERE code = 'BZ002'), 1.0), -- プロジェクト管理 1単位

-- PHP開発実践に追加単位  
(3, (SELECT id FROM credits WHERE code = 'SK001'), 1.0), -- チームワーク 1単位

-- プロジェクト管理に追加単位
(5, (SELECT id FROM credits WHERE code = 'SK002'), 1.0)  -- リーダーシップ 1単位
ON CONFLICT DO NOTHING;