<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'responsable') {
    header("Location: login-employe.php");
    exit();
}

$stmt = $pdo->query("SELECT * FROM users WHERE role != 'responsable' ORDER BY role, nom");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all clients with their bank accounts
$stmt = $pdo->query("
    SELECT 
        c.*,
        GROUP_CONCAT(DISTINCT cb.numero_compte ORDER BY cb.id) as comptes,
        GROUP_CONCAT(DISTINCT cb.type_compte ORDER BY cb.id) as types_compte,
        GROUP_CONCAT(DISTINCT cb.solde ORDER BY cb.id) as soldes
    FROM clients c
    LEFT JOIN comptes_bancaires cb ON c.id = cb.client_id
    GROUP BY c.id
    ORDER BY c.nom
");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle employee deletion
if (isset($_POST['delete_employee'])) {
    $employee_id = $_POST['employee_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'responsable'");
        $stmt->execute([$employee_id]);
        $_SESSION['success_message'] = "Employé supprimé avec succès.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de la suppression de l'employé.";
    }
    header("Location: admin_dashboard.php");
    exit();
}

// client deletion
if (isset($_POST['delete_client'])) {
    $client_id = $_POST['client_id'];
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete client's bank accounts
        $stmt = $pdo->prepare("DELETE FROM comptes_bancaires WHERE client_id = ?");
        $stmt->execute([$client_id]);
        
        // delete the client
        $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
        $stmt->execute([$client_id]);
    
        $pdo->commit();
        $_SESSION['success_message'] = "Client et ses comptes supprimés avec succès.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Erreur lors de la suppression du client.";
    }
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - MyBank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        /* Basic styles */
        .dashboard-header {
            background-color: #2c3e50;
            color: white;
            padding: 1rem 0;
        }

        /* Simple Modal Styles */
        .custom-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            margin: 50px auto;
            padding: 20px;
            width: 90%;
            max-width: 500px;
            border-radius: 5px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .modal-footer {
            margin-top: 20px;
            text-align: right;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .show-modal {
            display: block;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="dashboard-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="fas fa-university me-3" style="font-size: 2rem;"></i>
                    <h1 class="mb-0">Administration MyBank</h1>
                </div>
                <div>
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
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#employees" type="button" role="tab">
                    <i class="fas fa-users me-2"></i>Employés
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#clients" type="button" role="tab">
                    <i class="fas fa-user-circle me-2"></i>Clients
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Employees Tab -->
            <div class="tab-pane fade show active" id="employees" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="card-title">
                                <i class="fas fa-users me-2 text-primary"></i>
                                Gestion des Employés
                            </h3>
                            <a href="add_employee.php" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Ajouter un employé
                            </a>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Matricule</th>
                                        <th>Rôle</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $employee): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($employee['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['prenom']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['matricule']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $employee['role'] === 'caissier' ? 'info' : 'success'; ?>">
                                                <?php echo ucfirst($employee['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary me-2 edit-btn" 
                                                    data-employee-id="<?php echo $employee['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="" method="POST" class="d-inline">
                                                <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                                                <button type="submit" name="delete_employee" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet employé ?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modals Container - Moved outside the table -->
            <div id="modals-container">
                <?php foreach ($employees as $employee): ?>
                <div class="custom-modal" id="editEmployee<?php echo $employee['id']; ?>">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Modifier l'employé</h5>
                            <button type="button" class="close-modal">&times;</button>
                        </div>
                        <form action="update_employee.php" method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                                
                                <div class="form-group">
                                    <label class="form-label">Nom</label>
                                    <input type="text" name="nom" class="form-control" 
                                           value="<?php echo htmlspecialchars($employee['nom']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Prénom</label>
                                    <input type="text" name="prenom" class="form-control" 
                                           value="<?php echo htmlspecialchars($employee['prenom']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Matricule</label>
                                    <input type="text" name="matricule" class="form-control" 
                                           value="<?php echo htmlspecialchars($employee['matricule']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Nouveau mot de passe</label>
                                    <input type="password" name="password" class="form-control" 
                                           placeholder="Laisser vide pour ne pas changer">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Rôle</label>
                                    <select name="role" class="form-control" required>
                                        <option value="caissier" <?php echo $employee['role'] === 'caissier' ? 'selected' : ''; ?>>
                                            Caissier
                                        </option>
                                        <option value="charge_clientele" <?php echo $employee['role'] === 'charge_clientele' ? 'selected' : ''; ?>>
                                            Chargé de clientèle
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary close-modal">Annuler</button>
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Clients Tab -->
            <div class="tab-pane fade" id="clients" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title mb-4">
                            <i class="fas fa-user-circle me-2 text-primary"></i>
                            Liste des Clients
                        </h3>
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>CIN</th>
                                        <th>Date de naissance</th>
                                        <th>Comptes bancaires</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clients as $client): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($client['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($client['prenom']); ?></td>
                                        <td><?php echo htmlspecialchars($client['cin']); ?></td>
                                        <td><?php echo htmlspecialchars($client['date_naissance']); ?></td>
                                        <td>
                                            <?php 
                                            if (!empty($client['comptes'])) {
                                                $comptes = explode(',', $client['comptes']);
                                                $types = explode(',', $client['types_compte']);
                                                $soldes = explode(',', $client['soldes']);
                                                
                                                // Debug information
                                                error_log("Client ID: " . $client['id']);
                                                error_log("Comptes: " . print_r($comptes, true));
                                                error_log("Types: " . print_r($types, true));
                                                error_log("Soldes: " . print_r($soldes, true));
                                                
                                                // Ensure all arrays have the same length
                                                $count = min(count($comptes), count($types), count($soldes));
                                                
                                                for ($i = 0; $i < $count; $i++) {
                                                    $type = trim($types[$i]);
                                                    $compte = trim($comptes[$i]);
                                                    $solde = floatval(trim($soldes[$i]));
                                                    
                                                    // Determine badge color based on account type
                                                    $badgeClass = 'bg-info';
                                                    if ($type === 'courant') {
                                                        $badgeClass = 'bg-primary';
                                                    } elseif ($type === 'epargne') {
                                                        $badgeClass = 'bg-success';
                                                    }
                                                    
                                                    echo '<div class="mb-2">';
                                                    echo '<span class="badge ' . $badgeClass . ' me-2">' . 
                                                         ucfirst(htmlspecialchars($type)) . '</span>';
                                                    echo '<strong>' . htmlspecialchars($compte) . '</strong> - ';
                                                    echo '<span class="text-' . ($solde >= 0 ? 'success' : 'danger') . '">' . 
                                                         number_format($solde, 2, ',', ' ') . ' DH</span>';
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo '<span class="text-muted">Aucun compte</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <form action="" method="POST" class="d-inline">
                                                <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
                                                <button type="submit" name="delete_client" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce client et tous ses comptes ?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (!alert.matches(':hover')) {
                    alert.style.display = 'none';
                }
            });
        }, 5000);

        // Updated Modal JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Get all edit buttons
            const editButtons = document.querySelectorAll('.edit-btn');
            
            // Add click event to each edit button
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const employeeId = this.getAttribute('data-employee-id');
                    const modal = document.getElementById('editEmployee' + employeeId);
                    if (modal) {
                        modal.classList.add('show-modal');
                    }
                });
            });

            // Get all close buttons
            const closeButtons = document.querySelectorAll('.close-modal');
            
            // Add click event to each close button
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const modal = this.closest('.custom-modal');
                    if (modal) {
                        modal.classList.remove('show-modal');
                    }
                });
            });

            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target.classList.contains('custom-modal')) {
                    event.target.classList.remove('show-modal');
                }
            });
        });
    </script>
</body>
</html>