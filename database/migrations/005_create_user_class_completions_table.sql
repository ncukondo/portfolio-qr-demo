-- Create user_class_completions table
-- ユーザークラス受講完了テーブル

CREATE TABLE IF NOT EXISTS user_class_completions (
    id SERIAL PRIMARY KEY,
    user_id UUID NOT NULL,
    class_id INTEGER NOT NULL,
    completed_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    CONSTRAINT fk_user_class_completions_user_id 
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_class_completions_class_id 
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    
    -- Unique constraint to prevent duplicate completions
    CONSTRAINT uk_user_class_completions_user_class 
        UNIQUE (user_id, class_id)
);

-- Create indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_user_class_completions_user_id ON user_class_completions(user_id);
CREATE INDEX IF NOT EXISTS idx_user_class_completions_class_id ON user_class_completions(class_id);
CREATE INDEX IF NOT EXISTS idx_user_class_completions_completed_at ON user_class_completions(completed_at);

-- Add comments for documentation
COMMENT ON TABLE user_class_completions IS 'ユーザーのクラス受講完了記録を管理するテーブル';
COMMENT ON COLUMN user_class_completions.id IS '主キー';
COMMENT ON COLUMN user_class_completions.user_id IS 'ユーザーID（外部キー）';
COMMENT ON COLUMN user_class_completions.class_id IS 'クラスID（外部キー）';
COMMENT ON COLUMN user_class_completions.completed_at IS 'クラス受講完了日時';
COMMENT ON COLUMN user_class_completions.created_at IS 'レコード作成日時';
COMMENT ON COLUMN user_class_completions.updated_at IS 'レコード更新日時';

-- Create trigger to automatically update updated_at column
CREATE OR REPLACE FUNCTION update_user_class_completions_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_user_class_completions_updated_at
    BEFORE UPDATE ON user_class_completions
    FOR EACH ROW
    EXECUTE FUNCTION update_user_class_completions_updated_at();