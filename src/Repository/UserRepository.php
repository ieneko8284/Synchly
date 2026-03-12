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
}
