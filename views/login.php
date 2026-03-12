<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synchly - ログイン</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <h1>Synchly</h1>
        <p class="auth-subtitle">おかえりなさい！</p>
        
        <?php if(isset($_GET['error'])): ?>
            <p class="error-msg">メールアドレスかパスワードが違います。</p>
        <?php endif; ?>

        <form action="/api/signin" method="POST">
            <div class="form-group">
                <label>メールアドレス</label>
                <input type="email" name="email" required placeholder="jiro@example.com">
            </div>
            <div class="form-group">
                <label>パスワード</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="auth-btn">サインイン</button>
        </form>

        <div class="auth-footer">
            <p>アカウントを持っていない？ <a href="/signup">新規登録（サインアップ）</a></p>
        </div>
    </div>
</body>
</html>