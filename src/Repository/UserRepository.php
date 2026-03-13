<?php

namespace Synchly\Repository;

use PDO;

class UserRepository
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function findOppositeGender($myId, $myGender)
    {
        $targetGender = ($myGender == 1) ? 2 : 1;

        // NOT IN 句を使って、likesテーブルに自分のIDからの履歴がある人を除外する
        $query = "SELECT id, username, bio FROM users 
                  WHERE gender = :target 
                  AND id NOT IN (
                      SELECT to_user_id FROM likes WHERE from_user_id = :myId
                  )";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':target', $targetGender, PDO::PARAM_INT);
        $stmt->bindParam(':myId', $myId, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // 「いいね」を保存するメソッド
    public function saveLike($fromId, $toId)
    {
        $query = "INSERT IGNORE INTO likes (from_user_id, to_user_id) VALUES (:from, :to)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':from', $fromId);
        $stmt->bindParam(':to', $toId);
        return $stmt->execute();
    }

    // UserController 内に追加
    public function handleLikeApi()
    {
        // JSから送られてきた JSON を受け取る
        $input = json_decode(file_get_contents('php://input'), true);
        $toUserId = $input['to_user_id'] ?? null;
        $myId = $_SESSION['user_id'] ?? 'u1'; // 本来はログイン中のID

        if ($toUserId) {
            $database = new Database();
            $db = $database->getConnection();
            $repository = new UserRepository($db);

            $success = $repository->saveLike($myId, $toUserId);

            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
            exit;
        }
    }

    // UserRepository.php の中
    public function findReceivedLikes($myId)
    {
        // 71行目：これで $this->db が null じゃなくなるよ！
        $stmt = $this->conn->prepare("
            SELECT u.id, u.username, u.profile_image, u.bio 
            FROM likes l
            JOIN users u ON l.from_user_id = u.id 
            WHERE l.to_user_id = ?
        ");
        $stmt->execute([$myId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // 相手が自分をいいねしているか確認する
    public function isMatched($myId, $toUserId)
    {
        $stmt = $this->conn->prepare("
        SELECT COUNT(*) FROM likes 
        WHERE from_user_id = :toUserId AND to_user_id = :myId
    ");
        $stmt->execute(['toUserId' => $toUserId, 'myId' => $myId]);
        return $stmt->fetchColumn() > 0;
    }

    public function findMatchedUsers($myId)
    {
        // matchesテーブルをJOINして、match_idも取得するように変更
        $stmt = $this->conn->prepare("
        SELECT 
            u.id, 
            u.username, 
            u.profile_image, 
            u.bio,
            m.id AS match_id  -- ★ここがチャット機能で超重要！
        FROM users u
        JOIN matches m ON (
            (m.user_id_1 = :myId AND m.user_id_2 = u.id) OR 
            (m.user_id_1 = u.id AND m.user_id_2 = :myId)
        )
    ");
        $stmt->execute(['myId' => $myId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUnnotifiedMatches($myId)
    {
        // 自分宛てのいいね（相手→自分）のうち、まだ通知（is_notified=0）されていない
        // かつ、自分も相手にいいねを返しているもの
        $stmt = $this->conn->prepare("
        SELECT u.id, u.username, u.profile_image 
        FROM users u
        JOIN likes l1 ON u.id = l1.from_user_id AND l1.to_user_id = :myId
        JOIN likes l2 ON u.id = l2.to_user_id AND l2.from_user_id = :myId
        WHERE l1.is_notified = 0
    ");
        $stmt->execute(['myId' => $myId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function markMatchesAsNotified($myId, $fromUserId)
    {
        // 通知したマッチングを「既読（1）」にする
        $stmt = $this->conn->prepare("
        UPDATE likes SET is_notified = 1 
        WHERE from_user_id = :fromUserId AND to_user_id = :myId
    ");
        return $stmt->execute(['fromUserId' => $fromUserId, 'myId' => $myId]);
    }

    // src/Repository/UserRepository.php

    public function findById($id)
    {
        $stmt = $this->conn->prepare("SELECT username, profile_image, bio FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // src/Repository/UserRepository.php

    // メッセージを保存する
    public function saveMessage($matchId, $senderId, $text)
    {
        $stmt = $this->conn->prepare("INSERT INTO messages (match_id, sender_id, message_text) VALUES (?, ?, ?)");
        return $stmt->execute([$matchId, $senderId, $text]);
    }

    // 特定のマッチのメッセージ履歴をすべて取得する
    public function getMessagesByMatchId($matchId)
    {
        $stmt = $this->conn->prepare("
        SELECT * FROM messages 
        WHERE match_id = ? 
        ORDER BY created_at ASC
    ");
        $stmt->execute([$matchId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // UserRepository.php

    // すでにマッチング済みかチェック
    public function existsMatch($user1, $user2)
    {
        $u1 = min($user1, $user2);
        $u2 = max($user1, $user2);
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM matches WHERE user_id_1 = ? AND user_id_2 = ?");
        $stmt->execute([$u1, $u2]);
        return $stmt->fetchColumn() > 0;
    }

    // 新規マッチングを作成
    public function createMatch($user1, $user2)
    {
        $u1 = min($user1, $user2);
        $u2 = max($user1, $user2);
        $stmt = $this->conn->prepare("INSERT INTO matches (user_id_1, user_id_2, created_at) VALUES (?, ?, NOW())");
        return $stmt->execute([$u1, $u2]);
    }
}
