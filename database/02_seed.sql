-- テストデータ挿入
-- ※パスワードは全て 'password123' をハッシュ化したもの
-- $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO users (id, username, email, password_hash, gender, bio) VALUES 
(UUID(), 'じろう', 'jiro@example.com', '$2y$10$HJ/BcpWW5XRpXLxYZKnvzO1fcttW71YRCIkcvSnJJMpXSqxNOab0e', 1, 'じろうです。テストユーザー1号です。'),
(UUID(), 'あかり', 'akari@example.com', '$2y$10$HJ/BcpWW5XRpXLxYZKnvzO1fcttW71YRCIkcvSnJJMpXSqxNOab0e', 2, '音楽好きな女性です！'),
(UUID(), 'ひかり', 'hikari@example.com', '$2y$10$HJ/BcpWW5XRpXLxYZKnvzO1fcttW71YRCIkcvSnJJMpXSqxNOab0e', 2, '歌うのが好きです。');