<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$client_id = $_SESSION['user_id'];
$order_id = intval($_GET['id']);
$query = "SELECT o.*, u.company_name, u.contact_person, u.email, u.phone, u.country
          FROM orders o 
          JOIN users u ON o.client_id = u.id 
          WHERE o.id = ? AND o.client_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $order_id, $client_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: orders.php');
    exit();
}
$items_query = $conn->prepare("SELECT oi.*, p.name as product_name, p.reference as product_reference, p.moq, p.unit_price, oi.subtotal
                               FROM order_items oi 
                               JOIN products p ON oi.product_id = p.id 
                               WHERE oi.order_id = ?");
$items_query->bind_param("i", $order_id);
$items_query->execute();
$items = $items_query->get_result();

// Calcul du total
$total_amount = floatval($order['total_value']) ?? 0.00;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation Commande - FUS Denim</title>
    
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

        .btn-success {
            background: linear-gradient(135deg, var(--accent-4), var(--accent-1));
            border: none;
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
        }

        .order-section {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            margin-bottom: 2rem;
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2.5rem;
            position: relative;
        }

        .step-indicator:before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: var(--gray-200);
            z-index: 1;
        }

        .step {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--gray-200);
            color: var(--gray-500);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .step.active .step-number {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: var(--white);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .step.completed .step-number {
            background: linear-gradient(135deg, var(--accent-4), var(--accent-1));
            color: var(--white);
        }

        .step-label {
            font-size: 0.9rem;
            color: var(--gray-500);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .step.active .step-label {
            color: var(--accent-1);
            font-weight: 600;
        }

        .status-indicator {
            display: flex;
            justify-content: space-between;
            margin: 2.5rem 0;
            position: relative;
        }

        .status-indicator:before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: var(--gray-200);
            z-index: 1;
        }

        .status-step {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .status-dot {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--gray-200);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .status-step.active .status-dot {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: var(--white);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .status-step.completed .status-dot {
            background: linear-gradient(135deg, var(--accent-4), var(--accent-1));
            color: var(--white);
        }

        .status-label {
            font-size: 0.9rem;
            color: var(--gray-500);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .status-step.active .status-label {
            color: var(--accent-1);
            font-weight: 600;
        }

        .order-summary {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            position: sticky;
            top: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
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

        .print-only {
            display: none;
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

            .status-indicator, .step-indicator {
                flex-direction: column;
                gap: 1rem;
            }

            .status-indicator:before, .step-indicator:before {
                display: none;
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

        @media print {
            .sidebar, .header-actions, .no-print {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            
            .order-section {
                border: none;
                box-shadow: none;
                padding: 1rem 0;
            }
            
            body {
                font-size: 12pt;
                background: white !important;
            }
            
            .print-only {
                display: block !important;
            }
        }

        .badge-status {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .badge-received { background: linear-gradient(135deg, var(--accent-1), var(--accent-2)); color: white; }
        .badge-validating { background: linear-gradient(135deg, var(--accent-5), #FBBF24); color: white; }
        .badge-confirmed { background: linear-gradient(135deg, var(--accent-4), #34D399); color: white; }
        .badge-production { background: linear-gradient(135deg, #8B5CF6, var(--accent-2)); color: white; }
        .badge-shipped { background: linear-gradient(135deg, #10B981, var(--accent-4)); color: white; }
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
            <a href="orders.php" class="nav-item">
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
                <h1>Confirmation de Commande</h1>
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

        <!-- En-tête d'impression -->
        <div class="print-only mb-4">
            <div class="row">
                <div class="col-6">
                    <h3>FUS Denim</h3>
                    <p class="mb-0">Tunis, Tunisia</p>
                    <p class="mb-0">contact@fus-denim.com</p>
                </div>
                <div class="col-6 text-end">
                    <h3>Confirmation de Commande</h3>
                    <p class="mb-0">Date: <?php echo date('d/m/Y'); ?></p>
                </div>
            </div>
            <hr>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="order-section">
                    <!-- Statut de la commande -->
                    <div class="text-center mb-4">
                        <div class="d-inline-block p-4 rounded" style="background: linear-gradient(135deg, var(--accent-4), var(--accent-1));">
                            <i class="fas fa-check-circle fa-3x text-white mb-3"></i>
                            <h3 class="text-white mb-2">Commande Soumise avec Succès !</h3>
                            <p class="text-white mb-0">Merci pour votre commande. Votre demande a été reçue et est en cours de traitement.</p>
                        </div>
                    </div>

                    <!-- Timeline du statut -->
                    <div class="status-indicator">
                        <?php
                        $status_steps = [
                            ['status' => 'received', 'icon' => 'fa-inbox', 'label' => 'Reçue', 'description' => 'Commande reçue'],
                            ['status' => 'validating', 'icon' => 'fa-clipboard-check', 'label' => 'Validation', 'description' => 'En cours de vérification'],
                            ['status' => 'confirmed', 'icon' => 'fa-check-double', 'label' => 'Confirmée', 'description' => 'Commande confirmée'],
                            ['status' => 'production', 'icon' => 'fa-industry', 'label' => 'Production', 'description' => 'En production'],
                            ['status' => 'shipped', 'icon' => 'fa-truck', 'label' => 'Expédiée', 'description' => 'Commande expédiée']
                        ];
                        
                        $current_status_index = array_search($order['status'], array_column($status_steps, 'status'));
                        ?>
                        
                        <?php foreach ($status_steps as $index => $step): ?>
                            <div class="status-step <?php echo $index == $current_status_index ? 'active' : ($index < $current_status_index ? 'completed' : ''); ?>">
                                <div class="status-dot">
                                    <?php if ($index < $current_status_index): ?>
                                        <i class="fas fa-check"></i>
                                    <?php elseif ($index == $current_status_index): ?>
                                        <i class="fas <?php echo $step['icon']; ?>"></i>
                                    <?php else: ?>
                                        <i class="fas <?php echo $step['icon']; ?>"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="status-label">
                                    <small class="d-block"><?php echo $step['label']; ?></small>
                                    <small class="text-muted"><?php echo $step['description']; ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Détails de commande -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="mb-3"><i class="fas fa-building me-2"></i>Informations Client</h5>
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <p class="mb-2"><strong>Société :</strong> <?php echo htmlspecialchars($order['company_name']); ?></p>
                                    <p class="mb-2"><strong>Contact :</strong> <?php echo htmlspecialchars($order['contact_person']); ?></p>
                                    <p class="mb-2"><strong>Email :</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                                    <p class="mb-2"><strong>Téléphone :</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                                    <p class="mb-0"><strong>Pays :</strong> <?php echo htmlspecialchars($order['country']); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5 class="mb-3"><i class="fas fa-shipping-fast me-2"></i>Détails Commande</h5>
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <p class="mb-2"><strong>Date commande :</strong> <?php echo date('d/m/Y', strtotime($order['created_at'])); ?></p>
                                    <p class="mb-2"><strong>Statut :</strong> <span class="badge-status badge-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></p>
                                    <p class="mb-2"><strong>Articles totaux :</strong> <?php echo $order['total_items']; ?> unités</p>
                                    <p class="mb-2"><strong>Montant HT :</strong> <?php echo number_format($total_amount, 2); ?> €</p>
                                    <?php if ($order['estimated_delivery']): ?>
                                        <p class="mb-2"><strong>Livraison souhaitée :</strong> <?php echo date('d/m/Y', strtotime($order['estimated_delivery'])); ?></p>
                                    <?php endif; ?>
                                    <?php if ($order['shipping_address']): ?>
                                        <p class="mb-0"><strong>Adresse livraison :</strong><br><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Articles de commande -->
                    <h5 class="mb-3"><i class="fas fa-list-alt me-2"></i>Articles Commandés</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Produit</th>
                                    <th>Référence</th>
                                    <th>Couleur</th>
                                    <th>Taille</th>
                                    <th>Lavage</th>
                                    <th class="text-end">Prix Unitaire</th>
                                    <th class="text-end">Quantité</th>
                                    <th class="text-end">Sous-total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($item = $items->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['product_reference']); ?></td>
                                        <td><?php echo htmlspecialchars($item['color']); ?></td>
                                        <td><?php echo htmlspecialchars($item['size']); ?></td>
                                        <td><?php echo htmlspecialchars($item['wash_type']); ?></td>
                                        <td class="text-end"><?php echo number_format($item['unit_price'], 2); ?> €</td>
                                        <td class="text-end"><?php echo $item['quantity']; ?></td>
                                        <td class="text-end"><?php echo number_format($item['subtotal'], 2); ?> €</td>
                                    </tr>
                                <?php endwhile; ?>
                                <tr class="table-light">
                                    <td colspan="7" class="text-end fw-bold">Total HT :</td>
                                    <td class="text-end fw-bold"><?php echo number_format($total_amount, 2); ?> €</td>
                                </tr>
                                <tr class="table-light">
                                    <td colspan="7" class="text-end fw-bold">TVA (20%) :</td>
                                    <td class="text-end fw-bold"><?php echo number_format($total_amount * 0.20, 2); ?> €</td>
                                </tr>
                                <tr class="table-light">
                                    <td colspan="7" class="text-end fw-bold">Total TTC :</td>
                                    <td class="text-end fw-bold h5 text-success"><?php echo number_format($total_amount * 1.20, 2); ?> €</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Notes de commande -->
                    <?php if ($order['notes']): ?>
                        <div class="card mb-4 border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Notes de Commande</h6>
                            </div>
                            <div class="card-body">
                                <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Prochaines étapes -->
                    <div class="alert alert-info border-0 shadow-sm">
                        <h6><i class="fas fa-forward me-2"></i>Prochaines étapes :</h6>
                        <ul class="mb-0">
                            <li>Notre équipe examinera votre commande sous 1-2 jours ouvrés</li>
                            <li>Vous recevrez un email de confirmation avec les détails</li>
                            <li>La production commencera une fois la commande confirmée</li>
                            <li>Vous pouvez suivre le statut dans votre portail client</li>
                        </ul>
                    </div>
                    
                    <!-- Boutons d'action -->
                    <div class="d-flex justify-content-between mt-4 pt-3 border-top no-print">
                        <div>
                            <a href="orders.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Retour aux commandes
                            </a>
                            <a href="dashboard_client.php" class="btn btn-outline-primary">
                                <i class="fas fa-home me-2"></i>Tableau de bord
                            </a>
                        </div>
                        <div>
                            <button onclick="window.print()" class="btn btn-outline-dark me-2">
                                <i class="fas fa-print me-2"></i>Imprimer
                            </button>
                            <a href="export_order.php?id=<?php echo $order_id; ?>" class="btn btn-outline-success">
                                <i class="fas fa-file-excel me-2"></i>Exporter Excel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="order-summary">
                    <h4 class="mb-4"><i class="fas fa-receipt me-2"></i>Récapitulatif</h4>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-3">
                            <div>
                                <h6 class="mb-1">Commande #</h6>
                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($order['reference']); ?></p>
                            </div>
                            <div class="text-end">
                                <h6 class="mb-1">Statut</h6>
                                <span class="badge-status badge-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Date commande :</span>
                            <span class="fw-bold"><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total articles :</span>
                            <span class="fw-bold"><?php echo $order['total_items']; ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Montant HT :</span>
                            <span class="fw-bold"><?php echo number_format($total_amount, 2); ?> €</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>TVA (20%) :</span>
                            <span class="fw-bold"><?php echo number_format($total_amount * 0.20, 2); ?> €</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Total TTC :</span>
                            <span class="fw-bold text-success h5"><?php echo number_format($total_amount * 1.20, 2); ?> €</span>
                        </div>
                    </div>
                    
                    <div class="alert alert-info small border-0 shadow-sm">
                        <h6><i class="fas fa-info-circle me-2"></i>Informations :</h6>
                        <ul class="mb-0">
                            <li>Confirmation email envoyée à <?php echo htmlspecialchars($order['email']); ?></li>
                            <li>Délai de production : 3-4 semaines</li>
                            <li>Suivi commande disponible dans le portail</li>
                            <li>Contact : contact@fus-denim.com</li>
                        </ul>
                    </div>
                    
                    <div class="mt-4">
                        <h6>Actions rapides :</h6>
                        <div class="d-grid gap-2">
                            <a href="new_order.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-plus-circle me-2"></i>Nouvelle commande
                            </a>
                            <a href="message.php?order_id=<?php echo $order_id; ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-envelope me-2"></i>Envoyer message
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div>
                <i class="fas fa-gem" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Confirmation Commande
            </div>
            <div>
                <span class="system-status">
                    <i class="fas fa-circle"></i> Commande #<?php echo htmlspecialchars($order['reference']); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Informations d'impression -->
    <div class="print-only mt-4 pt-3 border-top">
        <p class="small text-muted mb-0">
            Ceci est une confirmation de commande automatique. Pour toute question, contactez contact@fus-denim.com
        </p>
        <p class="small text-muted mb-0">
            Commande #: <?php echo htmlspecialchars($order['reference']); ?> | 
            Date: <?php echo date('d/m/Y H:i:s'); ?>
        </p>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Impression automatique optionnelle
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === 'true') {
            setTimeout(() => {
                window.print();
            }, 1000);
        }
        
        // Sauvegarder comme PDF
        function saveAsPDF() {
            alert('Fonction d\'export PDF sera bientôt disponible.');
            // Ici, vous pourriez intégrer une bibliothèque comme jsPDF
        }
        
        // Partager la confirmation
        function shareConfirmation() {
            if (navigator.share) {
                navigator.share({
                    title: 'Confirmation Commande - <?php echo htmlspecialchars($order['reference']); ?>',
                    text: 'Ma commande a été soumise à FUS Denim',
                    url: window.location.href
                });
            } else {
                alert('Copiez ce lien pour partager : ' + window.location.href);
            }
        }
        
        // Afficher un message de succès
        document.addEventListener('DOMContentLoaded', function() {
            const successAlert = document.createElement('div');
            successAlert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
            successAlert.style.zIndex = '9999';
            successAlert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                Commande confirmée avec succès !
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Vérifier si c'est une nouvelle commande (en ajoutant un paramètre dans l'URL)
            if (urlParams.has('new')) {
                document.body.appendChild(successAlert);
                setTimeout(() => {
                    successAlert.remove();
                }, 5000);
            }
        });
    </script>
</body>
</html>