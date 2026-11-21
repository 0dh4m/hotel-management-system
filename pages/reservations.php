<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../Database.php';
require_once '../Reservation.php';
require_once '../Chambre.php';
require_once '../Client.php';

$database = new Database();
$db = $database->getConnection();

$reservationObj = new Reservation($db);
$chambreObj = new Chambre($db);
$clientObj = new Client($db);

$message = "";

// 1. معالجة إضافة حجز جديد
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajouter_reservation'])) {
    $reservationObj->id_client = $_POST['id_client'];
    $reservationObj->id_chambre = $_POST['id_chambre'];
    $reservationObj->date_arrivee = $_POST['date_arrivee'];
    $reservationObj->date_depart = $_POST['date_depart'];

    if ($reservationObj->create()) {
        $message = "<div class='alert alert-success'><i class='fa-solid fa-circle-check me-1'></i> Réservation effectuée avec succès !</div>";
    } else {
        $message = "<div class='alert alert-danger'>Erreur lors de la réservation.</div>";
    }
}

// 2. معالجة تعديل حالة الحجز
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modifier_statut_res'])) {
    $id_res = $_POST['id_reservation'];
    $nouveau_statut = $_POST['statut_reservation'];

    if ($reservationObj->updateStatut($id_res, $nouveau_statut)) {
        $message = "<div class='alert alert-success'><i class='fa-solid fa-circle-check me-1'></i> Statut de la réservation mis à jour !</div>";
    } else {
        $message = "<div class='alert alert-danger'>Erreur lors de la mise à jour.</div>";
    }
}

// منطق الفلترة الذكي على حساب الـ Statut د الحجز
$filter = isset($_GET['statut']) ? $_GET['statut'] : 'Tous';

if ($filter == 'Confirmée') {
    $query = "SELECT r.*, c.nom_complet, ch.numero_chambre 
              FROM reservations r
              INNER JOIN clients c ON r.id_client = c.id_client
              INNER JOIN chambres ch ON r.id_chambre = ch.id_chambre
              WHERE r.statut_reservation = 'Confirmée' 
              ORDER BY r.date_arrivee DESC";
    $list_reservations = $db->prepare($query);
    $list_reservations->execute();
} elseif ($filter == 'Terminée') {
    $query = "SELECT r.*, c.nom_complet, ch.numero_chambre 
              FROM reservations r
              INNER JOIN clients c ON r.id_client = c.id_client
              INNER JOIN chambres ch ON r.id_chambre = ch.id_chambre
              WHERE r.statut_reservation = 'Terminée' 
              ORDER BY r.date_arrivee DESC";
    $list_reservations = $db->prepare($query);
    $list_reservations->execute();
} elseif ($filter == 'Annulée') {
    $query = "SELECT r.*, c.nom_complet, ch.numero_chambre 
              FROM reservations r
              INNER JOIN clients c ON r.id_client = c.id_client
              INNER JOIN chambres ch ON r.id_chambre = ch.id_chambre
              WHERE r.statut_reservation = 'Annulée' 
              ORDER BY r.date_arrivee DESC";
    $list_reservations = $db->prepare($query);
    $list_reservations->execute();
} else {
    $list_reservations = $reservationObj->readAll();
}

$total_res = $list_reservations->rowCount();

// جلب القوائم للفورم د الإضافة
$list_clients = $clientObj->readAll();
$list_chambres = $chambreObj->readAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Réservations</title>
    <link rel="icon" type="image/png" href="../images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background-color: #212529; color: white; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; display: block; padding: 15px; }
        .sidebar a:hover { background-color: #343a40; color: white; }
        .sidebar a.active { background-color: #343a40; color: white; border-left: 4px solid #ffc107; }
        .align-grid { align-items: flex-start; }
        
        .btn-outline-warning.active-filter { 
            background-color: #ffc107 !important; 
            color: #212529 !important; 
            font-weight: bold;
        }
        .btn-outline-warning {
            color: #212529;
            border-color: #ffc107;
        }
        .btn-outline-warning:hover {
            background-color: #ffc107;
            color: #212529;
        }
        
        /* [إضافة جديدة]: ستايل خاص لتصغير الأزرار وضمان بقائها في سطر واحد مضغوط */
        .btn-custom-sm {
            padding: 2px 6px !important;
            font-size: 0.78rem !important;
            line-height: 1.2 !important;
            border-radius: 4px !important;
        }
        .action-cell {
            white-space: nowrap; /* كايمنع الأزرار يهبطو لسطر جديد */
            width: 1%; /* كايجبر الكولوم يشد غي القياس د الأزرار بالظبط */
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 sidebar p-0 d-flex flex-column">
            <div class="p-3 text-center bg-dark">
                <h5><i class="fa-solid fa-hotel me-2 text-warning"></i>Maison D'hôtes</h5>
                <small class="text-muted">Espace Admin</small>
            </div>
            <div class="flex-grow-1">
                <a href="../index.php"><i class="fa-solid fa-chart-line me-2"></i> Dashboard</a>
                <a href="chambres.php"><i class="fa-solid fa-bed me-2"></i> Les Chambres</a>
                <a href="clients.php"><i class="fa-solid fa-users me-2"></i> Les Clients</a>
                <a href="reservations.php" class="active"><i class="fa-solid fa-calendar-days me-2"></i> Les Réservations</a>
            </div>
            <div class="p-3 border-top border-secondary text-center">
                <small class="d-block mb-2"><i class="fa-solid fa-user me-1 text-success"></i> <?php echo $_SESSION['nom']; ?></small>
                <a href="../logout.php" class="btn btn-sm btn-danger w-100 py-1 text-white"><i class="fa-solid fa-right-from-bracket me-1"></i> Déconnexion</a>
            </div>
        </div>

        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fa-solid fa-calendar-days me-2 text-warning"></i>Gestion des Réservations</h2>
                <span class="badge bg-secondary p-2">Date: <?php echo date('d/m/2026'); ?></span>
            </div>
            <hr>

            <?php echo $message; ?>

            <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm border">
                <div class="btn-group" role="group">
                    <a href="reservations.php?statut=Tous" class="btn btn-sm btn-outline-warning d-flex align-items-center gap-1 <?php echo $filter == 'Tous' ? 'active-filter' : ''; ?>">
                        <i class="fa-solid fa-list"></i> Toutes
                    </a>
                    <a href="reservations.php?statut=Confirmée" class="btn btn-sm btn-outline-warning d-flex align-items-center gap-1 <?php echo $filter == 'Confirmée' ? 'active-filter' : ''; ?>">
                        <i class="fa-solid fa-circle-check text-success"></i> Confirmées
                    </a>
                    <a href="reservations.php?statut=Terminée" class="btn btn-sm btn-outline-warning d-flex align-items-center gap-1 <?php echo $filter == 'Terminée' ? 'active-filter' : ''; ?>">
                        <i class="fa-solid fa-clock-rotate-left text-info"></i> Terminées
                    </a>
                    <a href="reservations.php?statut=Annulée" class="btn btn-sm btn-outline-warning d-flex align-items-center gap-1 <?php echo $filter == 'Annulée' ? 'active-filter' : ''; ?>">
                        <i class="fa-solid fa-circle-xmark text-danger"></i> Annulées
                    </a>
                </div>
                <span class="text-muted small">Filtre: <strong class="text-dark"><?php echo $filter; ?></strong> (<?php echo $total_res; ?>)</span>
            </div>

            <div class="row align-grid">
                
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-warning text-dark py-3">
                            <h5 class="mb-0"><i class="fa-solid fa-calendar-plus me-2"></i>Nouvelle Réservation</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Sélectionner un Client</label>
                                    <select name="id_client" class="form-select" required>
                                        <option value="">-- Choisir un client --</option>
                                        <?php while($c = $list_clients->fetch(PDO::FETCH_ASSOC)): ?>
                                            <option value="<?php echo $c['id_client']; ?>"><?php echo $c['nom_complet']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Sélectionner une Chambre</label>
                                    <select name="id_chambre" class="form-select" required>
                                        <option value="">-- Choisir une chambre --</option>
                                        <?php while($ch = $list_chambres->fetch(PDO::FETCH_ASSOC)): ?>
                                            <?php if($ch['statut'] == 'Disponible'): ?>
                                                <option value="<?php echo $ch['id_chambre']; ?>">Chambre N°<?php echo $ch['numero_chambre']; ?> (<?php echo $ch['prix_nuit']; ?> DH/Nuit)</option>
                                            <?php endif; ?>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Date d'arrivée</label>
                                    <input type="date" name="date_arrivee" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Date de départ</label>
                                    <input type="date" name="date_depart" class="form-control" required>
                                </div>
                                <button type="submit" name="ajouter_reservation" class="btn btn-warning w-100 text-dark fw-bold">Confirmer la réservation</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-warning text-dark py-3">
                            <h5 class="mb-0"><i class="fa-solid fa-list me-2"></i>Historique des Réservations</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Client</th>
                                            <th>Chambre</th>
                                            <th>Arrivée</th>
                                            <th>Départ</th>
                                            <th>Montant</th>
                                            <th>Statut</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($total_res > 0): ?>
                                            <?php while ($r = $list_reservations->fetch(PDO::FETCH_ASSOC)): ?>
                                                <tr>
                                                    <td><strong><?php echo $r['nom_complet']; ?></strong></td>
                                                    <td><span class="badge bg-light text-dark border">Chambre N°<?php echo $r['numero_chambre']; ?></span></td>
                                                    <td><?php echo $r['date_arrivee']; ?></td>
                                                    <td><?php echo $r['date_depart']; ?></td>
                                                    <td><strong class="text-success"><?php echo $r['montant_total']; ?> DH</strong></td>
                                                    <td>
                                                        <?php 
                                                        $st_badge = "bg-warning text-dark";
                                                        if($r['statut_reservation'] == 'Confirmée') $st_badge = "bg-success text-white";
                                                        if($r['statut_reservation'] == 'Terminée') $st_badge = "bg-info text-dark";
                                                        if($r['statut_reservation'] == 'Annulée') $st_badge = "bg-danger text-white";
                                                        ?>
                                                        <span class="badge <?php echo $st_badge; ?>"><?php echo $r['statut_reservation']; ?></span>
                                                    </td>
                                                    <td class="action-cell text-center">
                                                        <div class="d-inline-flex gap-1">
                                                            <a href="facture.php?id=<?php echo $r['id_reservation']; ?>" target="_blank" class="btn btn-custom-sm btn-outline-dark" title="Imprimer la facture">
                                                                <i class="fa-solid fa-print"></i> Facture
                                                            </a>
                                                            <button class="btn btn-custom-sm btn-outline-warning text-dark" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#statusModal<?php echo $r['id_reservation']; ?>">
                                                                <i class="fa-solid fa-user-pen"></i> Modifier
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>

                                                <div class="modal fade" id="statusModal<?php echo $r['id_reservation']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-sm">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h6 class="modal-title">Modifier le Statut</h6>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form method="POST" action="">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="id_reservation" value="<?php echo $r['id_reservation']; ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Statut du séjour</label>
                                                                        <select name="statut_reservation" class="form-select">
                                                                            <option value="Confirmée" <?php echo $r['statut_reservation'] == 'Confirmée' ? 'selected' : ''; ?>>Confirmée</option>
                                                                            <option value="Terminée" <?php echo $r['statut_reservation'] == 'Terminée' ? 'selected' : ''; ?>>Terminée (Check-out)</option>
                                                                            <option value="Annulée" <?php echo $r['statut_reservation'] == 'Annulée' ? 'selected' : ''; ?>>Annulée</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                                    <button type="submit" name="modifier_statut_res" class="btn btn-sm btn-warning text-dark fw-bold">Mettre à jour</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center p-4 text-muted">Aucune réservation trouvée avec ce statut.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>