<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        $this->host = getenv('HOTEL_DB_HOST') ?: "localhost";
        $this->db_name = getenv('HOTEL_DB_NAME') ?: "gestion_hotel";
        $this->username = getenv('HOTEL_DB_USER') ?: "root";
        $this->password = getenv('HOTEL_DB_PASS') ?: "";
        $this->port = getenv('HOTEL_DB_PORT') ?: "3306";
    }

    public function getConnection() {
        $this->conn = null;
        try {

            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password,
                [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"]
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Erreur de connexion : " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>