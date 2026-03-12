<?php

namespace Synchly\Controller;

use Synchly\Repository\Database;
use Synchly\Repository\UserRepository;

class UserController
{

    /**
     * サインイン（既存ユーザーのログイン）
     */
    public function signIn()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $database = new Database();
            $db = $database->getConnection();

            // Emailでユーザーを検索
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            // パスワード照合（ハッシュ化対応）
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['username'];
                $_SESSION['user_gender'] = $user['gender'];
                header('Location: /');
                exit;
            } else {
                // 失敗したらログイン画面へ（エラーメッセージは任意）
                header('Location: /login?error=1');
                exit;
            }
        }
    }

    /**
     * サインアップ（新規ユーザー登録）
     */
    public function signUp()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
            $gender = $_POST['gender'] ?? 1;

            // UUIDの生成 (デファクトスタンダードな形式)
            $data = random_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant RFC 4122
            $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

            $database = new Database();
            $db = $database->getConnection();

            $stmt = $db->prepare("INSERT INTO users (id, username, email, password_hash, gender) VALUES (?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$uuid, $username, $email, $password, $gender]);

                // 登録成功したらそのままログイン状態にする
                $_SESSION['user_id'] = $uuid;
                $_SESSION['user_name'] = $username;
                $_SESSION['user_gender'] = $gender;
                header('Location: /');
            } catch (\Exception $e) {
                header('Location: /signup?error=dup');
            }
            exit;
        }
    }

    /**
     * ユーザー一覧API（異性のみ、いいね済み除外）
     */
    public function getUsersApi()
    {
        $myId = $_SESSION['user_id'] ?? null;
        $myGender = $_SESSION['user_gender'] ?? null;

        if (!$myId || !$myGender) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }

        $database = new Database();
        $db = $database->getConnection();
        $repository = new UserRepository($db);

        $users = $repository->findOppositeGender($myId, $myGender);

        header('Content-Type: application/json');
        echo json_encode($users);
        exit;
    }

    /**
     * いいね処理API
     */
    public function handleLikeApi()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $toUserId = $input['to_user_id'] ?? null;
        $myId = $_SESSION['user_id'] ?? null;

        if ($toUserId && $myId) {
            $database = new Database();
            $db = $database->getConnection();
            $repository = new UserRepository($db);

            $success = $repository->saveLike($myId, $toUserId);

            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
            exit;
        }
    }

    public function getMyLikesApi()
    {
        // データベース接続を取得
        $database = new Database();
        $db = $database->getConnection();

        $stmt = $db->prepare("
            SELECT users.id, users.username, users.profile_image, users.bio 
            FROM likes 
            JOIN users ON likes.to_user_id = users.id 
            WHERE likes.from_user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        header('Content-Type: application/json');
        echo json_encode($stmt->fetchAll(\PDO::FETCH_ASSOC));
        exit;
    }

    public function removeLikeApi()
    {
        // 1. 送られてきたJSONデータを取得
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        // 2. 相手のIDがちゃんと入っているかチェック
        $toUserId = $data['to_user_id'] ?? null;
        $myId = $_SESSION['user_id'] ?? null;

        // --- ここからデバッグ用ログ ---
        // C:\Users\student\Desktop\Synchly\debug.log に書き込まれるよ
        $log = sprintf("[%s] From: %s, To: %s\n", date('Y-m-d H:i:s'), $myId, $toUserId);
        file_put_contents(__DIR__ . '/../../debug.log', $log, FILE_APPEND);
        // --- デバッグ用ログここまで ---

        if (!$toUserId || !$myId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'IDが足りません']);
            exit;
        }

        // 3. データベース接続
        $database = new Database();
        $db = $database->getConnection();

        // 4. 削除実行
        $stmt = $db->prepare("DELETE FROM likes WHERE from_user_id = ? AND to_user_id = ?");
        $success = $stmt->execute([$myId, $toUserId]);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }
}
