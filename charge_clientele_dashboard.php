<?php
session_start();
include 'config.php';
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">'
        . $_SESSION['success_message'] .
        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">'
        . $_SESSION['error_message'] .
        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
    unset($_SESSION['error_message']);
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'charge_clientele') {
    header("Location: login-employe.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$clients = $pdo->query("SELECT id, nom, prenom, cin FROM clients")->fetchAll(PDO::FETCH_ASSOC);
$reclamations = $pdo->query("SELECT * FROM reclamations WHERE statut = 'en_attente'")->fetchAll(PDO::FETCH_ASSOC);

// Updated query to get accounts with proper ordering
$comptes = $pdo->query("
    SELECT cb.*, c.nom, c.prenom 
    FROM comptes_bancaires cb
    JOIN clients c ON cb.client_id = c.id
    ORDER BY c.nom, c.prenom, cb.id
")->fetchAll(PDO::FETCH_ASSOC);

// reclamations par client
$clientsById = [];
foreach ($clients as $client) {
    $clientsById[$client['id']] = $client;
}

// Détermine l'onglet actif en fonction de la demande
$activeTab = 'reclamations'; // Onglet par défaut

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reclamation_id'], $_POST['action'])) {
        $activeTab = 'reclamations';
        $id = $_POST['reclamation_id'];
        $newStatut = $_POST['action'];

       
        $stmt = $pdo->prepare("UPDATE reclamations SET statut = ? WHERE id = ?");
        $stmt->execute([
            $newStatut,
            $id
        ]);

        if ($stmt) {
            $_SESSION['success_message'] = "Réclamation mise à jour avec succès.";
            header("Location: " . $_SERVER['PHP_SELF'] . "?tab=reclamations");
            exit;
        } else {
            $_SESSION['error_message'] = "Erreur lors de la mise à jour de la réclamation.";
        }
    } elseif (isset($_POST['client_id'], $_POST['chequierType'])) {
        $activeTab = 'chequiers';
        $clientId = $_POST['client_id'];
        $chequierType = $_POST['chequierType'];

        // Insert the chequier request into the database
        $stmt = $pdo->prepare("INSERT INTO chequiers (client_id, type_chequier) VALUES (?, ?)");
        $stmt->execute([$clientId, $chequierType]);

        if ($stmt) {
            $_SESSION['success_message'] = "Demande de chéquier fait avec succès.";
            header("Location: " . $_SERVER['PHP_SELF'] . "?tab=chequiers");
            exit;
        } else {
            $_SESSION['error_message'] = "Erreur lors de l'envoi de la demande de chéquier.";
        }
    } elseif (isset($_POST['client_id'], $_POST['accountId'], $_POST['creditAmount'], $_POST['creditReason'])) {
        $activeTab = 'credits';
        $clientId = $_POST['client_id'];
        $accountId = $_POST['accountId'];
        $creditAmount = $_POST['creditAmount'];
        $creditReason = $_POST['creditReason'];

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO credits (client_id, montant, motif) VALUES (?, ?, ?)");
            $stmt->execute([$clientId, $creditAmount, $creditReason]);
            // Mettre à jour le solde du compte bancaire
            $updateStmt = $pdo->prepare("UPDATE comptes_bancaires SET solde = solde + ? WHERE id = ?");
            $updateStmt->execute([$creditAmount, $accountId]);
            $pdo->commit();

            $_SESSION['success_message'] = "Demande de crédit faite avec succès. Le solde du compte a été mis à jour.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
        }

        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=credits");
        exit;
    } elseif (isset($_POST['client_id'])) {

        if (isset($_POST['form_type']) && $_POST['form_type'] === 'chequier') {
            $activeTab = 'chequiers';
        } else {
            $activeTab = 'credits';
        }
    }
}

// Récupère l'onglet actif depuis l'URL si présent
if (isset($_GET['tab']) && in_array($_GET['tab'], ['reclamations', 'chequiers', 'credits'])) {
    $activeTab = $_GET['tab'];
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Chargé de Clientèle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <link href="styles.css" rel="stylesheet">
    <style>
        .card {
            background-color: rgb(255, 255, 255);
            border: none;
            box-shadow: 0 0 10px rgba(4, 200, 235, 0.4);
        }

        .nav-tabs .nav-link {
            color: rgb(11, 154, 164);
        }

        .nav-tabs .nav-link:hover {
            background-color: rgb(99, 200, 231);
            color: white;
        }

        .nav-tabs .nav-link.active {
            background-color: rgb(11, 154, 164);
            border-color: rgb(13, 163, 209);
            color: white;
        }

        /* Fix for list styling */
        .list-group-item {
            background-color: rgb(255, 255, 255);
            color: rgb(11, 154, 164);
            border: 1px solid rgb(11, 154, 164);
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    <header class="dashboard-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class=" col-md-6 ">
                    <i class="fas fa-university me-3" style="font-size: 2rem;"></i>
                    <h1>Dashboard - Chargé de Clientèle</h1>
                    
                </div>
                <div class="col-md-4 text-end">
                    <span class="me-3">Bienvenue, <?php echo $_SESSION['prenom'] . ' ' . $_SESSION['nom']; ?></span>
                    <a href="logout.php" class="btn btn-outline-light">
                        <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </header>
    <div class="container py-5">
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="clienteleTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'reclamations' ? 'active' : ''; ?>"
                    id="reclam-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#reclamations"
                    type="button"
                    role="tab">Réclamations</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'chequiers' ? 'active' : ''; ?>"
                    id="chequiers-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#chequiers"
                    type="button"
                    role="tab">Chéquiers</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'credits' ? 'active' : ''; ?>"
                    id="credits-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#credits"
                    type="button"
                    role="tab">Crédits</button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="clienteleTabsContent">
            <!-- Réclamations -->
            <div class="tab-pane fade <?php echo $activeTab === 'reclamations' ? 'show active' : ''; ?>"
                id="reclamations"
                role="tabpanel">
                <div class="card p-3 mb-3">
                    <ul class="list-group">
                        <?php if (count($reclamations) > 0): ?>
                            <?php foreach ($reclamations as $reclamation): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        Client #<?php echo $reclamation['user_id']; ?>
                                        <?php echo $clientsById[$reclamation['user_id']]['prenom'] . ' ' . $clientsById[$reclamation['user_id']]['nom']; ?>
                                        - <?php echo $reclamation['message']; ?>
                                    </span>
                                    <div class="d-flex gap-2">
                                        <form method="post" action="">
                                            <input type="hidden" name="reclamation_id" value="<?php echo $reclamation['id']; ?>">
                                            <button type="submit" name="action" value="en_cours" class="btn btn-sm btn-primary">En cours</button>
                                        </form>
                                        <form method="post" action="">
                                            <input type="hidden" name="reclamation_id" value="<?php echo $reclamation['id']; ?>">
                                            <button type="submit" name="action" value="traitee" class="btn btn-sm btn-primary">Traiter</button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center">Aucune réclamation en attente</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Chéquiers -->
            <div class="tab-pane fade <?php echo $activeTab === 'chequiers' ? 'show active' : ''; ?>"
                id="chequiers"
                role="tabpanel">
                <div class="card p-3">
                    <h4 class="text-primary">Faire une demande de chéquier</h4>
                    <form action="" method="POST">
                        <input type="hidden" name="form_type" value="chequier">
                        <!-- Sélection du client -->
                        <div class="mb-3">
                            <label for="client_id_chequier" class="form-label text-light">Sélectionner un client</label>
                            <select class="form-select" id="client_id_chequier" name="client_id" required onchange="this.form.submit()">
                                <option value="">-- Sélectionner un client --</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>"
                                        <?php if (isset($_POST['client_id']) && $_POST['client_id'] == $client['id'] && $activeTab === 'chequiers') echo 'selected'; ?>>
                                        <?php echo $client['prenom'] . ' ' . $client['nom'] . ' (CIN: ' . $client['cin'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if (isset($_POST['client_id']) && $_POST['client_id'] !== '' && $activeTab === 'chequiers'): ?>
                            <!-- Comptes bancaires du client sélectionné -->
                            <div class="mb-3" id="accountSelectionChequier">
                                <label for="accountIdChequier" class="form-label text-primary">Sélectionner un compte bancaire</label>
                                <select class="form-select" id="accountIdChequier" name="accountId" required>
                                    <?php
                                    $hasAccounts = false;
                                    foreach ($comptes as $compte):
                                        if ($compte['client_id'] == $_POST['client_id']):
                                            $hasAccounts = true;
                                    ?>
                                            <option value="<?php echo $compte['id']; ?>">
                                                <?php echo $compte['id'] . ' - ' . $compte['type_compte'] . ' (Solde: ' . $compte['solde'] . ')'; ?>
                                            </option>
                                        <?php
                                        endif;
                                    endforeach;

                                    if (!$hasAccounts):
                                        ?>
                                        <option value="" disabled>Ce client n'a pas de comptes</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Type de chéquier -->
                            <div class="mb-3">
                                <label for="chequierType" class="form-label text-primary">Type de Chéquier</label>
                                <select class="form-select" id="chequierType" name="chequierType" required>
                                    <option value="nouveau">Nouveau Chéquier</option>
                                    <option value="reedition">Réédition</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary" <?php echo !$hasAccounts ? 'disabled' : ''; ?>>
                                Soumettre la demande
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Crédits -->
            <div class="tab-pane fade <?php echo $activeTab === 'credits' ? 'show active' : ''; ?>"
                id="credits"
                role="tabpanel">
                <div class="card p-3">
                    <h4 class="text-primary">Demande de crédit</h4>
                    <form action="" method="POST">
                        <!-- Sélection du client -->
                        <div class="mb-3">
                            <label for="client_id" class="form-label">Sélectionner un client</label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value="">Choisir un client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>">
                                        <?php echo $client['prenom'] . ' ' . $client['nom'] . ' (CIN: ' . $client['cin'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Comptes du client -->
                        <div class="mb-3">
                            <label class="form-label">Comptes du client</label>
                            <div id="client_accounts" class="border rounded p-3">
                                <div class="text-muted">Sélectionnez un client pour voir ses comptes</div>
                            </div>
                        </div>

                        <!-- Sélection du compte -->
                        <div class="mb-3">
                            <label for="accountId" class="form-label">Sélectionner un compte</label>
                            <select class="form-select" id="accountId" name="accountId" required>
                                <option value="">Choisir un compte</option>
                            </select>
                        </div>

                        <!-- Montant du crédit -->
                        <div class="mb-3">
                            <label for="creditAmount" class="form-label">Montant du crédit</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="creditAmount" name="creditAmount" required step="0.01">
                                <span class="input-group-text">DH</span>
                            </div>
                        </div>

                        <!-- Motif du crédit -->
                        <div class="mb-3">
                            <label for="creditReason" class="form-label">Motif du crédit</label>
                            <textarea class="form-control" id="creditReason" name="creditReason" rows="3" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Soumettre la demande</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap tabs properly
            const tabTriggers = document.querySelectorAll('[data-bs-toggle="tab"]');

            tabTriggers.forEach(trigger => {
                trigger.addEventListener('click', function(event) {
                    event.preventDefault();

                    // Get the tab target
                    const tabTarget = this.getAttribute('data-bs-target').substring(1);

                    // Update URL without reloading the page
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tabTarget);
                    window.history.pushState({}, '', url);

                    // Let Bootstrap handle the tab activation
                    const tabInstance = new bootstrap.Tab(this);
                    tabInstance.show();
                });
            });

            // Handle client selection to maintain tab state
            const clientSelects = document.querySelectorAll('select[name="client_id"]');
            clientSelects.forEach(select => {
                select.addEventListener('change', function() {
                    // Add hidden field to track which tab the form belongs to
                    const formType = this.closest('form').querySelector('input[name="form_type"]').value;

                    // Store current tab in session storage before submitting
                    sessionStorage.setItem('activeTab', formType === 'chequier' ? 'chequiers' : 'credits');

                    // Submit the form
                    this.form.submit();
                });
            });
        });

        // Function to format currency
        function formatCurrency(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'MAD'
            }).format(amount);
        }

        // Function to update account display
        function updateAccountDisplay(clientId) {
            const accountsContainer = document.getElementById('client_accounts');
            const accountSelect = document.getElementById('accountId');
            
            // Clear previous content
            accountsContainer.innerHTML = '';
            accountSelect.innerHTML = '<option value="">Choisir un compte</option>';
            
            if (!clientId) {
                accountsContainer.innerHTML = '<div class="text-muted">Sélectionnez un client pour voir ses comptes</div>';
                return;
            }

            // Filter accounts for selected client
            const clientAccounts = <?php echo json_encode($comptes); ?>.filter(account => account.client_id == clientId);
            
            if (clientAccounts.length === 0) {
                accountsContainer.innerHTML = '<div class="text-muted">Ce client n\'a pas de comptes</div>';
                return;
            }

            // Create accounts display
            const accountsList = document.createElement('div');
            clientAccounts.forEach(account => {
                // Determine badge color based on account type
                const badgeClass = account.type_compte === 'courant' ? 'bg-primary' : 'bg-success';
                
                const accountDiv = document.createElement('div');
                accountDiv.className = 'mb-2';
                accountDiv.innerHTML = `
                    <span class="badge ${badgeClass} me-2">${account.type_compte.charAt(0).toUpperCase() + account.type_compte.slice(1)}</span>
                    <strong>${account.numero_compte}</strong> - 
                    <span class="text-${account.solde >= 0 ? 'success' : 'danger'}">${formatCurrency(account.solde)}</span>
                `;
                accountsList.appendChild(accountDiv);

                // Add option to select
                const option = document.createElement('option');
                option.value = account.id;
                option.textContent = `${account.numero_compte} (${account.type_compte}) - ${formatCurrency(account.solde)}`;
                accountSelect.appendChild(option);
            });

            accountsContainer.appendChild(accountsList);
        }

        // Add event listener for client selection
        document.getElementById('client_id').addEventListener('change', function() {
            updateAccountDisplay(this.value);
        });
    </script>
</body>

</html>