<?php
session_start();

if (isset($_SESSION['error_message'])) {
    echo '<div class="error-message">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion Employé - MyBank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <form action="auth.php" method="POST" class="login-form">
                    <div class="text-center mb-4">
                        <i class="fas fa-briefcase text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h2 class="text-primary">Connexion Employé</h2>
                    </div>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger text-center">
                            <?= $_GET['error']?>
                             <!--  htmlspecialchars($_GET['error']) -->
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="matricule" class="form-label">Matricule</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <input type="text" name="matricule" id="matricule" required class="form-control" placeholder="Entrez votre matricule">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" id="password" required class="form-control" placeholder="Entrez votre mot de passe">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mb-3">Se connecter</button>
                    
                    <div class="text-center">
                        <p class="mb-0">Vous êtes un client? <a href="login-client.php" class="text-primary text-decoration-none">Connexion Client</a></p>
                    </div>
                    <div class="text-center">
                        <a href="index.php" class="text-primary text-decoration-none">
                            <i class="fas fa-arrow-left me-2"></i>Retour à la page d'accueil
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
