<?php
class Database {
    private $host = "localhost";
    private $db_name = "tourgrid";
    private $username = "root";
    private $password = "";
    private $conn;

    // Get database connection
    // Make sure error reporting is enabled for debugging
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>
