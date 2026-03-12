<?php
namespace Synchly\Controller;

use Synchly\Repository\Database;
use Synchly\Repository\UserRepository;

class UserController {
    public function getUsersApi() {
        // 本来はセッションから自分の情報を取る
        // $_SESSION['user_gender'] が 1（男）だと仮定
        $myGender = $_SESSION['user_gender'] ?? 1; 

        $database = new Database();
        $db = $database->getConnection();
        $repository = new UserRepository($db);

        // 異性のみを取得
        $users = $repository->findOppositeGender($myGender);

        header('Content-Type: application/json');
        echo json_encode($users);
        exit;
    }
}