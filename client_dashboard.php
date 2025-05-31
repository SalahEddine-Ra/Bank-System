<?php
session_start();
include 'config.php';

if (!isset($_SESSION['client']) || empty($_SESSION['client'])) {
    header('Location: login-client.php');
    exit;
}

$compte = $_SESSION['client'];
if ($compte) {
    $stmt = $pdo->prepare("SELECT * FROM comptes_bancaires WHERE id = ?");
    $stmt->execute([$compte['id']]);
    $compte = $stmt->fetch(PDO::FETCH_ASSOC);
    $_SESSION['success_message'] = "Bienvenue sur votre tableau de bord.";
} else {
    $error = "Compte non trouvé.";
    header('Location: login.php');
    exit;
}



// Si un virement est effectué
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_transfer'])) {
    $montant = $_POST['amount'];
    $compte_dest_num = $_POST['recipient'];
    $accountNumber = $compte['numero_compte'];

    if ($montant <= 0) {
        $error = "Le montant doit être supérieur à zéro.";
    } elseif ($montant > $compte['solde']) {
        $error = "Solde insuffisant pour effectuer cette transaction.";
    } else {
        $pdo->beginTransaction();
        try {
            // Mettre à jour le solde du compte source
            $stmt = $pdo->prepare("UPDATE comptes_bancaires SET solde = solde - ? WHERE numero_compte = ?");
            $stmt->execute([$montant, $accountNumber]);
            $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM comptes_bancaires WHERE numero_compte = ?");
            $stmt2->execute([$compte_dest_num]);
            if ($stmt2->fetchColumn() == 0) {
                throw new Exception("Le compte destinataire n'existe pas.");
            } else {
                $pdo->prepare("UPDATE comptes_bancaires SET solde = solde + ? WHERE numero_compte = ?")
                    ->execute([$montant, $compte_dest_num]);
            }
            // Enregistrer la transaction
            $stmt = $pdo->prepare("INSERT INTO transactions 
                (montant, type, compte_source_id, compte_dest_id) 
                VALUES (?, 'virement', ?, ?)");
            $stmt->execute([$montant, $accountNumber, $compte_dest_num]);

            $pdo->commit();
            $success = "Virement effectué avec succès.";
            $_SESSION['success_message'] = "Virement effectué avec succès.";
            header("Location: client_dashboard.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erreur lors du virement : " . $e->getMessage();
            $_SESSION['error_message'] = $error;
            header("Location: client_dashboard.php");
            exit;
        }
    }
}

$formatted_balance = isset($compte['solde']) ? number_format($compte['solde'], 2, ',', ' ') : '0,00';





// Formulaire reclamations
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_reclamation'])) {
    $sujet = $_POST['sujet'];
    $message = $_POST['message'];
    if ($compte['client_id'] && $sujet && $message) {
        $stmt = $pdo->prepare("INSERT INTO reclamations (user_id, objet, message, statut, date_reclamation) VALUES (?, ?, ?, 'en_attente', NOW())");
        $stmt->execute([$compte['client_id'], $sujet, $message]);
        $success = "Réclamation envoyée avec succès.";
        header("Location: client_dashboard.php");
        exit;
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}

// Récupérer l'historique des réclamations
$stmt = $pdo->prepare("SELECT * FROM reclamations WHERE user_id = ? ORDER BY date_reclamation DESC");
$stmt->execute([$compte['client_id']]);
$reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);




// les données de l'utilisateur connecté
$stmtUser = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmtUser->execute([$compte['client_id']]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

// les données de la carte associée
$stmtCard = $pdo->prepare("SELECT * FROM cartes_bancaires WHERE compte_id = ?");
$stmtCard->execute([$compte['id']]);
$card = $stmtCard->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banking Dashboard</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        .dashboard-header {
            background-color: #2c3e50;
            color: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }

        .nav-link {
            color: #2c3e50;
            transition: all 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            color: rgb(11, 154, 164);
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            font-weight: bold;
            padding: 1rem 1.25rem;
        }

        .balance-icon {
            width: 50px;
            height: 50px;
            background-color: rgba(40, 167, 69, 0.1);
            color: rgb(11, 154, 164);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .profile-section {
            position: absolute;
            top: 70px;
            right: 20px;
            width: 300px;
            z-index: 1000;
            display: none;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            font-weight: 600;
        }

        .btn-success {
            background-color: rgb(11, 154, 164);
            border-color: rgb(11, 154, 164);
        }

        .btn-success:hover {
            background-color: rgb(9, 139, 149);
            border-color: rgb(9, 139, 149);
        }

        #sidebar {
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
            min-height: 100vh;
            transition: transform 0.3s ease-in-out;
        }

        @media (max-width: 768px) {
            #sidebar.hidden {
                transform: translateX(-100%);
            }
        }
    </style>
</head>

<body class="bg-light">
    <!-- Header -->
    <header class="dashboard-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <button id="toggle-sidebar" class="btn btn-link text-white d-md-none me-3">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-university me-3" style="font-size: 2rem;"></i>
                        <h1 class="mb-0">MyBank</h1>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-3">Bienvenue, <?php echo isset($user['prenom']) ? htmlspecialchars($user['prenom']) : ''; ?></span>
                    <button id="profile-toggle" class="btn btn-outline-light">
                        <i class="fas fa-user me-2"></i>Mon Profil
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="d-flex">
        <!-- Sidebar -->
        <aside id="sidebar" style="width: 250px;">
            <nav class="nav flex-column p-3">
                <a href="#" class="nav-link mb-2" id="dashboardBtn">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <a href="#" class="nav-link mb-2" id="supportBtn">
                    <i class="fas fa-question-circle me-2"></i>Support
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                </a>
            </nav>
        </aside>

        <!-- Profile Card -->
        <div class="card profile-section" id="profile-card">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-user-circle me-2"></i>Mon Profil
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <small class="text-muted">Informations personnelles</small>
                    <div class="d-flex align-items-center mt-2">
                        <div class="balance-icon me-3">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h6 class="mb-0"><?= isset($user['prenom']) && isset($user['nom']) ? htmlspecialchars($user['prenom']) . ' ' . htmlspecialchars($user['nom']) : 'Nom non disponible' ?></h6>
                            <small class="text-muted">Client</small>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <small class="text-muted">Contact</small>
                    <div class="d-flex align-items-center mt-2">
                        <div class="balance-icon me-3">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h6 class="mb-0"><?= isset($user['email']) ? htmlspecialchars($user['email']) : 'Email non disponible' ?></h6>
                            <small class="text-muted">Email</small>
                        </div>
                    </div>
                </div>

                <?php if (isset($card) && $card): ?>
                    <div class="mb-4">
                        <small class="text-muted">Carte bancaire</small>
                        <div class="d-flex align-items-center mt-2">
                            <div class="balance-icon me-3">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">
                                    <?= substr(htmlspecialchars($card['numero'] ?? ''), 0, 4) ?>
                                    ****
                                    ****
                                    <?= substr(htmlspecialchars($card['numero'] ?? ''), -4) ?>
                                </h6>
                                <small class="text-muted">
                                    Expire: <?= htmlspecialchars($card['date_expiration'] ?? 'Non disponible') ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-grow-1 p-4">
            <div class="container">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['error_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <!-- Dashboard Content -->
                <div id="dashboardMain">
                    <!-- Account Balance Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-wallet me-2 text-primary"></i>Solde du compte
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="balance-icon me-3">
                                        <i class="fas fa-coins"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= $formatted_balance ?> DH</h3>
                                        <small class="text-muted">Compte N° <?= isset($compte['numero']) ? htmlspecialchars($compte['numero']) : 'Non disponible' ?></small>
                                    </div>
                                </div>

                                <!-- Replace the existing button with this one -->
                                <a href="generate_pdf.php" class="btn btn-primary">
                                    <i class="fas fa-file-alt me-2"></i>Relevé
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Transfer Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-exchange-alt me-2 text-primary"></i>Effectuer un virement
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Numéro de compte du destinataire</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" name="recipient" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Montant</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-money-bill"></i></span>
                                        <input type="number" class="form-control" name="amount" step="0.01" required>
                                        <span class="input-group-text">DH</span>
                                    </div>
                                </div>
                                <button type="submit" name="submit_transfer" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Effectuer le virement
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <?php
                    $stmt = $pdo->prepare("SELECT date_transaction, montant, compte_dest_id, type FROM transactions WHERE type = 'virement' AND compte_source_id = ? ORDER BY date_transaction");
                    $stmt->execute([$compte['numero_compte']]);
                    ?>
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-history me-2 text-primary"></i>Transactions récentes
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Destinataire</th>
                                            <th>Montant</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($stmt->rowCount() == 0): ?>
                                            <tr>
                                                <td colspan="4" style="text-align: center;">Aucune transaction récente</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php while ($ligne = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                                <tr>
                                                    <td><?php echo $ligne["date_transaction"]; ?></td>
                                                    <td><?php echo $ligne["compte_dest_id"]; ?></td>
                                                    <td><?php echo number_format($ligne["montant"], 2) . ' DH'; ?></td>
                                                    <td><span class="badge bg-primary"><?php echo ucfirst($ligne["type"]); ?></span></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Support Content -->
                <div id="supportMain" style="display: none;">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-comment-alt me-2 text-primary"></i>Soumettre une réclamation
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">Sujet</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-heading"></i></span>
                                        <input type="text" class="form-control" name="sujet" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Message</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-comment"></i></span>
                                        <textarea class="form-control" name="message" rows="4" required></textarea>
                                    </div>
                                </div>
                                <button type="submit" name="submit_reclamation" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Envoyer
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-history me-2 text-primary"></i>Historique des réclamations
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Sujet</th>
                                            <th>Message</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reclamations as $reclamation): ?>
                                            <tr>
                                                <td><?php echo $reclamation["date_reclamation"]; ?></td>
                                                <td><?php echo htmlspecialchars($reclamation["objet"]); ?></td>
                                                <td><?php echo htmlspecialchars($reclamation["message"]); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $reclamation["statut"] === 'en_attente' ? 'warning' : 'success'; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $reclamation["statut"])); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar functionality
        const toggleSidebarButton = document.getElementById('toggle-sidebar');
        const sidebar = document.getElementById('sidebar');

        toggleSidebarButton.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
        });

        // Profile card toggle functionality
        const profileToggle = document.getElementById('profile-toggle');
        const profileCard = document.getElementById('profile-card');

        // Function to toggle profile card
        function toggleProfileCard() {
            if (profileCard.style.display === 'none' || profileCard.style.display === '') {
                profileCard.style.display = 'block';
            } else {
                profileCard.style.display = 'none';
            }
        }

        // Add event listeners to both profile toggle buttons
        profileToggle.addEventListener('click', toggleProfileCard);

        // Close the profile card when clicking outside of it
        document.addEventListener('click', (event) => {
            if (!profileCard.contains(event.target) &&
                !profileToggle.contains(event.target)) {
                profileCard.style.display = 'none';
            }
        });
        // support section toggle functionality
        document.getElementById("supportBtn").addEventListener("click", function(e) {
            e.preventDefault();
            document.getElementById("dashboardMain").style.display = "none";
            document.getElementById("supportMain").style.display = "block";
        });

        document.getElementById("dashboardBtn").addEventListener("click", function(e) {
            e.preventDefault();
            document.getElementById("supportMain").style.display = "none";
            document.getElementById("dashboardMain").style.display = "block";
        });
    </script>
</body>

</html>