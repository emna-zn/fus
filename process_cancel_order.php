<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

<<<<<<< HEAD
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id'])) {
    $_SESSION['error'] = 'Requête invalide';
=======
if (!isset($_GET['id'])) {
>>>>>>> eb88bb074795731a4e423446ae0688689a615430
    header('Location: orders.php');
    exit();
}

<<<<<<< HEAD
$order_id = (int)$_POST['order_id'];
$client_id = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();
$check_query = "SELECT id, status FROM orders WHERE id = ? AND client_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $order_id, $client_id);
$check_stmt->execute();
$order = $check_stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = 'Commande non trouvée ou accès non autorisé';
    header('Location: orders.php');
    exit();
}
if ($order['status'] !== 'received') {
    $_SESSION['error'] = 'Seules les commandes avec statut "Reçue" peuvent être supprimées';
    header('Location: orders.php');
    exit();
}
$conn->begin_transaction();

try {
    $delete_items = "DELETE FROM order_items WHERE order_id = ?";
    $stmt1 = $conn->prepare($delete_items);
    $stmt1->bind_param("i", $order_id);
    $stmt1->execute();
    
    $delete_order = "DELETE FROM orders WHERE id = ?";
    $stmt2 = $conn->prepare($delete_order);
    $stmt2->bind_param("i", $order_id);
    $stmt2->execute();
    
    $conn->commit();
    
    $_SESSION['success'] = 'Commande supprimée avec succès';
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = 'Erreur lors de la suppression : ' . $e->getMessage();
}

header('Location: orders.php');
exit();
?>
=======
$database = new Database();
$conn = $database->getConnection();
$client_id = $_SESSION['user_id'];
$order_id = intval($_GET['id']);

// Vérifier si la commande appartient au client et si elle peut être annulée
$query = "SELECT o.*, u.company_name, u.contact_person, u.email 
          FROM orders o 
          JOIN users u ON o.client_id = u.id 
          WHERE o.id = ? AND o.client_id = ? AND o.status IN ('received', 'validating')";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $order_id, $client_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['error_message'] = "Commande non trouvée ou ne peut pas être annulée.";
    header('Location: orders.php');
    exit();
}

// Récupérer les articles de la commande pour affichage
$items_query = $conn->prepare("SELECT oi.*, p.name as product_name, p.reference as product_reference 
                               FROM order_items oi 
                               JOIN products p ON oi.product_id = p.id 
                               WHERE oi.order_id = ?");
$items_query->bind_param("i", $order_id);
$items_query->execute();
$items = $items_query->get_result();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annuler Commande - FUS Denim</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1F2937;
            --secondary: #111827;
            --accent-1: #3B82F6;
            --accent-2: #8B5CF6;
            --accent-3: #EC4899;
            --accent-4: #10B981;
            --accent-5: #F59E0B;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --white: #FFFFFF;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%);
            color: var(--primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .logo {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo i {
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo h2 {
            font-size: 1.5rem;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .nav-section {
            padding: 1.5rem 1rem;
        }

        .nav-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1rem;
            padding: 0 0.75rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-item:hover {
            color: var(--white);
            background: rgba(255, 255, 255, 0.08);
        }

        .nav-item.active {
            color: var(--white);
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
        }

        .nav-item i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        .sidebar-user {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, rgba(0, 0, 0, 0.1) 100%);
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .user-info small {
            color: rgba(255, 255, 255, 0.6);
            display: block;
            font-size: 0.75rem;
        }

        .user-info strong {
            color: var(--white);
            font-size: 0.9rem;
        }

        .logout-btn {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: transparent;
            color: var(--white);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
            color: var(--white);
            text-decoration: none;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header-title h1 {
            font-size: 2rem;
            color: var(--primary);
            margin: 0;
        }

        .header-title p {
            color: var(--gray-500);
            margin: 0.25rem 0 0 0;
            font-size: 0.9rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .time-display {
            padding: 0.75rem 1.5rem;
            background: var(--white);
            border-radius: 10px;
            font-weight: 600;
            color: var(--accent-1);
            box-shadow: var(--shadow-sm);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            border: none;
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.2);
        }

        .btn-danger {
            background: linear-gradient(135deg, #EF4444, #DC2626);
            border: none;
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.2);
        }

        .order-section {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            margin-bottom: 2rem;
        }

        .warning-card {
            background: linear-gradient(135deg, #FEF3C7, #FDE68A);
            border: 2px solid #F59E0B;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid var(--gray-300);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-1);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .footer {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-200);
            color: var(--gray-500);
            font-size: 0.9rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        @media (max-width: 1200px) {
            .sidebar {
                width: 260px;
            }

            .main-content {
                margin-left: 260px;
                padding: 1.5rem;
            }
        }

        @media (max-width: 992px) {
            .order-summary {
                position: static;
                margin-top: 2rem;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 240px;
                position: fixed;
            }

            .main-content {
                margin-left: 240px;
                padding: 1rem;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .header-actions {
                width: 100%;
            }
        }

        @media (max-width: 640px) {
            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
                padding: 0.75rem;
            }

            .header-title h1 {
                font-size: 1.5rem;
            }

            .footer {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-gem"></i>
            <h2>FUS Client</h2>
        </div>

        <div class="nav-section">
            <div class="nav-label">Menu Principal</div>
            <a href="dashboard_client.php" class="nav-item">
                <i class="fas fa-chart-line"></i>
                <span>Tableau de bord</span>
            </a>
            <a href="catalog_prv.php" class="nav-item">
                <i class="fas fa-tshirt"></i>
                <span>Catalogue produits</span>
            </a>
            <a href="orders.php" class="nav-item active">
                <i class="fas fa-shopping-bag"></i>
                <span>Mes commandes</span>
            </a>
            <a href="new_order.php" class="nav-item">
                <i class="fas fa-plus-circle"></i>
                <span>Nouvelle commande</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-label">Compte</div>
            <a href="profile.php" class="nav-item">
                <i class="fas fa-user-cog"></i>
                <span>Mon profil</span>
            </a>
            <a href="message.php" class="nav-item">
                <i class="fas fa-envelope"></i>
                <span>Messages</span>
            </a>
        </div>

        <div class="sidebar-user">
            <div class="user-card">
                <div class="user-avatar">
                    <i class="fas fa-building"></i>
                </div>
                <div class="user-info">
                    <small>Société</small>
                    <strong><?php echo htmlspecialchars(substr($_SESSION['company_name'] ?? '', 0, 20)); ?></strong>
                </div>
            </div>
            <a href="login.php?action=logout" class="logout-btn">
                <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="header-title">
                <h1>Annuler Commande</h1>
                <p>Commande #<?php echo htmlspecialchars($order['reference']); ?></p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
                <a href="orders.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Retour aux commandes
                </a>
            </div>
        </div>

        <!-- Messages d'alerte -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="order-section">
                    <!-- Avertissement -->
                    <div class="warning-card">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning me-3 mt-1"></i>
                            <div>
                                <h4 class="text-warning mb-3">Attention : Annulation de commande</h4>
                                <p class="mb-2"><strong>Cette action est irréversible.</strong> Une fois la commande annulée, vous ne pourrez plus la réactiver.</p>
                                <p class="mb-2">Conditions d'annulation :</p>
                                <ul class="mb-0">
                                    <li>Seules les commandes avec statut "Reçue" ou "En validation" peuvent être annulées</li>
                                    <li>Les commandes en production ne peuvent plus être annulées</li>
                                    <li>Les frais éventuels seront calculés selon nos conditions générales</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Détails de la commande -->
                    <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Détails de la commande</h5>
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Commande # :</strong> <?php echo htmlspecialchars($order['reference']); ?></p>
                                    <p class="mb-2"><strong>Date :</strong> <?php echo date('d/m/Y', strtotime($order['created_at'])); ?></p>
                                    <p class="mb-2"><strong>Statut :</strong> <span class="badge bg-warning"><?php echo ucfirst($order['status']); ?></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Articles totaux :</strong> <?php echo $order['total_items']; ?> unités</p>
                                    <p class="mb-2"><strong>Valeur HT :</strong> <?php echo number_format($order['total_value'], 2); ?> €</p>
                                    <?php if ($order['estimated_delivery']): ?>
                                        <p class="mb-0"><strong>Livraison prévue :</strong> <?php echo date('d/m/Y', strtotime($order['estimated_delivery'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Articles de la commande -->
                    <h5 class="mb-3"><i class="fas fa-list-alt me-2"></i>Articles commandés</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Produit</th>
                                    <th>Référence</th>
                                    <th>Couleur</th>
                                    <th>Taille</th>
                                    <th class="text-end">Quantité</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($item = $items->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['product_reference']); ?></td>
                                        <td><?php echo htmlspecialchars($item['color']); ?></td>
                                        <td><?php echo htmlspecialchars($item['size']); ?></td>
                                        <td class="text-end"><?php echo $item['quantity']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                                <tr class="table-light">
                                    <td colspan="4" class="text-end fw-bold">Total :</td>
                                    <td class="text-end fw-bold"><?php echo $order['total_items']; ?> unités</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Formulaire d'annulation -->
                    <form method="POST" action="process_cancel_order.php?id=<?php echo $order_id; ?>" id="cancelForm">
                        <h5 class="mb-3"><i class="fas fa-clipboard-list me-2"></i>Raison d'annulation</h5>
                        
                        <div class="mb-4">
                            <label class="form-label">Sélectionnez la raison principale :</label>
                            <select class="form-select" name="cancellation_reason" required>
                                <option value="">-- Sélectionnez une raison --</option>
                                <option value="Changement de collection/saison">Changement de collection/saison</option>
                                <option value="Modification des besoins clients">Modification des besoins clients</option>
                                <option value="Retard de livraison non acceptable">Retard de livraison non acceptable</option>
                                <option value="Problème de budget">Problème de budget</option>
                                <option value="Erreur dans la commande">Erreur dans la commande</option>
                                <option value="Autre raison">Autre raison</option>
                            </select>
                            <small class="text-muted">Cette information nous aide à améliorer notre service.</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Notes complémentaires :</label>
                            <textarea class="form-control" name="notes" rows="4" placeholder="Décrivez brièvement les raisons de l'annulation... (facultatif)"></textarea>
                            <small class="text-muted">Maximum 500 caractères.</small>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="confirmCancellation" required>
                            <label class="form-check-label" for="confirmCancellation">
                                Je comprends que cette action est irréversible et j'accepte les conditions d'annulation.
                            </label>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                            <a href="orders.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-danger btn-lg" onclick="return confirmCancellation()">
                                <i class="fas fa-ban me-2"></i>Confirmer l'annulation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="order-section">
                    <h4 class="mb-4"><i class="fas fa-exclamation-circle me-2"></i>Conséquences</h4>
                    
                    <div class="mb-4">
                        <div class="alert alert-warning border-0 shadow-sm">
                            <h6><i class="fas fa-clock me-2"></i>Délais d'annulation</h6>
                            <p class="mb-0">Les commandes ne peuvent être annulées que dans les 48 heures suivant leur soumission.</p>
                        </div>
                        
                        <div class="alert alert-info border-0 shadow-sm">
                            <h6><i class="fas fa-file-invoice me-2"></i>Frais éventuels</h6>
                            <ul class="mb-0">
                                <li>Pas de frais si annulation avant traitement</li>
                                <li>Frais de 10% si déjà en validation</li>
                                <li>Coûts réels si production déjà commencée</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-light border-0 shadow-sm">
                            <h6><i class="fas fa-sync-alt me-2"></i>Processus</h6>
                            <ul class="mb-0">
                                <li>Confirmation immédiate de l'annulation</li>
                                <li>Email de confirmation envoyé</li>
                                <li>Remboursement sous 5-10 jours ouvrés</li>
                                <li>Historique conservé pour référence</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h6>Besoin d'aide ?</h6>
                        <div class="d-grid gap-2">
                            <a href="message.php?subject=Annulation%20commande%20%23<?php echo $order['reference']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-envelope me-2"></i>Contacter le support
                            </a>
                            <a href="tel:+21671123456" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-phone me-2"></i>Appeler le support
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div>
                <i class="fas fa-gem" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Annulation Commande
            </div>
            <div>
                <span class="system-status">
                    <i class="fas fa-exclamation-triangle"></i> Action irréversible
                </span>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmCancellation() {
            const reason = document.querySelector('select[name="cancellation_reason"]').value;
            const confirmation = document.getElementById('confirmCancellation').checked;
            
            if (!reason) {
                alert('Veuillez sélectionner une raison d\'annulation.');
                return false;
            }
            
            if (!confirmation) {
                alert('Veuillez confirmer que vous comprenez les conséquences de cette action.');
                return false;
            }
            
            return confirm('Êtes-vous ABSOLUMENT sûr de vouloir annuler cette commande ?\n\nCette action est définitive et ne peut pas être annulée.');
        }
        
        document.getElementById('cancelForm').addEventListener('submit', function(e) {
            if (!confirmCancellation()) {
                e.preventDefault();
                return false;
            }
        });
        
        // Limiter le nombre de caractères dans les notes
        document.querySelector('textarea[name="notes"]').addEventListener('input', function(e) {
            if (this.value.length > 500) {
                this.value = this.value.substring(0, 500);
                alert('Maximum 500 caractères autorisés.');
            }
        });
    </script>
</body>
</html>
>>>>>>> eb88bb074795731a4e423446ae0688689a615430
