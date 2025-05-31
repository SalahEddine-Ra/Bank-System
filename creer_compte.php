<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? $_SESSION['client']['id'] ?? null;
    if (!$client_id) {
        die("Erreur: ID client non spécifié.");
    }
    
    $type_compte = $_POST['account_type'];
    $solde = $_POST['balance'];
    $date_creation = date('Y-m-d'); 
    $code_secret = $_POST['code']; 
    
    if (empty($_POST['account_number'])) {
        die("Le numéro de compte est requis.");
    }

    $numero_compte = $_POST['account_number'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comptes_bancaires WHERE numero_compte = ?");
    $stmt->execute([$numero_compte]);
    if ($stmt->fetchColumn() > 0) {
        die("Le numéro de compte existe déjà.");
    }

    $signature_image_path = null;
    if (isset($_FILES['signature_image']) && $_FILES['signature_image']['size'] > 0) {
        // Vérifier si le répertoire existe, sinon le créer
        if (!is_dir("signatures")) {
            mkdir("signatures");
        }
        
        // Vérifier l'extension du fichier
        $extension = pathinfo($_FILES['signature_image']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($extension), ["jpg", "jpeg", "png"])) {
            die("L'extension de l'image n'est pas prise en charge. Extensions acceptées: jpg, jpeg, png");
        } else {
            // nouveau nom 
            $nouveau_nom = "signature_" . $client_id . "_" . time() . "." . $extension;
            $chemin_destination = "signatures/" . $nouveau_nom;
            
            // Déplacer le fichier téléchargé
            $transfert = move_uploaded_file($_FILES['signature_image']['tmp_name'], $chemin_destination);
            if ($transfert) {
                $signature_image_path = $chemin_destination;
            } else {
                die("Transfert non réussi. Code d'erreur: " . $_FILES['signature_image']['error']);
            }
        }
    }

    try {
        $pdo->beginTransaction();
        
        if ($signature_image_path) {
            $stmt = $pdo->prepare("INSERT INTO comptes_bancaires 
                (numero_compte, client_id, type_compte, solde, code_secret, date_creation, signature_image) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $numero_compte, 
                $client_id, 
                $type_compte, 
                $solde, 
                $code_secret,
                $date_creation,
                $signature_image_path
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO comptes_bancaires 
                (numero_compte, client_id, type_compte, solde, code_secret, date_creation) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $numero_compte, 
                $client_id, 
                $type_compte, 
                $solde, 
                $code_secret,
                $date_creation
            ]);
        }
        
        $compte_id = $pdo->lastInsertId(); 
    
        $create_card = isset($_POST['create_card']) ? $_POST['create_card'] : '0';
        if ($type_compte === 'courant' || $create_card === '1') {
            function genererNumeroCarte() {
                return '4000' . str_pad(rand(0, 999999999999), 12, '0', STR_PAD_LEFT);
            }
            
            $numero_carte = genererNumeroCarte();
            $date_expiration = date('Y-m-d', strtotime('+3 years'));
            $cvv = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
            $type_carte = $_POST['carte_type'] ?? 'Débit'; 
            
            $stmt = $pdo->prepare("INSERT INTO cartes_bancaires 
                (numero_carte, date_expiration, cvv, type, compte_id) 
                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $numero_carte, 
                $date_expiration, 
                $cvv, 
                $type_carte,
                $compte_id
            ]);
            
            $card_message = " avec carte bancaire de type " . $type_carte;
        } else {
            $card_message = "";
        }
        
        $pdo->commit();
        
        // Ajouter un message concernant la signature si une image a été uploadée
        $signature_message = $signature_image_path ? " et signature enregistrée" : "";
        $_SESSION['success_message'] = "Compte créé avec succès" . $card_message . $signature_message;
        
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'caissier') {
            header("Location: caissier_dashboard.php");
        } else {
            header("Location: client_dashboard.php");
        }
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erreur lors de la création du compte: " . $e->getMessage());
    }
}
?>