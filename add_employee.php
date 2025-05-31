<?php
session_start();

include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'responsable') {
    header("Location: login-employe.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $matricule = $_POST['matricule'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($nom) || empty($matricule) || empty($password) || empty($role)) {
        $error = "Tous les champs doivent être remplis.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE matricule = ?");
        $stmt->execute([$matricule]);
        if ($stmt->rowCount() > 0) {
            $error = "Cet matricule est déjà utilisé.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, matricule, mdp, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $prenom, $matricule, $password, $role]);
            $_SESSION['message'] = "Employé ajouté avec succès.";
            header('Location: add_employee.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un Employé - MyBank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form action="" method="POST" class="login-form">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h2 class="text-primary">Ajouter un Employé</h2>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger text-center"><?= $error ?></div>
                    <?php elseif (isset($_SESSION['message'])): ?>
                        <div class="alert alert-success text-center"><?= $_SESSION['message'] ?></div>
                        <?php unset($_SESSION['message']); ?>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" name="nom" required class="form-control" placeholder="Nom">
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" name="prenom" required class="form-control" placeholder="Prénom">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Matricule</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <input type="text" name="matricule" required class="form-control" placeholder="Matricule">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" required class="form-control" placeholder="Mot de passe">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Rôle</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                            <select name="role" required class="form-select">
                                <option value="">Sélectionnez un rôle</option>
                                <option value="caissier">Caissier</option>
                                <option value="charge_clientele">Chargé de clientèle</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-save me-2"></i>Ajouter l'employé
                    </button>

                    <div class="text-center">
                        <a href="admin_dashboard.php" class="text-primary text-decoration-none">
                            <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
