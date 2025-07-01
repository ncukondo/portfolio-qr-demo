-- 単位管理用テーブル作成
CREATE TABLE IF NOT EXISTS credits (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    label VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- テーブルコメント
COMMENT ON TABLE credits IS '単位管理テーブル';
COMMENT ON COLUMN credits.code IS '単位コード（一意）';
COMMENT ON COLUMN credits.label IS '単位名';
COMMENT ON COLUMN credits.category IS '単位カテゴリ';
COMMENT ON COLUMN credits.description IS '単位の説明';

-- インデックス作成
CREATE INDEX IF NOT EXISTS idx_credits_code ON credits(code);
CREATE INDEX IF NOT EXISTS idx_credits_category ON credits(category);
CREATE INDEX IF NOT EXISTS idx_credits_label ON credits(label);

-- 更新時刻の自動更新トリガー
DROP TRIGGER IF EXISTS update_credits_updated_at ON credits;
CREATE TRIGGER update_credits_updated_at 
    BEFORE UPDATE ON credits 
    FOR EACH ROW 
    EXECUTE FUNCTION update_updated_at_column();