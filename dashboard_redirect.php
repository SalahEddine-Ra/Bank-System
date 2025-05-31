<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login-employe.php');
    exit;
}

switch ($_SESSION['role']) {
    case 'caissier':
        header('Location: caissier_dashboard.php');
        break;
    case 'charge_clientele':
        header('Location: charge_clientele_dashboard.php');
        break;
    case 'responsable':
        header('Location: admin_dashboard.php');
        break;
    default:
        echo "Rôle inconnu.";
        exit;
}
exit;
?>