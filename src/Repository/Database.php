<?php
namespace Synchly\Repository;

use PDO;
use PDOException;

class Database {
    private $host = 'localhost';
    private $db_name = 'synchly_db';
    private $username = 'root'; // XAMPPのデフォルト
    private $password = 'next123';     // XAMPPのデフォルト
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "接続エラー: " . $exception->getMessage();
        }
        return $this->conn;
    }
}