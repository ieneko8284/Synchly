<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synchly - 共鳴する出会い</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
</head>

<body data-my-id="<?php echo $_SESSION['user_id']; ?>">

    <header class="app-header">
        <h1>Synchly</h1>
    </header>

    <main id="app-content" class="container">
        <div class="loader">読み込み中...</div>
    </main>

    <nav class="mobile-nav">
        <button class="nav-item active" data-page="list">
            <span class="material-symbols-outlined">search</span>
            <span class="nav-label">探す</span>
        </button>
        <button class="nav-item" data-page="received-likes">
            <span class="material-symbols-outlined">favorite</span>
            <span class="nav-label">いいね</span>
        </button>
        <button class="nav-item" data-page="matches"> <span class="material-symbols-outlined">chat_bubble</span>
            <span class="nav-label">トーク</span>
        </button>
        <button class="nav-item" data-page="mypage">
            <span class="material-symbols-outlined">person</span>
            <span class="nav-label">マイページ</span>
        </button>
    </nav>

    <script src="/js/app.js" type="module"></script>
</body>

</html>