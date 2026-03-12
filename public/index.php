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
        // ログイン状態によって振り分け
        if (isset($_SESSION['user_id'])) {
            require __DIR__ . '/../views/main.php'; // メインのガワを返す
        } else {
            header('Location: /login');
        }
        break;

    case '/api/users':
        $controller = new UserController();
        $controller->getUsersApi(); // JSONを返す
        break;

    case '/login':
        // ログイン画面の表示
        require __DIR__ . '/../views/login.php';
        break;

    default:
        // どこにも当てはまらない場合は404
        http_response_code(404);
        echo "404 Not Found";
        break;
}