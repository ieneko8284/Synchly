<?php
namespace Synchly\Repository;

use PDO;
use PDOException;

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_DATABASE') ?: 'synchly_db';
        $this->username = getenv('DB_USERNAME') ?: 'root'; // XAMPPのデフォルト
        $this->password = getenv('DB_PASSWORD') ?: 'next123'; // XAMPPのデフォルト
    }

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
            $this->conn->exec("SET NAMES utf8mb4");
        } catch(PDOException $exception) {
            echo "接続エラー: " . $exception->getMessage();
        }
        return $this->conn;
    }
}