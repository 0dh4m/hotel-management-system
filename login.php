<?php
session_start();
require_once 'Database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();

    $username = htmlspecialchars(strip_tags($_POST['username']));
    $password = $_POST['password'];

    $query = "SELECT * FROM utilisateurs WHERE username = :username LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($password, $user['password'])) {
            // تسجيل معلومات الجلسة (Session)
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nom'] = $user['nom'];

            header("Location: index.php");
            exit();
        } else {
            $error = "Mot de passe incorrect.";
        }
    } else {
        $error = "Utilisateur non trouvé.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Maison d'Hôtes</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #212529 0%, #343a40 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card-login { border: none; border-radius: 15px; width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .btn-login { background-color: #0d6efd; border: none; }
        .btn-login:hover { background-color: #0b5ed7; }
    </style>
</head>
<body>

<div class="card card-login bg-white p-4">
    <div class="text-center mb-4">
        <i class="fa-solid fa-hotel fa-3x text-primary mb-2"></i>
        <h4 class="text-dark">Maison d'Hôtes</h4>
        <small class="text-muted">Espace d'administration</small>
    </div>

    <?php if(!empty($error)): ?>
        <div class="alert alert-danger text-center py-2"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label"><i class="fa-solid fa-user me-2 text-secondary"></i>Nom d'utilisateur</label>
            <input type="text" name="username" class="form-control" required placeholder="Ex: admin">
        </div>
        <div class="mb-3">
            <label class="form-label"><i class="fa-solid fa-lock me-2 text-secondary"></i>Mot de passe</label>
            <input type="password" name="password" class="form-control" required placeholder="••••••••">
        </div>
        <button type="submit" class="btn btn-primary w-100 btn-login text-white py-2 mt-2">Se connecter</button>
    </form>
</div>

</body>
</html>