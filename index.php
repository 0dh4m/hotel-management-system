<?php
session_start();

// 1. حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. نعيطو للملفات د قاعدة البيانات والكلاسات
require_once 'Database.php';
require_once 'Chambre.php';
require_once 'Client.php';
require_once 'Reservation.php';

$database = new Database();
$db = $database->getConnection();

// معالجة طلب الـ Check-out المباشر من الـ Dashboard
if (isset($_GET['action']) && $_GET['action'] == 'checkout' && isset($_GET['id_res']) && isset($_GET['id_ch'])) {
    $id_reservation = $_GET['id_res'];
    $id_chambre = $_GET['id_ch'];
    
    try {
        $db->beginTransaction();
        
        // أ. تحديث حالة الحجز إلى "Terminée"
        $query_update_res = "UPDATE reservations SET statut_reservation = 'Terminée' WHERE id_reservation = :id_res";
        $stmt_res = $db->prepare($query_update_res);
        $stmt_res->bindParam(':id_res', $id_reservation);
        $stmt_res->execute();
        
        // b. تحديث حالة الغرفة المرتبطة بهذا الحجز إلى "Disponible"
        $query_update_ch = "UPDATE chambres SET statut = 'Disponible' WHERE id_chambre = :id_ch";
        $stmt_ch = $db->prepare($query_update_ch);
        $stmt_ch->bindParam(':id_ch', $id_chambre);
        $stmt_ch->execute();
        
        $db->commit();
        header("Location: index.php?status=success");
        exit();
        
    } catch (Exception $e) {
        $db->rollBack();
        header("Location: index.php?status=error");
        exit();
    }
}

$chambreObj = new Chambre($db);
$clientObj = new Client($db);
$reservationObj = new Reservation($db);

$total_chambres = $chambreObj->readAll()->rowCount();
$total_clients = $clientObj->readAll()->rowCount();
$total_reservations = $reservationObj->readAll()->rowCount();

// 3. [تعديل هنا]: حساب إجمالي الأرباح فقط للحجوزات المنتهية (Terminée)
$query_gain = "SELECT SUM(montant_total) AS total_gagne FROM reservations WHERE statut_reservation = 'Terminée'";
$stmt_gain = $db->prepare($query_gain);
$stmt_gain->execute();
$row_gain = $stmt_gain->fetch(PDO::FETCH_ASSOC);
$total_gagne = $row_gain['total_gagne'] ? $row_gain['total_gagne'] : 0;

// 4. جلب الحجوزات اللي غاتسالي اليوم (Check-out)
$query_today = "SELECT r.*, c.nom_complet, ch.id_chambre, ch.numero_chambre, c.telephone 
                FROM reservations r
                INNER JOIN clients c ON r.id_client = c.id_client
                INNER JOIN chambres ch ON r.id_chambre = ch.id_chambre
                WHERE r.date_depart = CURDATE() AND r.statut_reservation = 'Confirmée'";
$stmt_today = $db->prepare($query_today);
$stmt_today->execute();
$total_depart_today = $stmt_today->rowCount();

// 5. جلب آخر 5 زبناء مضافين للسيستيم
$query_recent_clients = "SELECT * FROM clients ORDER BY id_client DESC LIMIT 5";
$stmt_recent_clients = $db->prepare($query_recent_clients);
$stmt_recent_clients->execute();
$total_recent_clients = $stmt_recent_clients->rowCount();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Maison d'Hôtes - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link class="favicon" rel="icon" type="image/png" href="images/logo.png">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background-color: #212529; color: white; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; display: block; padding: 15px; }
        .sidebar a:hover { background-color: #343a40; color: white; }
        .sidebar a.active { background-color: #343a40; color: white; border-left: 4px solid #0d6efd; }
        .card-stat { border: none; border-radius: 10px; transition: transform 0.2s; }
        .card-stat:hover { transform: translateY(-5px); }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 sidebar p-0 d-flex flex-column">
            <div class="p-3 text-center bg-dark">
                <h5><i class="fa-solid fa-hotel me-2 text-primary"></i>Maison D'hôtes</h5>
                <small class="text-muted">Espace Admin</small>
            </div>
            <div class="flex-grow-1">
                <a href="index.php" class="active"><i class="fa-solid fa-chart-line me-2"></i> Dashboard</a>
                <a href="pages/chambres.php"><i class="fa-solid fa-bed me-2"></i> Les Chambres</a>
                <a href="pages/clients.php"><i class="fa-solid fa-users me-2"></i> Les Clients</a>
                <a href="pages/reservations.php"><i class="fa-solid fa-calendar-days me-2"></i> Les Réservations</a>
            </div>
            <div class="p-3 border-top border-secondary text-center">
                <small class="d-block mb-2"><i class="fa-solid fa-user me-1 text-success"></i> <?php echo $_SESSION['nom']; ?></small>
                <a href="logout.php" class="btn btn-sm btn-danger w-100 py-1 text-white"><i class="fa-solid fa-right-from-bracket me-1"></i> Déconnexion</a>
            </div>
        </div>

        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-hotel text-primary" style="font-size: 1.8rem;"></i>
                    Tableau de Bord
                </h2>
                <span class="badge bg-secondary p-2">Date: <?php echo date('d/m/2026'); ?></span>
            </div>
            
            <hr>

            <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-1"></i> <strong>Check-out réussi!</strong> Chambre libérée avec succès وجرى حساب الأرباح تلقائياً.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row mt-4">
                <div class="col-md-3 mb-4">
                    <div class="card card-stat bg-primary text-white shadow-sm h-100">
                        <div class="card-body d-flex justify-content-between align-items-center p-4">
                            <div>
                                <h6 class="card-title text-uppercase mb-2" style="font-size: 0.8rem; opacity: 0.9;">Total Chambres</h6>
                                <h2 class="display-6 font-weight-bold mb-0"><?php echo $total_chambres; ?></h2>
                            </div>
                            <i class="fa-solid fa-bed fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card card-stat bg-success text-white shadow-sm h-100">
                        <div class="card-body d-flex justify-content-between align-items-center p-4">
                            <div>
                                <h6 class="card-title text-uppercase mb-2" style="font-size: 0.8rem; opacity: 0.9;">Total Clients</h6>
                                <h2 class="display-6 font-weight-bold mb-0"><?php echo $total_clients; ?></h2>
                            </div>
                            <i class="fa-solid fa-users fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card card-stat bg-warning text-white shadow-sm h-100">
                        <div class="card-body d-flex justify-content-between align-items-center p-4">
                            <div>
                                <h6 class="card-title text-uppercase mb-2" style="font-size: 0.8rem; opacity: 0.9;">Réservations</h6>
                                <h2 class="display-6 font-weight-bold mb-0"><?php echo $total_reservations; ?></h2>
                            </div>
                            <i class="fa-solid fa-calendar-check fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card card-stat bg-danger text-white shadow-sm h-100">
                        <div class="card-body d-flex justify-content-between align-items-center p-4">
                            <div>
                                <h6 class="card-title text-uppercase mb-2" style="font-size: 0.75rem; opacity: 0.9; white-space: nowrap;">Chiffre d'Affaires</h6>
                                <h2 class="display-6 font-weight-bold mb-0" style="font-size: 1.8rem;">
                                    <?php echo number_format($total_gagne, 0, '.', ' '); ?><span style="font-size: 1.2rem; font-weight: 300; margin-left: 5px; opacity: 0.85;">DH</span>
                                </h2>
                            </div>
                            <i class="fa-solid fa-wallet fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4 shadow-sm border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-bell me-2"></i> Départs Attendus Aujourd'hui (Check-out)</h5>
                    <span class="badge bg-white text-primary fw-bold"><?php echo $total_depart_today; ?> Alerte(s)</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Chambre</th>
                                    <th>Téléphone</th>
                                    <th>Date d'arrivée</th>
                                    <th>Action Instantanée</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($total_depart_today > 0): ?>
                                    <?php while ($row_today = $stmt_today->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><strong><?php echo $row_today['nom_complet']; ?></strong></td>
                                            <td><span class="badge bg-info text-dark">Chambre N°<?php echo $row_today['numero_chambre']; ?></span></td>
                                            <td><?php echo $row_today['telephone']; ?></td>
                                            <td><?php echo $row_today['date_arrivee']; ?></td>
                                            <td>
                                                <a href="index.php?action=checkout&id_res=<?php echo $row_today['id_reservation']; ?>&id_ch=<?php echo $row_today['id_chambre']; ?>" 
                                                   class="btn btn-sm btn-primary text-white" 
                                                   onclick="return confirm('Voulez-vous vraiment libérer cette chambre ?');">
                                                    <i class="fa-solid fa-key me-1"></i> Libérer la chambre
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center p-4 text-muted">Aucun départ prévu pour aujourd'hui.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>