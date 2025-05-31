<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'caissier') {
    header("Location: login-employe.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $adresse = $_POST['adresse'];
    $date_naissance = $_POST['date_naissance'];
    $cin = $_POST['cin'];
    
    if (empty($nom) || empty($prenom) || empty($email) || empty($telephone) || empty($adresse) || empty($date_naissance) || empty($cin)) {
        $_SESSION['error_message'] = "Tous les champs sont obligatoires.";
        header("Location: caissier_dashboard.php");
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE cin = ?");
    $stmt->execute([$cin]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error_message'] = "Un client avec ce CIN existe déjà.";
        header("Location: caissier_dashboard.php");
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO clients (nom, prenom, email, telephone, adresse, date_naissance, cin, date_inscription) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$nom, $prenom, $email, $telephone, $adresse, $date_naissance, $cin]);
        
        $_SESSION['success_message'] = "Client créé avec succès!";
        header("Location: caissier_dashboard.php");
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de la création du client: " . $e->getMessage();
        header("Location: caissier_dashboard.php");
        exit();
    }
}
?>