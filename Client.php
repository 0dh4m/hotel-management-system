<?php
class Client {
    private $conn;
    private $table_name = "clients";

    public $id_client;
    public $nom_complet;
    public $cin;
    public $telephone;
    public $email;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nom_complet=:nom, cin=:cin, telephone=:telephone, email=:email";
        
        $stmt = $this->conn->prepare($query);

        $this->nom_complet = htmlspecialchars(strip_tags($this->nom_complet));
        $this->cin = htmlspecialchars(strip_tags($this->cin));
        $this->telephone = htmlspecialchars(strip_tags($this->telephone));
        $this->email = htmlspecialchars(strip_tags($this->email));

        $stmt->bindParam(":nom", $this->nom_complet);
        $stmt->bindParam(":cin", $this->cin);
        $stmt->bindParam(":telephone", $this->telephone);
        $stmt->bindParam(":email", $this->email);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY nom_complet ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>