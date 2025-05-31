<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricule = $_POST['matricule'] ?? '';
    $password = $_POST['password'] ?? '';
    $numero = $_POST['numero'] ?? '';
    $code = $_POST['code_secret'] ?? '';

    // Authentication for employe login
    if (!empty($matricule) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE matricule = ?");
        $stmt->execute([$matricule]);
        $user = $stmt->fetch();

        if ($user && $password == $user['mdp']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['prenom'] = $user['prenom'];
            $_SESSION['success_message'] = "Connexion réussie.";
            header('Location: dashboard_redirect.php');
            exit;
        } else {
            $_SESSION['error_message'] = "Identifiant ou mot de passe introuvable";
            header("Location: login-employe.php");
            exit;
        }
    }

    // Authentication for bank account login
    if (!empty($numero) && !empty($code)) {
        $sql = "SELECT * FROM comptes_bancaires WHERE numero_compte = :num AND code_secret = :code";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['num' => $numero, 'code' => $code]);

        $compte = $stmt->fetch();
        if ($compte) {
            $_SESSION['client'] = $compte; // contains the account details
            $_SESSION['success_message'] = "Connexion réussie.";
            header("Location: client_dashboard.php");
            exit;
        } else {
            echo "Connexion échouée.";
        }
    }
}

