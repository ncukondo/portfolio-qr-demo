-- classesテーブルからcredit_code JSONB列を削除
ALTER TABLE classes DROP COLUMN IF EXISTS credit_code;

-- インデックスも削除
DROP INDEX IF EXISTS idx_classes_credit_code;