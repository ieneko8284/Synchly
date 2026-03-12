<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synchly - 新規登録</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <h1>Synchly</h1>
        <p class="auth-subtitle">新しい共鳴をみつけよう</p>

        <form action="/api/signup" method="POST">
            <div class="form-group">
                <label>ニックネーム</label>
                <input type="text" name="username" required placeholder="例：家村佳佑">
            </div>
            <div class="form-group">
                <label>メールアドレス</label>
                <input type="email" name="email" required placeholder="music@example.com">
            </div>
            <div class="form-group">
                <label>パスワード</label>
                <input type="password" name="password" required placeholder="8文字以上">
            </div>
            <div class="form-group">
                <label>あなたの性別</label>
                <select name="gender" required>
                    <option value="1">男性</option>
                    <option value="2">女性</option>
                </select>
            </div>
            <button type="submit" class="auth-btn">アカウント作成</button>
        </form>

        <div class="auth-footer">
            <p>すでに登録済み？ <a href="/login">サインイン</a></p>
        </div>
    </div>
</body>
</html>