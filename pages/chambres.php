<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../Database.php';
require_once '../Chambre.php';

$database = new Database();
$db = $database->getConnection();
$chambreObj = new Chambre($db);

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajouter_chambre'])) {
    $chambreObj->numero_chambre = $_POST['numero_chambre'];
    $chambreObj->type_chambre = $_POST['type_chambre'];
    $chambreObj->prix_nuit = $_POST['prix_nuit'];
    $chambreObj->statut = $_POST['statut'];

    if ($chambreObj->create()) {
        $message = "<div class='alert alert-success'><i class='fa-solid fa-circle-check me-1'></i> Chambre ajoutée avec succès !</div>";
    } else {
        $message = "<div class='alert alert-danger'>Erreur lors de l'ajout.</div>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modifier_chambre'])) {
    $chambreObj->id_chambre = $_POST['id_chambre'];
    $chambreObj->type_chambre = $_POST['type_chambre'];
    $chambreObj->prix_nuit = $_POST['prix_nuit'];
    $chambreObj->statut = $_POST['statut'];

    if ($chambreObj->update()) {
        $message = "<div class='alert alert-success'><i class='fa-solid fa-circle-check me-1'></i> Chambre modifiée avec succès !</div>";
    } else {
        $message = "<div class='alert alert-danger'>Erreur lors de la modification.</div>";
    }
}

$filter = isset($_GET['statut']) ? $_GET['statut'] : 'Tous';

if ($filter == 'Disponible') {
    $query = "SELECT * FROM chambres WHERE statut = 'Disponible' ORDER BY numero_chambre ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
} elseif ($filter == 'Occupée') {
    $query = "SELECT * FROM chambres WHERE statut = 'Occupée' ORDER BY numero_chambre ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
} elseif ($filter == 'Nettoyage') {
    $query = "SELECT * FROM chambres WHERE statut = 'En nettoyage' ORDER BY numero_chambre ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
} else {
    $stmt = $chambreObj->readAll();
}

$total_chambres = $stmt->rowCount();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Chambres</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link class="favicon" rel="icon" type="image/png" href="../images/logo.png">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background-color: #212529; color: white; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; display: block; padding: 15px; }
        .sidebar a:hover { background-color: #343a40; color: white; }
        .sidebar a.active { background-color: #343a40; color: white; border-left: 4px solid #0d6efd; }
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
                <a href="../index.php"><i class="fa-solid fa-chart-line me-2"></i> Dashboard</a>
                <a href="chambres.php" class="active"><i class="fa-solid fa-bed me-2"></i> Les Chambres</a>
                <a href="clients.php"><i class="fa-solid fa-users me-2"></i> Les Clients</a>
                <a href="reservations.php"><i class="fa-solid fa-calendar-days me-2"></i> Les Réservations</a>
            </div>
            <div class="p-3 border-top border-secondary text-center">
                <small class="d-block mb-2"><i class="fa-solid fa-user me-1 text-success"></i> <?php echo $_SESSION['nom']; ?></small>
                <a href="../logout.php" class="btn btn-sm btn-danger w-100 py-1 text-white"><i class="fa-solid fa-right-from-bracket me-1"></i> Déconnexion</a>
            </div>
        </div>

        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fa-solid fa-bed me-2 text-primary"></i>Gestion des Chambres</h2>
                <span class="badge bg-secondary p-2">Date: <?php echo date('d/m/2026'); ?></span>
            </div>
            <hr>
            
            <?php echo $message; ?>

            <div class="d-flex justify-content-between align-items-center my-3 bg-white p-3 rounded shadow-sm">
                <div class="btn-group" role="group">
                    <a href="chambres.php?statut=Tous" class="btn btn-sm <?php echo $filter == 'Tous' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="fa-solid fa-list me-1"></i> Toutes
                    </a>
                    <a href="chambres.php?statut=Disponible" class="btn btn-sm <?php echo $filter == 'Disponible' ? 'btn-success' : 'btn-outline-success'; ?>">
                        <i class="fa-solid fa-circle-check me-1"></i> Disponibles
                    </a>
                    <a href="chambres.php?statut=Occupée" class="btn btn-sm <?php echo $filter == 'Occupée' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                        <i class="fa-solid fa-circle-xmark me-1"></i> Occupées
                    </a>
                    <a href="chambres.php?statut=Nettoyage" class="btn btn-sm <?php echo $filter == 'Nettoyage' ? 'btn-warning text-dark' : 'btn-outline-warning text-dark'; ?>">
                        <i class="fa-solid fa-broom me-1"></i> En nettoyage
                    </a>
                </div>
                <span class="text-muted small">Filtre: <strong><?php echo $filter; ?></strong> (<?php echo $total_chambres; ?>)</span>
            </div>

            <div class="row mt-4">
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fa-solid fa-plus me-2"></i>Ajouter une Chambre</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Numéro de Chambre</label>
                                    <input type="text" name="numero_chambre" class="form-control" required placeholder="Ex: 101">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Type de Chambre</label>
                                    <select name="type_chambre" class="form-select" required>
                                        <option value="Single">Single</option>
                                        <option value="Double">Double</option>
                                        <option value="Suite">Suite</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Prix par Nuit (DH)</label>
                                    <input type="number" step="0.01" name="prix_nuit" class="form-control" required placeholder="Ex: 450">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Statut initial</label>
                                    <select name="statut" class="form-select">
                                        <option value="Disponible">Disponible</option>
                                        <option value="Occupée">Occupée</option>
                                        <option value="En nettoyage">En nettoyage</option>
                                    </select>
                                </div>
                                <button type="submit" name="ajouter_chambre" class="btn btn-primary w-100">Ajouter la chambre</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fa-solid fa-list me-2"></i>Liste des Chambres</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Numéro</th>
                                            <th>Type</th>
                                            <th>Prix / Nuit</th>
                                            <th>Statut</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if($total_chambres > 0): ?>
                                            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                                <tr>
                                                    <td><strong>Chambre N° <?php echo $row['numero_chambre']; ?></strong></td>
                                                    <td><?php echo $row['type_chambre']; ?></td>
                                                    <td><?php echo $row['prix_nuit']; ?> DH</td>
                                                    <td>
                                                        <?php 
                                                        $badge = "bg-success";
                                                        if($row['statut'] == "Occupée") $badge = "bg-danger";
                                                        if($row['statut'] == "En nettoyage") $badge = "bg-warning text-dark";
                                                        ?>
                                                        <span class="badge <?php echo $badge; ?>"><?php echo $row['statut']; ?></span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-secondary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editModal<?php echo $row['id_chambre']; ?>">
                                                            <i class="fa-solid fa-user-pen"></i> Modifier
                                                        </button>
                                                    </td>
                                                </tr>

                                                <div class="modal fade" id="editModal<?php echo $row['id_chambre']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Modifier la Chambre N° <?php echo $row['numero_chambre']; ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form method="POST" action="">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="id_chambre" value="<?php echo $row['id_chambre']; ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Type de Chambre</label>
                                                                        <select name="type_chambre" class="form-select">
                                                                            <option value="Single" <?php echo $row['type_chambre'] == 'Single' ? 'selected' : ''; ?>>Single</option>
                                                                            <option value="Double" <?php echo $row['type_chambre'] == 'Double' ? 'selected' : ''; ?>>Double</option>
                                                                            <option value="Suite" <?php echo $row['type_chambre'] == 'Suite' ? 'selected' : ''; ?>>Suite</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Prix par Nuit (DH)</label>
                                                                        <input type="number" step="0.01" name="prix_nuit" class="form-control" value="<?php echo $row['prix_nuit']; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Statut</label>
                                                                        <select name="statut" class="form-select">
                                                                            <option value="Disponible" <?php echo $row['statut'] == 'Disponible' ? 'selected' : ''; ?>>Disponible</option>
                                                                            <option value="Occupée" <?php echo $row['statut'] == 'Occupée' ? 'selected' : ''; ?>>Occupée</option>
                                                                            <option value="En nettoyage" <?php echo $row['statut'] == 'En nettoyage' ? 'selected' : ''; ?>>En nettoyage</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                                    <button type="submit" name="modifier_chambre" class="btn btn-success">Enregistrer</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center p-4 text-muted">Aucune chambre trouvée.</td>
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