<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synchly - 共鳴する出会い</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

    <header class="app-header">
        <h1>Synchly</h1>
    </header>

    <main id="app-content" class="container">
        <div class="loader">読み込み中...</div>
    </main>

    <nav class="mobile-nav">
        <button class="nav-item active" data-page="list">
            <span class="nav-icon">🏠</span>
            <span class="nav-label">探す</span>
        </button>
        <button class="nav-item" data-page="likes">
            <span class="nav-icon">♥</span>
            <span class="nav-label">いいね</span>
        </button>
        <button class="nav-item" data-page="messages">
            <span class="nav-icon">✉️</span>
            <span class="nav-label">トーク</span>
        </button>
        <button class="nav-item" data-page="mypage">
            <span class="nav-icon">👤</span>
            <span class="nav-label">マイページ</span>
        </button>
    </nav>

    <script src="/js/app.js" type="module"></script>
</body>
</html>