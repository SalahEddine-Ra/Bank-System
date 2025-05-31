<?php
session_start();
include 'config.php';
// message d'erreur et de succès
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

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'caissier') {
    header("Location: login-employe.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$clients = $pdo->query("SELECT id, nom, prenom, cin FROM clients")->fetchAll(PDO::FETCH_ASSOC);
$comptes = $pdo->query("SELECT * FROM comptes_bancaires")->fetchAll(PDO::FETCH_ASSOC);
$transactions = $pdo->query("SELECT * FROM transactions")->fetchAll(PDO::FETCH_ASSOC);

//the selected client ID for the transaction tab
$selected_client_id = isset($_POST['client_id']) ? $_POST['client_id'] : '';

// activeted Tab
$active_tab = 'nouveauClient';
if (isset($_POST['client_id'])) {
    $active_tab = 'transactions';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Caissier - MyBank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>

<body>
    <!-- Header -->
    <header class="dashboard-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="fas fa-university me-3" style="font-size: 2rem;"></i>
                    <h1 class="mb-0">Dashboard Caissier</h1>
                </div>
                <div>
                    <span class="me-3">Bienvenue, <?php echo $_SESSION['prenom'] . ' ' . $_SESSION['nom']; ?></span>
                    <a href="logout.php" class="btn btn-outline-light">
                        <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="container py-4">
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger" role="alert">
                <?php
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success" role="alert">
                <?php
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($active_tab == 'nouveauClient') ? 'active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#nouveauClient" type="button" role="tab">
                    <i class="fas fa-user-plus me-2"></i>Nouveau Client
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($active_tab == 'comptes') ? 'active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#comptes" type="button" role="tab">
                    <i class="fas fa-exchange-alt me-2"></i>Nouveau compte Bancaire
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($active_tab == 'transactions') ? 'active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#transactions" type="button" role="tab">
                    <i class="fas fa-wallet me-2"></i>Transactions
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($active_tab == 'transactions-history') ? 'active' : ''; ?>" data-bs-toggle="tab" data-bs-target="#transactions-history" type="button" role="tab">
                    <i class="fas fa-history me-2"></i>Historique des Transactions
                </button>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Nouveau Client Tab -->
            <div class="tab-pane fade <?php echo ($active_tab == 'nouveauClient') ? 'show active' : ''; ?>" id="nouveauClient" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title mb-4">
                            <i class="fas fa-user-plus me-2 text-primary"></i>
                            Créer un nouveau client
                        </h3>
                        <form action="cree_client.php" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">Nom</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="nom" name="nom" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="prenom" class="form-label">Prénom</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="prenom" name="prenom" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="date_naissance" class="form-label">Date de naissance</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                        <input type="date" class="form-control" id="date_naissance" name="date_naissance" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="cin" class="form-label">CIN</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        <input type="text" class="form-control" id="cin" name="cin" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="adresse" class="form-label">Adresse</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        <input type="text" class="form-control" id="adresse" name="adresse" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="telephone" class="form-label">Téléphone</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        <input type="tel" class="form-control" id="telephone" name="telephone" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Créer le client
                            </button>
                        </form>
                    </div>
                </div>
            </div>


            <!-- Nouveau Compte Bancaire Tab -->
            <div class="tab-pane fade <?php echo ($active_tab == 'comptes') ? 'show active' : ''; ?>" id="comptes" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title mb-4">
                            <i class="fas fa-credit-card me-2 text-primary"></i>
                            Créer un Compte Bancaire
                        </h3>
                        <form action="creer_compte.php" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="client_id" class="form-label">Client</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <select class="form-select" id="client_id" name="client_id" required>
                                            <option value="">Sélectionner un client</option>
                                            <?php foreach ($clients as $client): ?>
                                                <option value="<?php echo $client['id']; ?>">
                                                    <?php echo $client['prenom'] . ' ' . $client['nom'] . ' (CIN: ' . $client['cin'] . ')'; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="account_number" class="form-label">Numéro de compte</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                        <input type="text" class="form-control" id="account_number" name="account_number" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="account_type" class="form-label">Type de compte</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-piggy-bank"></i></span>
                                        <select class="form-select" id="account_type" name="account_type" required>
                                            <option value="épargne">Épargne</option>
                                            <option value="courant">Courant</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="balance" class="form-label">Solde initial</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-coins"></i></span>
                                        <input type="number" class="form-control" id="balance" name="balance" required step="0.01">
                                        <span class="input-group-text">DH</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="code" class="form-label">Code secret</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="code" name="code" required>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-4 border-light bg-light">
                                <div class="card-body">
                                    <div class="form-check mb-3">
                                        <input type="checkbox" class="form-check-input" id="create_card" name="create_card" value="1">
                                        <label class="form-check-label" for="create_card">
                                            <i class="fas fa-credit-card me-2"></i>Créer une carte bancaire
                                        </label>
                                    </div>

                                    <div id="card_type_section" style="display: none;">
                                        <label for="carte_type" class="form-label">Type de carte</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-credit-card"></i></span>
                                            <select class="form-select" id="carte_type" name="carte_type">
                                                <option value="Débit">Débit</option>
                                                <option value="Crédit">Crédit</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="signature_image" class="form-label">Image de signature</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-signature"></i></span>
                                        <input type="file" class="form-control" id="signature_image" name="signature_image" accept="image/jpeg,image/png">
                                        <div class="form-text">Formats acceptés: JPG, PNG, JPEG.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Créer le compte
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Transactions Tab -->
            <div class="tab-pane fade <?php echo ($active_tab == 'transactions') ? 'show active' : ''; ?>" id="transactions" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title mb-4">
                            <i class="fas fa-exchange-alt me-2 text-primary"></i>
                            Effectuer une Opération
                        </h3>
                        <form action="transactions.php" method="POST" id="transactionForm">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="client_id_transaction" class="form-label">Sélectionner un client</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <select class="form-select" id="client_id_transaction" name="client_id" onchange="loadClientAccounts(this.value)">
                                            <option value="">-- Sélectionner un client --</option>
                                            <?php foreach ($clients as $client): ?>
                                                <option value="<?php echo $client['id']; ?>" <?php echo ($selected_client_id == $client['id']) ? 'selected' : ''; ?>>
                                                    <?php echo $client['prenom'] . ' ' . $client['nom'] . ' (CIN: ' . $client['cin'] . ')'; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Comptes bancaires du client sélectionné -->
                            <div class="row">
                                <div class="col-md-12 mb-3" id="accountSelectionTransaction">
                                    <label for="accountIdTransaction" class="form-label">Sélectionner un compte bancaire</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-credit-card"></i></span>
                                        <select class="form-select" id="accountIdTransaction" name="accountId" required>
                                            <?php
                                            if ($selected_client_id):
                                                $client_accounts = false;
                                                foreach ($comptes as $compte):
                                                    if ($compte['client_id'] == $selected_client_id):
                                                        $client_accounts = true;
                                            ?>
                                                        <option value="<?php echo $compte['id']; ?>">
                                                            <?php echo 'Compte ' . $compte['numero_compte'] . ' - ' . ucfirst($compte['type_compte']) . ' (Solde: ' . number_format($compte['solde'], 2) . ' DH)'; ?>
                                                        </option>
                                                    <?php
                                                    endif;
                                                endforeach;

                                                if (!$client_accounts):
                                                    ?>
                                                    <option value="" disabled>Ce client n'a pas de comptes</option>
                                            <?php
                                                endif;
                                            endif;
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Type d'Opération</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-exchange-alt"></i></span>
                                        <select class="form-select" name="transactionType" id="transactionType" required>
                                            <option value="dépôt">Dépôt</option>
                                            <option value="retrait">Retrait</option>
                                            <option value="virement">Virement</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Montant</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-money-bill"></i></span>
                                        <input type="number" class="form-control" name="amount" step="0.01" required>
                                        <span class="input-group-text">DH</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3" id="receiverAccountDiv" style="display: none;">
                                <label class="form-label">Compte Bénéficiaire</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user-circle"></i></span>
                                    <input type="text" class="form-control" name="receiverAccountNumber" placeholder="Numéro du compte bénéficiaire">
                                </div>
                            </div>

                            <button type="submit" name="submit" class="btn btn-primary" <?php echo (!$selected_client_id) ? 'disabled' : ''; ?>>
                                <i class="fas fa-check-circle me-2"></i>Valider l'Opération
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- transactions historique Tab -->
            <div class="tab-pane fade <?php echo ($active_tab == 'transactions-history') ? 'show active' : ''; ?>" id="transactions-history" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title mb-4">
                            <i class="fas fa-exchange-alt me-2 text-primary"></i>
                            Historique des Transactions
                        </h3>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><?php echo $transaction['date_transaction']; ?></td>
                                            <td><?php echo ucfirst($transaction['type']); ?></td>
                                            <td><?php echo number_format($transaction['montant'], 2) . ' DH'; ?></td>
                                            <td>
                                                <span class="badge bg-success">Complété</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($transactions)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Aucune transaction trouvée.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle alerts auto-hide
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (!alert.matches(':hover')) {
                    alert.style.display = 'none';
                }
            });
        }, 5000);

        // Handle card type section visibility
        document.getElementById('create_card')?.addEventListener('change', function() {
            document.getElementById('card_type_section').style.display = this.checked ? 'block' : 'none';
        });

        // Handle transaction type change to show/hide receiver account field
        document.getElementById('transactionType')?.addEventListener('change', function() {
            document.getElementById('receiverAccountDiv').style.display =
                this.value === 'virement' ? 'block' : 'none';
        });

        // Function to load client accounts via AJAX
        function loadClientAccounts(clientId) {
            if (clientId === '') {
                document.getElementById('accountIdTransaction').innerHTML = '<option value="">Veuillez d\'abord sélectionner un client</option>';
                return;
            }

            // Create a hidden form to submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = window.location.href;

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'client_id';
            input.value = clientId;

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }

        // Show/hide receiver account field based on transaction type on page load
        window.addEventListener('DOMContentLoaded', function() {
            const transactionType = document.getElementById('transactionType');
            if (transactionType) {
                document.getElementById('receiverAccountDiv').style.display =
                    transactionType.value === 'virement' ? 'block' : 'none';
            }

            // Set active tab if returning from form submission
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab) {
                const tabElement = document.querySelector(`[data-bs-target="#${tab}"]`);
                if (tabElement) {
                    const tabInstance = new bootstrap.Tab(tabElement);
                    tabInstance.show();
                }
            }
        });
    </script>
</body>

</html>