-- ユーザー用テストデータ

-- 管理者ユーザー
INSERT INTO users (id, name, email, password_hash, email_verified_at) VALUES
(gen_random_uuid(), '管理者 太郎', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', CURRENT_TIMESTAMP),
(gen_random_uuid(), 'クラス主催者 花子', 'owner@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', CURRENT_TIMESTAMP),
(gen_random_uuid(), '学習者 次郎', 'learner1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', CURRENT_TIMESTAMP),
(gen_random_uuid(), '学習者 三郎', 'learner2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL),
(gen_random_uuid(), 'マルチロール 四郎', 'multi@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', CURRENT_TIMESTAMP)
ON CONFLICT (email) DO NOTHING;

-- ユーザーIDを取得してロールを割り当て
WITH user_data AS (
  SELECT id, email FROM users WHERE email IN (
    'admin@example.com', 
    'owner@example.com', 
    'learner1@example.com', 
    'learner2@example.com', 
    'multi@example.com'
  )
)
INSERT INTO user_roles (user_id, role_id)
SELECT 
  u.id,
  r.id
FROM user_data u
CROSS JOIN roles r
WHERE 
  -- 管理者: administrator ロール
  (u.email = 'admin@example.com' AND r.name = 'administrator') OR
  -- クラス主催者: class-owner ロール
  (u.email = 'owner@example.com' AND r.name = 'class-owner') OR
  -- 学習者1: learner ロール
  (u.email = 'learner1@example.com' AND r.name = 'learner') OR
  -- 学習者2: learner ロール
  (u.email = 'learner2@example.com' AND r.name = 'learner') OR
  -- マルチロール: class-owner と learner ロール
  (u.email = 'multi@example.com' AND r.name IN ('class-owner', 'learner'))
ON CONFLICT (user_id, role_id) DO NOTHING;