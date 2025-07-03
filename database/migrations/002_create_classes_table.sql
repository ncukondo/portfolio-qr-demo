-- クラス登録用テーブル作成
CREATE TABLE IF NOT EXISTS classes (
    id SERIAL PRIMARY KEY,
    class_name VARCHAR(255) NOT NULL,
    description TEXT,
    organizer VARCHAR(255) NOT NULL,
    event_datetime TIMESTAMP NOT NULL,
    duration_minutes INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- テーブルコメント
COMMENT ON TABLE classes IS 'クラス登録テーブル';
COMMENT ON COLUMN classes.class_name IS 'クラス名';
COMMENT ON COLUMN classes.description IS '詳細';
COMMENT ON COLUMN classes.organizer IS '開催団体';
COMMENT ON COLUMN classes.event_datetime IS '開催日時';
COMMENT ON COLUMN classes.duration_minutes IS '長さ(分)';

-- インデックス作成
CREATE INDEX IF NOT EXISTS idx_classes_event_datetime ON classes(event_datetime);
CREATE INDEX IF NOT EXISTS idx_classes_organizer ON classes(organizer);

-- 更新時刻の自動更新トリガー
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $func$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$func$ language 'plpgsql';

CREATE OR REPLACE TRIGGER update_classes_updated_at 
    BEFORE UPDATE ON classes 
    FOR EACH ROW 
    EXECUTE FUNCTION update_updated_at_column();