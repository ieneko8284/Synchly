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

    public function signUp()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $passwordRaw = $_POST['password'] ?? '';
        $gender = $_POST['gender'] ?? 1;

        // --- 1. バリデーション（入力チェック） ---
        if (empty($username) || empty($email) || strlen($passwordRaw) < 6) {
            header('Location: /signup?error=invalid');
            exit;
        }

        // データベースとリポジトリの準備
        $database = new Database();
        $db = $database->getConnection();
        $repository = new UserRepository($db);

        // --- 2. 重複チェック（Repositoryのメソッドを使用） ---
        if ($repository->existsEmail($email)) {
            header('Location: /signup?error=dup');
            exit;
        }

        // --- 3. ユーザー作成処理 ---
        $passwordHash = password_hash($passwordRaw, PASSWORD_DEFAULT);

        // UUID生成（ここはロジックとしてコントローラーに残してもOK）
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        try {
            // 保存処理をRepositoryに任せる
            $success = $repository->createUser($uuid, $username, $email, $passwordHash, $gender);

            if ($success) {
                // 成功したらセッションに保存
                $_SESSION['user_id'] = $uuid;
                $_SESSION['user_name'] = $username;
                $_SESSION['user_gender'] = $gender;
                header('Location: /');
            } else {
                throw new \Exception("Insert failed");
            }
        } catch (\Exception $e) {
            header('Location: /signup?error=system');
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

            // 1. まずはいいねを保存
            $success = $repository->saveLike($myId, $toUserId);

            $isMatched = false;
            $matchedUser = null;

            if ($success) {
                $isMatched = $repository->isMatched($myId, $toUserId);

                if ($isMatched) {
                    // ★追加：まだマッチングテーブルにデータがなければ作成する
                    if (!$repository->existsMatch($myId, $toUserId)) {
                        $repository->createMatch($myId, $toUserId);
                    }

                    // マッチした相手の情報を取得
                    $stmt = $db->prepare("SELECT id, username, profile_image FROM users WHERE id = ?");
                    $stmt->execute([$toUserId]);
                    $matchedUser = $stmt->fetch(\PDO::FETCH_ASSOC);
                }
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'isMatched' => $isMatched,
                'matchedUser' => $matchedUser
            ]);
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
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $toUserId = $data['to_user_id'] ?? null;
    $myId = $_SESSION['user_id'] ?? null;

    if (!$toUserId || !$myId) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID不足']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();
    $repository = new UserRepository($db);

    // ★リポジトリの新しいメソッドを呼び出す
    $success = $repository->removeLikeAndPotentialMatch($myId, $toUserId);

    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

    public function getReceivedLikesApi()
    {
        $myId = $_SESSION['user_id'] ?? null;
        if (!$myId) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }

        $database = new Database();
        $db = $database->getConnection();
        // Repositoryをインスタンス化して呼び出す
        $repository = new UserRepository($db);
        $users = $repository->findReceivedLikes($myId);

        header('Content-Type: application/json');
        echo json_encode($users);
        exit;
    }

    public function getMatchesApi()
    {
        $myId = $_SESSION['user_id'] ?? null;
        if (!$myId) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }

        $database = new Database();
        $db = $database->getConnection();
        $repository = new UserRepository($db);
        $users = $repository->findMatchedUsers($myId);

        header('Content-Type: application/json');
        echo json_encode($users);
        exit;
    }

    public function checkNewMatchesApi()
    {
        $myId = $_SESSION['user_id'] ?? null;
        if (!$myId) {
            echo json_encode([]);
            exit;
        }

        $database = new Database();
        $db = $database->getConnection();
        $repository = new UserRepository($db);

        // まだ通知していない（is_notified = 0）相互いいねユーザーを取得
        $newMatches = $repository->getUnnotifiedMatches($myId);

        // 取得できたら、即座に「通知済み」に更新して、二度出ないようにする
        foreach ($newMatches as $match) {
            $repository->markMatchesAsNotified($myId, $match['id']);
        }

        header('Content-Type: application/json');
        echo json_encode($newMatches);
        exit;
    }

    public function markMatchNotifiedApi()
    {
        // JSON形式で送られてくるデータを受け取る
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $myId = $_SESSION['user_id'] ?? null;
        $fromUserId = $data['from_user_id'] ?? null;

        if (!$myId || !$fromUserId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        $database = new Database();
        $db = $database->getConnection();
        $repository = new UserRepository($db);

        // Repositoryのメソッドを呼んでフラグを更新（1にする）
        $success = $repository->markMatchesAsNotified($myId, $fromUserId);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }

    // src/Controller/UserController.php 内に追加

    public function getMyProfileApi()
    {
        $myId = $_SESSION['user_id'] ?? null;

        if (!$myId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $database = new Database();
        $db = $database->getConnection();
        $repository = new UserRepository($db);

        $user = $repository->findById($myId);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => !!$user, // $userが存在すればtrue
            'user' => $user
        ]);
        exit;
    }

    // src/Controller/UserController.php

    public function sendMessageApi()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $matchId = $input['match_id'] ?? null;
        $text = $input['message'] ?? '';
        $myId = $_SESSION['user_id'] ?? null;

        if ($matchId && $text && $myId) {
            $database = new Database();
            $db = $database->getConnection();
            $repository = new UserRepository($db);

            $success = $repository->saveMessage($matchId, $myId, $text);
            echo json_encode(['success' => $success]);
        }
        exit;
    }

    public function getChatHistoryApi()
    {
        $matchId = $_GET['match_id'] ?? null;
        if ($matchId) {
            $database = new Database();
            $db = $database->getConnection();
            $repository = new UserRepository($db);

            $messages = $repository->getMessagesByMatchId($matchId);
            header('Content-Type: application/json');
            echo json_encode($messages);
        }
        exit;
    }

    public function withdrawApi()
    {
        $myId = $_SESSION['user_id'] ?? null;
        if (!$myId) { /* エラー処理 */
        }

        $database = new Database();
        $db = $database->getConnection();
        $repository = new UserRepository($db);

        // これだけでDBが全部掃除してくれる
        $success = $repository->deleteUserComplete($myId);

        if ($success) {
            $_SESSION = [];
            session_destroy();
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }
}
