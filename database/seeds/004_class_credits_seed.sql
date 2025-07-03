-- クラスと単位の関連テーブル初期データ
-- class_id は classes テーブルの id を参照
-- credit_id は credits テーブルの id を参照

INSERT INTO class_credits (class_id, credit_id, credit_amount) VALUES
-- Web開発入門 (class_id: 1)
(1, 1, 2.0), -- プログラミング基礎 2単位
(1, 4, 1.0), -- プロジェクト管理 1単位

-- データベース設計 (class_id: 2)  
(2, 2, 3.0), -- データベース設計 3単位
(2, 1, 1.0), -- プログラミング基礎 1単位

-- PHP開発実践 (class_id: 3)
(3, 1, 3.0), -- プログラミング基礎 3単位
(3, 7, 1.0), -- チームワーク 1単位

-- セキュリティ基礎 (class_id: 4)
(4, 1, 2.0), -- プログラミング基礎 2単位

-- プロジェクト管理 (class_id: 5)
(5, 4, 2.0), -- プロジェクト管理 2単位
(5, 8, 1.0)  -- リーダーシップ 1単位
ON CONFLICT DO NOTHING;