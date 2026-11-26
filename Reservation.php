<?php
class Reservation {
    private $conn;
    private $table_name = "reservations";

    public $id_reservation;
    public $id_client;
    public $id_chambre;
    public $date_arrivee;
    public $date_depart;
    public $montant_total;
    public $statut_reservation;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $datetime1 = new DateTime($this->date_arrivee);
        $datetime2 = new DateTime($this->date_depart);
        $interval = $datetime1->diff($datetime2);
        $nb_nuits = $interval->days;
        if($nb_nuits <= 0) $nb_nuits = 1;

        // Reject invalid or overlapping stays for the same room.
        if ($datetime2 <= $datetime1) {
            return false;
        }

        $query_conflict = "SELECT COUNT(*) FROM " . $this->table_name . "
                           WHERE id_chambre = :id_chambre
                             AND statut_reservation NOT IN ('Annulée', 'Terminée')
                             AND date_arrivee < :date_depart
                             AND date_depart > :date_arrivee";
        $stmt_conflict = $this->conn->prepare($query_conflict);
        $stmt_conflict->bindParam(":id_chambre", $this->id_chambre);
        $stmt_conflict->bindParam(":date_depart", $this->date_depart);
        $stmt_conflict->bindParam(":date_arrivee", $this->date_arrivee);
        $stmt_conflict->execute();
        if ((int)$stmt_conflict->fetchColumn() > 0) {
            return false;
        }

        $query_prix = "SELECT prix_nuit FROM chambres WHERE id_chambre = :id_chambre LIMIT 1";
        $stmt_prix = $this->conn->prepare($query_prix);
        $stmt_prix->bindParam(":id_chambre", $this->id_chambre);
        $stmt_prix->execute();
        $row = $stmt_prix->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        $prix_nuit = $row['prix_nuit'];

        $this->montant_total = $nb_nuits * $prix_nuit;

        try {
            $this->conn->beginTransaction();

            $query = "INSERT INTO " . $this->table_name . " 
                      SET id_client=:id_client, id_chambre=:id_chambre, 
                          date_arrivee=:date_arrivee, date_depart=:date_depart, 
                          montant_total=:montant_total";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id_client", $this->id_client);
            $stmt->bindParam(":id_chambre", $this->id_chambre);
            $stmt->bindParam(":date_arrivee", $this->date_arrivee);
            $stmt->bindParam(":date_depart", $this->date_depart);
            $stmt->bindParam(":montant_total", $this->montant_total);
            $stmt->execute();

            $query_update_chambre = "UPDATE chambres SET statut = 'Occupée' WHERE id_chambre = :id_chambre";
            $stmt_update = $this->conn->prepare($query_update_chambre);
            $stmt_update->bindParam(":id_chambre", $this->id_chambre);
            $stmt_update->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    public function readAll() {
        $query = "SELECT r.*, c.nom_complet, ch.numero_chambre 
                  FROM " . $this->table_name . " r
                  INNER JOIN clients c ON r.id_client = c.id_client
                  INNER JOIN chambres ch ON r.id_chambre = ch.id_chambre
                  ORDER BY r.date_arrivee DESC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function updateStatut($id_res, $nouveau_statut) {
        $query = "UPDATE " . $this->table_name . " SET statut_reservation = :statut WHERE id_reservation = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":statut", $nouveau_statut);
        $stmt->bindParam(":id", $id_res);

        if($stmt->execute()) {
            if($nouveau_statut == 'Annulée' || $nouveau_statut == 'Terminée') {
                $query_ch = "UPDATE chambres SET statut = 'Disponible' WHERE id_chambre = (SELECT id_chambre FROM reservations WHERE id_reservation = :id)";
                $stmt_ch = $this->conn->prepare($query_ch);
                $stmt_ch->bindParam(":id", $id_res);
                $stmt_ch->execute();
            }
            return true;
        }
        return false;
    }
}
?>