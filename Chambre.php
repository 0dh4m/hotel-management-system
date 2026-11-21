<?php
class Chambre {
    private $conn;
    private $table_name = "chambres";

    public $id_chambre;
    public $numero_chambre;
    public $type_chambre;
    public $prix_nuit;
    public $statut;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY numero_chambre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET numero_chambre=:numero, type_chambre=:type, prix_nuit=:prix, statut=:statut";
        
        $stmt = $this->conn->prepare($query);

        $this->numero_chambre = htmlspecialchars(strip_tags($this->numero_chambre));
        $this->type_chambre = htmlspecialchars(strip_tags($this->type_chambre));
        $this->prix_nuit = htmlspecialchars(strip_tags($this->prix_nuit));
        $this->statut = htmlspecialchars(strip_tags($this->statut));

        $stmt->bindParam(":numero", $this->numero_chambre);
        $stmt->bindParam(":type", $this->type_chambre);
        $stmt->bindParam(":prix", $this->prix_nuit);
        $stmt->bindParam(":statut", $this->statut);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
    
public function update() {
    $query = "UPDATE " . $this->table_name . " 
              SET type_chambre = :type, prix_nuit = :prix, statut = :statut 
              WHERE id_chambre = :id";
    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(":type", $this->type_chambre);
    $stmt->bindParam(":prix", $this->prix_nuit);
    $stmt->bindParam(":statut", $this->statut);
    $stmt->bindParam(":id", $this->id_chambre);

    return $stmt->execute();
}
}