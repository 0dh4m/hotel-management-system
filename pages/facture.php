<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../Database.php';

$database = new Database();
$db = $database->getConnection();
$id_res = $_GET['id'];

// جلب كل تفاصيل الحجز والكليان والبيت ف دقة
$query = "SELECT r.*, c.nom_complet, c.telephone, ch.numero_chambre, ch.type_chambre, ch.prix_nuit 
          FROM reservations r
          INNER JOIN clients c ON r.id_client = c.id_client
          INNER JOIN chambres ch ON r.id_chambre = ch.id_chambre
          WHERE r.id_reservation = :id_res";

$stmt = $db->prepare($query);
$stmt->bindParam(':id_res', $id_res);
$stmt->execute();
$facture = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$facture) {
    echo "Facture introuvable.";
    exit();
}

// حساب عدد الليالي
$datetime1 = new DateTime($facture['date_arrivee']);
$datetime2 = new DateTime($facture['date_depart']);
$interval = $datetime1->diff($datetime2);
$nb_nuits = $interval->days == 0 ? 1 : $interval->days;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture N° REC-<?php echo $facture['id_reservation']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #fff; font-family: 'Arial', sans-serif; color: #333; }
        .invoice-box { padding: 40px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.05); margin: 30px auto; max-width: 800px; }
        .invoice-header { border-bottom: 3px solid #0d6efd; padding-bottom: 20px; margin-bottom: 30px; }
        @media print {
            .no-print { display: none; }
            .invoice-box { border: none; box-shadow: none; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="text-center mt-4 no-print">
        <button onclick="window.print();" class="btn btn-success"><i class="fa-solid fa-print"></i> Imprimer la Facture</button>
        <button onclick="window.close();" class="btn btn-secondary">Fermer</button>
    </div>

    <div class="invoice-box">
        <div class="invoice-header d-flex justify-content-between align-items-center">
            <div>
                 <h5><i class="fa-solid fa-hotel me-2 text-success"></i>Maison D'hôtes</h5>
                <p class="text-muted small mb-0">Rabat, Maroc | Tél: +212 600 000000</p>
            </div>
            <div class="text-end">
                <h4 class="mb-1">FACTURE</h4>
                <p class="mb-0 small"><strong>N°:</strong> REC-2026-<?php echo $facture['id_reservation']; ?></p>
                <p class="mb-0 small"><strong>Date:</strong> <?php echo date('d/m/2026'); ?></p>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-6">
                <h6 class="text-muted text-uppercase font-weight-bold">Facturé à:</h6>
                <h5><strong><?php echo $facture['nom_complet']; ?></strong></h5>
                <p class="text-muted small mb-0"><strong>Téléphone:</strong> <?php echo $facture['telephone']; ?></p>
            </div>
            <div class="col-6 text-end">
                <h6 class="text-muted text-uppercase font-weight-bold">Détails Séjour:</h6>
                <p class="mb-1 small"><strong>Date d'arrivée:</strong> <?php echo $facture['date_arrivee']; ?></p>
                <p class="mb-1 small"><strong>Date de départ:</strong> <?php echo $facture['date_depart']; ?></p>
                <p class="mb-0 small"><strong>Statut:</strong> <span class="badge bg-light text-dark border"><?php echo $facture['statut_reservation']; ?></span></p>
            </div>
        </div>

        <table class="table table-bordered align-middle">
            <thead class="table-primary text-white">
                <tr>
                    <th>Désignation Service</th>
                    <th class="text-center">Prix / Nuit</th>
                    <th class="text-center">Nombre de Nuits</th>
                    <th class="text-end">Total HT</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Hébergement - Chambre N°<?php echo $facture['numero_chambre']; ?></strong><br>
                        <small class="text-muted">Type: <?php echo $facture['type_chambre']; ?></small>
                    </td>
                    <td class="text-center"><?php echo $facture['prix_nuit']; ?> DH</td>
                    <td class="text-center"><?php echo $nb_nuits; ?></td>
                    <td class="text-end"><?php echo $facture['prix_nuit'] * $nb_nuits; ?> DH</td>
                </tr>
                <tr class="table-light">
                    <td colspan="3" class="text-end"><strong>Total Général Net:</strong></td>
                    <td class="text-end text-primary h5 mb-0"><strong><?php echo $facture['montant_total']; ?> DH</strong></td>
                </tr>
            </tbody>
        </table>

        <div class="mt-5 pt-4 text-center border-top text-muted small">
            <p class="mb-1">Merci pour votre visite dans notre Maison d'Hôtes !</p>
            <p class="mb-0">Logiciel de Gestion V1.0 - Édité par Adham Cherkaoui</p>
        </div>
    </div>
</div>

<script>
    window.onload = function() {
        window.print();
    }
</script>
</body>
</html>