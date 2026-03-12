<?php
namespace Synchly\Repository;

use PDO;

class UserRepository {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 引数に自分の性別（myGender）を受け取るように変更
    public function findOppositeGender($myGender) {
        // 1（男）なら 2（女）を、2（女）なら 1（男）をターゲットにする
        $targetGender = ($myGender == 1) ? 2 : 1;

        $query = "SELECT id, username, bio FROM users WHERE gender = :target";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':target', $targetGender, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}