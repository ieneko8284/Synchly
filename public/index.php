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
        exit;

        // ★ここを追加：ログイン画面の表示
    case '/login':
        require __DIR__ . '/../views/login.php';
        exit;

        // ★ここを追加：ログアウト処理
    case '/logout':
        $_SESSION = array(); // セッション変数を空にする
        session_destroy();    // セッションを破棄
        header('Location: /login'); // ログイン画面へ戻す
        exit;

    case '/signup':
        require __DIR__ . '/../views/signup.php';
        exit;

    case '/api/signin':
        $controller = new UserController();
        $controller->signIn();
        exit;

    case '/api/signup':
        $controller = new UserController();
        $controller->signUp();
        exit;

    case '/api/users':
        $controller = new UserController();
        $controller->getUsersApi();
        exit;

    case '/api/like':
        $controller = new UserController();
        $controller->handleLikeApi();
        exit;

        // --- api/likes/mine (自分がいいねした人一覧) ---
        // --- index.php の該当箇所をこれに差し替え ---
    case '/api/likes/mine':
        $controller = new UserController();
        $controller->getMyLikesApi(); // ここでコントローラーを呼ぶ
        exit;

    case '/api/like/remove':
        $controller = new UserController();
        $controller->removeLikeApi(); // ここも同じ
        exit;

    case '/api/likes/received':
        $controller = new UserController();
        $controller->getReceivedLikesApi();
        exit;

    case '/api/matches':
        $controller = new UserController();
        $controller->getMatchesApi();
        exit;

        // public/index.php

    case '/api/check-new-matches':
        $controller = new UserController();
        $controller->checkNewMatchesApi(); // ここでコントローラーを呼ぶ
        exit;

    case '/api/mark-match-notified':
        $controller = new UserController();
        $controller->markMatchNotifiedApi(); // 通知済みフラグを立てる用
        exit;

        // index.php のルーター部分のイメージ
    case '/api/my-profile': // または action パラメータ判定
        $controller = new UserController();
        $controller->getMyProfileApi();
        exit; // JSONを返した後は必ず止める！

        // --- チャット機能 ---
    case '/api/chat/history':
        $controller = new UserController();
        $controller->getChatHistoryApi();
        exit;

    case '/api/chat/send':
        $controller = new UserController();
        $controller->sendMessageApi();
        exit;

    case '/api/withdraw':
        $controller = new UserController();
        $controller->withdrawApi();
        exit;

    // case '/api/profile/update': // ここを追加しておこう
    //     $controller = new UserController();
    //     $controller->updateProfileApi();
    //     break;

    default:
        http_response_code(404);
        echo "404 Not Found";
        exit;
}
