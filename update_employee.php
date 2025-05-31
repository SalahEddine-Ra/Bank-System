<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'responsable') {
    header("Location: login-employe.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? '';
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $matricule = $_POST['matricule'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($employee_id) || empty($nom) || empty($matricule) || empty($role)) {
        $_SESSION['error_message'] = "Tous les champs obligatoires doivent être remplis.";
        header("Location: admin_dashboard.php");
        exit();
    }

    try {
        // Check if matricule is already used by another employee
        $stmt = $pdo->prepare("SELECT id FROM users WHERE matricule = ? AND id != ?");
        $stmt->execute([$matricule, $employee_id]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['error_message'] = "Ce matricule est déjà utilisé par un autre employé.";
            header("Location: admin_dashboard.php");
            exit();
        }

        // Update employee information
        if (!empty($password)) {
            $stmt = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, matricule = ?, mdp = ?, role = ? WHERE id = ?");
            $stmt->execute([$nom, $prenom, $matricule, $password, $role, $employee_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, matricule = ?, role = ? WHERE id = ?");
            $stmt->execute([$nom, $prenom, $matricule, $role, $employee_id]);
        }

        $_SESSION['success_message'] = "Informations de l'employé mises à jour avec succès.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de la mise à jour des informations de l'employé.";
    }
}

header("Location: admin_dashboard.php");
exit();
?>