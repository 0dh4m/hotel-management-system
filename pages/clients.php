<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../Database.php';
require_once '../Client.php';

$database = new Database();
$db = $database->getConnection();
$clientObj = new Client($db);

$message = "";

// 1. معالجة إضافة زبون جديد
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajouter_client'])) {
    $clientObj->nom_complet = $_POST['nom_complet'];
    $clientObj->cin = $_POST['cin'];
    $clientObj->telephone = $_POST['telephone'];
    $clientObj->email = $_POST['email'];

    if ($clientObj->create()) {
        $message = "<div class='alert alert-success'><i class='fa-solid fa-circle-check me-1'></i> Client ajouté avec succès !</div>";
    } else {
        $message = "<div class='alert alert-danger'>Erreur lors de l'ajout.</div>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modifier_client'])) {
    $clientObj->id_client = $_POST['id_client'];
    $clientObj->nom_complet = $_POST['nom_complet'];
    $clientObj->cin = $_POST['cin'];
    $clientObj->telephone = $_POST['telephone'];
    $clientObj->email = $_POST['email'];

    if ($clientObj->update()) {
        $message = "<div class='alert alert-success'><i class='fa-solid fa-circle-check me-1'></i> Client modifié avec succès !</div>";
    } else {
        $message = "<div class='alert alert-danger'>Erreur lors de la modification.</div>";
    }
}

// 3. آلية البحث (فقط عبر الاسم أو CIN)
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

if (!empty($search)) {
    $query = "SELECT * FROM clients WHERE 
              nom_complet LIKE :search OR 
              cin LIKE :search 
              ORDER BY id_client DESC";
    $stmt = $db->prepare($query);
    $search_param = "%" . $search . "%";
    $stmt->bindParam(':search', $search_param);
    $stmt->execute();
} else {
    $stmt = $clientObj->readAll();
}

$total_clients = $stmt->rowCount();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Clients</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link class="favicon" rel="icon" type="image/png" href="../images/logo.png">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background-color: #212529; color: white; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; display: block; padding: 15px; }
        .sidebar a:hover { background-color: #343a40; color: white; }
        .sidebar a.active { background-color: #343a40; color: white; border-left: 4px solid #198754; }
        .align-grid { align-items: flex-start; }
        
        /* ستايل مخصص لتصغير كارت الإحصائيات وجعلها متناسقة ومضغوطة */
        .mini-stat-card {
            padding: 8px 12px !important;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 sidebar p-0 d-flex flex-column">
            <div class="p-3 text-center bg-dark">
                <h5><i class="fa-solid fa-hotel me-2 text-success"></i>Maison D'hôtes</h5>
                <small class="text-muted">Espace Admin</small>
            </div>
            <div class="flex-grow-1">
                <a href="../index.php"><i class="fa-solid fa-chart-line me-2"></i> Dashboard</a>
                <a href="chambres.php"><i class="fa-solid fa-bed me-2"></i> Les Chambres</a>
                <a href="clients.php" class="active"><i class="fa-solid fa-users me-2"></i> Les Clients</a>
                <a href="reservations.php"><i class="fa-solid fa-calendar-days me-2"></i> Les Réservations</a>
            </div>
            <div class="p-3 border-top border-secondary text-center">
                <small class="d-block mb-2"><i class="fa-solid fa-user me-1 text-success"></i> <?php echo $_SESSION['nom']; ?></small>
                <a href="../logout.php" class="btn btn-sm btn-danger w-100 py-1 text-white"><i class="fa-solid fa-right-from-bracket me-1"></i> Déconnexion</a>
            </div>
        </div>

        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fa-solid fa-users me-2 text-success"></i>Gestion des Clients</h2>
                <span class="badge bg-secondary p-2">Date: <?php echo date('d/m/2026'); ?></span>
            </div>
            <hr>
            
            <?php echo $message; ?>

            <div class="row mt-4 align-grid">
                <div class="col-lg-4 mb-4">
                    
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-header bg-success text-white mini-stat-card d-flex justify-content-between align-items-center rounded">
                            <span class="fw-bold" style="font-size: 0.9rem;"><i class="fa-solid fa-chart-simple me-1"></i> Statistiques</span>
                            <span class="badge bg-white text-success fw-bold" style="font-size: 0.78rem; padding: 4px 8px;">
                                Total: <?php echo $total_clients; ?> Clients
                            </span>
                        </div>
                    </div>

                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-success text-white py-3">
                            <h5 class="mb-0"><i class="fa-solid fa-user-plus me-2"></i>Ajouter un Client</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Nom Complet</label>
                                    <input type="text" name="nom_complet" class="form-control" required placeholder="Ex: Ahmed Alami">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">CIN / Passeport</label>
                                    <input type="text" name="cin" class="form-control" required placeholder="Ex: AB123456">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Téléphone</label>
                                    <input type="tel" name="telephone" class="form-control" required placeholder="Ex: 0612345678">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" placeholder="Ex: ahmed@mail.com">
                                </div>
                                <button type="submit" name="ajouter_client" class="btn btn-success w-100">Ajouter le client</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    
                    <div class="mb-3">
                        <form method="GET" action="">
                            <div class="input-group shadow-sm">
                                <span class="input-group-text bg-white border-end-0 text-muted">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </span>
                                <input type="text" name="search" class="form-control border-start-0" 
                                       placeholder="Rechercher par nom ou CIN..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <?php if(!empty($search)): ?>
                                    <a href="clients.php" class="btn btn-outline-secondary d-flex align-items-center">
                                        <i class="fa-solid fa-xmark"></i>
                                    </a>
                                <?php endif; ?>
                                <button type="submit" class="btn btn-success px-4">Rechercher</button>
                            </div>
                        </form>
                    </div>

                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fa-solid fa-users me-2"></i>Liste des Clients</h5>
                            <?php if(!empty($search)): ?>
                                <span class="badge bg-light text-success fw-bold"><?php echo $total_clients; ?> Résultat(s)</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nom Complet</th>
                                            <th>CIN</th>
                                            <th>Téléphone</th>
                                            <th>Email</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($total_clients > 0): ?>
                                            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                                <tr>
                                                    <td><strong><?php echo $row['nom_complet']; ?></strong></td>
                                                    <td><span class="badge bg-light text-dark border"><?php echo $row['cin']; ?></span></td>
                                                    <td><?php echo $row['telephone']; ?></td>
                                                    <td><?php echo $row['email'] ? $row['email'] : '---'; ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-success" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editClientModal<?php echo $row['id_client']; ?>">
                                                            <i class="fa-solid fa-user-pen"></i> Modifier
                                                        </button>
                                                    </td>
                                                </tr>

                                                <div class="modal fade" id="editClientModal<?php echo $row['id_client']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Modifier le Client: <?php echo $row['nom_complet']; ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form method="POST" action="">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="id_client" value="<?php echo $row['id_client']; ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Nom Complet</label>
                                                                        <input type="text" name="nom_complet" class="form-control" value="<?php echo $row['nom_complet']; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">CIN / Passeport</label>
                                                                        <input type="text" name="cin" class="form-control" value="<?php echo $row['cin']; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Téléphone</label>
                                                                        <input type="tel" name="telephone" class="form-control" value="<?php echo $row['telephone']; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Email</label>
                                                                        <input type="email" name="email" class="form-control" value="<?php echo $row['email']; ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn border" data-bs-dismiss="modal">Fermer</button>
                                                                    <button type="submit" name="modifier_client" class="btn btn-success">Enregistrer</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center p-4 text-muted">
                                                    <i class="fa-solid fa-circle-exclamation me-1"></i> Aucun client ne correspond à votre recherche (Nom / CIN).
                                                </td>
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