<?php
class Database {
    private $host = "localhost";
    private $db_name = "fus_portal";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            $this->conn->set_charset("utf8mb4");
            
        } catch (Exception $exception) {
            die("Database connection error: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
function sanitize($input, $conn) {
    return $conn->real_escape_string(htmlspecialchars(trim($input)));
}
?>