<?php
class Database {
    private $host = "localhost";
    private $db_name = "gestion_hotel";
    private $username = "root";
    private $password = "";
	private $port = "3306";
    public $conn;

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