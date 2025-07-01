-- クラスと単位の中間テーブル作成（多対多の関係）
CREATE TABLE IF NOT EXISTS class_credits (
    id SERIAL PRIMARY KEY,
    class_id INTEGER NOT NULL,
    credit_id INTEGER NOT NULL,
    credit_amount DECIMAL(3,1) DEFAULT 1.0 NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- 外部キー制約
    CONSTRAINT fk_class_credits_class_id 
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    CONSTRAINT fk_class_credits_credit_id 
        FOREIGN KEY (credit_id) REFERENCES credits(id) ON DELETE CASCADE,
    
    -- 同じクラスと単位の組み合わせは一意
    CONSTRAINT unique_class_credit 
        UNIQUE (class_id, credit_id)
);

-- テーブルコメント
COMMENT ON TABLE class_credits IS 'クラスと単位の関連テーブル';
COMMENT ON COLUMN class_credits.class_id IS 'クラスID';
COMMENT ON COLUMN class_credits.credit_id IS '単位ID';
COMMENT ON COLUMN class_credits.credit_amount IS '取得可能単位数';

-- インデックス作成
CREATE INDEX idx_class_credits_class_id ON class_credits(class_id);
CREATE INDEX idx_class_credits_credit_id ON class_credits(credit_id);