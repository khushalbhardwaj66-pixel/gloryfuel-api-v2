<?php
// config/database.php
class Database {
    private $host = "gateway01.ap-southeast-1.prod.aws.tidbcloud.com";
    private $db_name = "test";
    private $username = "u8awf4fFprGi4MV.root";
    private $password = "4Db2ddKV2qz8x5cl";
    private $port = "4000";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
            ]);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            header('Content-Type: application/json');
            echo json_encode(["error" => "Connection error: " . $exception->getMessage()]);
            exit;
        }
        return $this->conn;
    }
}
?>
