<?php
// public/index.php
session_start();

// Composerのオートロードを読み込み
require_once __DIR__ . '/../vendor/autoload.php';

use Synchly\Controller\UserController;

// URLのパスを取得（例: /api/users や /list）
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Ajax（非同期）リクエストかどうかを判定するフラグ
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// 簡単なルーター
switch ($path) {
    case '/':
    case '/index.php':
        if (isset($_SESSION['user_id'])) {
            require __DIR__ . '/../views/main.php';
        } else {
            header('Location: /login');
        }
        break;

    // ★ここを追加：ログイン画面の表示
    case '/login':
        require __DIR__ . '/../views/login.php';
        break;

    // ★ここを追加：ログアウト処理
    case '/logout':
        $_SESSION = array(); // セッション変数を空にする
        session_destroy();    // セッションを破棄
        header('Location: /login'); // ログイン画面へ戻す
        exit;

    case '/signup':
        require __DIR__ . '/../views/signup.php';
        break;

    case '/api/signin':
        $controller = new UserController();
        $controller->signIn();
        break;

    case '/api/signup':
        $controller = new UserController();
        $controller->signUp();
        break;

    case '/api/users':
        $controller = new UserController();
        $controller->getUsersApi();
        break;

    case '/api/like':
        $controller = new UserController();
        $controller->handleLikeApi();
        break;

    // --- api/likes/mine (自分がいいねした人一覧) ---
    // --- index.php の該当箇所をこれに差し替え ---
    case '/api/likes/mine':
        $controller = new UserController();
        $controller->getMyLikesApi(); // ここでコントローラーを呼ぶ
        break;

    case '/api/like/remove':
        $controller = new UserController();
        $controller->removeLikeApi(); // ここも同じ
        break;

    default:
        http_response_code(404);
        echo "404 Not Found";
        break;
}
