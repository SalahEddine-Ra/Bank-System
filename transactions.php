<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'caissier') {
    $_SESSION['error_message'] = "Accès non autorisé";
    header('Location: caissier_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_id = $_POST['account_id'] ?? null;
    $transaction_type = $_POST['transactionType'] ?? null;
    $amount = floatval($_POST['amount'] ?? 0);
    $receiver_account = $_POST['receiverAccountNumber'] ?? null;

    if (!$account_id || !$transaction_type || $amount <= 0) {
        $_SESSION['error_message'] = "Veuillez remplir tous les champs correctement.";
        header('Location: caissier_dashboard.php');
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT numero_compte, solde, type_compte FROM comptes_bancaires WHERE id = ?");
        $stmt->execute([$account_id]);
        $source_account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$source_account) {
            throw new Exception("Compte source introuvable.");
        }

        switch ($transaction_type) {
            case 'dépôt':
                // Update account balance for deposit
                $stmt = $pdo->prepare("UPDATE comptes_bancaires SET solde = solde + ? WHERE id = ?");
                $stmt->execute([$amount, $account_id]);

                $stmt = $pdo->prepare("INSERT INTO transactions (montant, type, compte_source_id, date_transaction) 
                                     VALUES (?, 'dépôt', ?, NOW())");
                $stmt->execute([$amount, $source_account['numero_compte']]);

                $_SESSION['success_message'] = "Dépôt de " . number_format($amount, 2) . " DH effectué avec succès.";
                break;

            case 'retrait':
                // Check if account has sufficient balance
                if ($source_account['solde'] < $amount) {
                    throw new Exception("Solde insuffisant pour effectuer ce retrait.");
                }

                // Update account balance
                $stmt = $pdo->prepare("UPDATE comptes_bancaires SET solde = solde - ? WHERE id = ?");
                $stmt->execute([$amount, $account_id]);

                // Record the transaction
                $stmt = $pdo->prepare("INSERT INTO transactions (montant, type, compte_source_id, date_transaction) 
                                     VALUES (?, 'retrait', ?, NOW())");
                $stmt->execute([$amount, $source_account['numero_compte']]);

                $_SESSION['success_message'] = "Retrait de " . number_format($amount, 2) . " DH effectué avec succès.";
                break;

            case 'virement':
                if (!$receiver_account) {
                    throw new Exception("Veuillez spécifier un compte bénéficiaire.");
                }

                // Check if source account has sufficient balance
                if ($source_account['solde'] < $amount) {
                    throw new Exception("Solde insuffisant pour effectuer ce virement.");
                }

                // Verify and get destination account
                $stmt = $pdo->prepare("SELECT id FROM comptes_bancaires WHERE numero_compte = ?");
                $stmt->execute([$receiver_account]);
                $dest_account = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$dest_account) {
                    throw new Exception("Compte bénéficiaire introuvable.");
                }

                // Update source account balance
                $stmt = $pdo->prepare("UPDATE comptes_bancaires SET solde = solde - ? WHERE id = ?");
                $stmt->execute([$amount, $account_id]);

                // Update destination account balance
                $stmt = $pdo->prepare("UPDATE comptes_bancaires SET solde = solde + ? WHERE numero_compte = ?");
                $stmt->execute([$amount, $receiver_account]);

                // Record the transaction
                $stmt = $pdo->prepare("INSERT INTO transactions (montant, type, compte_source_id, compte_dest_id, date_transaction) 
                                     VALUES (?, 'virement', ?, ?, NOW())");
                $stmt->execute([$amount, $source_account['numero_compte'], $receiver_account]);

                $_SESSION['success_message'] = "Virement de " . number_format($amount, 2) . " DH effectué avec succès.";
                break;

            default:
                throw new Exception("Type de transaction non valide.");
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Erreur: " . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = "Méthode non autorisée.";
}

header('Location: caissier_dashboard.php');
exit;
