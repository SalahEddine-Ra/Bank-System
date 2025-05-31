<?php
session_start();
include 'config.php';

if (!isset($_SESSION['client']) || empty($_SESSION['client'])) {
    header('Location: login-client.php');
    exit;
}


// Get client data
$compte = $_SESSION['client'];
if ($compte) {
    $stmt = $pdo->prepare("SELECT * FROM comptes_bancaires WHERE id = ?");
    $stmt->execute([$compte['id']]);
    $compte = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $_SESSION['error_message'] = "Compte non trouvé.";
    header('Location: client_dashboard.php');
    exit;
}

// Get client personal information
$stmtUser = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmtUser->execute([$compte['client_id']]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

// Get card information
$stmtCard = $pdo->prepare("SELECT * FROM cartes_bancaires WHERE compte_id = ?");
$stmtCard->execute([$compte['id']]);
$card = $stmtCard->fetch(PDO::FETCH_ASSOC);

// Get recent transactions (last 10)
$stmtTransactions = $pdo->prepare("SELECT t.*, 
                                  CASE 
                                    WHEN t.compte_source_id = ? THEN 'débit'
                                    WHEN t.compte_dest_id = ? THEN 'crédit'
                                  END as sens_operation
                                  FROM transactions t
                                  WHERE t.compte_source_id = ? OR t.compte_dest_id = ? 
                                  ORDER BY t.date_transaction DESC LIMIT 10");
$stmtTransactions->execute([
    $compte['numero_compte'], 
    $compte['numero_compte'],
    $compte['numero_compte'],
    $compte['numero_compte']
]);
$transactions = $stmtTransactions->fetchAll(PDO::FETCH_ASSOC);

// Set the content type to HTML (we'll use print functionality instead of PDF)
header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relevé de Compte - <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
            color: #333;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h1, h2 {
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .date {
            color: #777;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .footer {
            margin-top: 50px;
            font-size: 12px;
            color: #777;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        
        .debit {
            color: #e74c3c;
        }
        
        .credit {
            color: #27ae60;
        }
        
        .print-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .print-btn:hover {
            background-color: #2980b9;
        }
        
        @media print {
            .print-btn {
                display: none;
            }
            
            body {
                background-color: #fff;
                padding: 0;
            }
            
            .container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="print-btn" onclick="window.print();">Imprimer ce relevé</button>
        
        <div class="header">
            <div class="logo">
                <i class="fas fa-university"></i> MyBank
            </div>
            <div class="date">
                Généré le <?= date('d/m/Y à H:i') ?>
            </div>
        </div>
        
        <h1>Relevé de Compte</h1>
        
        <div class="section">
            <h2>Informations Personnelles</h2>
            <table>
                <tr>
                    <th>Nom complet</th>
                    <td><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                </tr>
                <?php if (isset($user['adresse']) && !empty($user['adresse'])): ?>
                <tr>
                    <th>Adresse</th>
                    <td><?= htmlspecialchars($user['adresse']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if (isset($user['telephone']) && !empty($user['telephone'])): ?>
                <tr>
                    <th>Téléphone</th>
                    <td><?= htmlspecialchars($user['telephone']) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <div class="section">
            <h2>Informations du Compte</h2>
            <table>
                <tr>
                    <th>Numéro de compte</th>
                    <td><?= htmlspecialchars($compte['numero_compte']) ?></td>
                </tr>
                <tr>
                    <th>Type de compte</th>
                    <td><?= htmlspecialchars($compte['type'] ?? 'Compte courant') ?></td>
                </tr>
                <tr>
                    <th>Date d'ouverture</th>
                    <td><?= htmlspecialchars($compte['date_creation'] ?? date('d/m/Y')) ?></td>
                </tr>
                <tr>
                    <th>Solde actuel</th>
                    <td><strong><?= number_format($compte['solde'], 2, ',', ' ') ?> DH</strong></td>
                </tr>
            </table>
        </div>
        
        <?php if ($card): ?>
        <div class="section">
            <h2>Carte Bancaire</h2>
            <table>
                <tr>
                    <th>Numéro de carte</th>
                    <td><?= substr(htmlspecialchars($card['numero_carte']), 0, 4) . ' **** **** ' . substr(htmlspecialchars($card['numero_carte']), -4) ?></td>
                </tr>
                <tr>
                    <th>Type de carte</th>
                    <td><?= htmlspecialchars($card['type'] ?? 'Standard') ?></td>
                </tr>
                <tr>
                    <th>Date d'expiration</th>
                    <td><?= htmlspecialchars($card['date_expiration']) ?></td>
                </tr>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Transactions Récentes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Compte Source</th>
                        <th>Compte Destination</th>
                        <th>Montant (DH)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Aucune transaction récente</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <?php 
                            $isDebit = $transaction['sens_operation'] === 'débit';
                            $formattedAmount = number_format($transaction['montant'], 2, ',', ' ');
                            $class = $isDebit ? 'debit' : 'credit';
                            $sign = $isDebit ? '-' : '+';
                            ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($transaction['date_transaction'])) ?></td>
                                <td><?= ucfirst($transaction['type']) ?></td>
                                <td><?= htmlspecialchars($transaction['compte_source_id']) ?></td>
                                <td><?= htmlspecialchars($transaction['compte_dest_id'] ?? '-') ?></td>
                                <td class="<?= $class ?>"><?= $sign . ' ' . $formattedAmount ?> DH</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>Ce document est un relevé de compte généré automatiquement. Veuillez contacter votre agence pour toute question ou réclamation.</p>
            <p>MyBank vous remercie de votre confiance.</p>
        </div>
    </div>
</body>
</html>