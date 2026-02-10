<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$client_id = $_SESSION['user_id'];
$order_id = $_GET['id'];

$query = "SELECT o.* FROM orders o WHERE o.id = ? AND o.client_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $order_id, $client_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    header('Location: orders.php');
    exit();
}

$order = $order_result->fetch_assoc();

$items_query = "SELECT oi.*, p.reference as product_ref, p.name as product_name, p.description as product_desc
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
                ORDER BY oi.id ASC";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$status_labels = [
    'received' => 'Reçue',
    'validating' => 'Validation en cours',
    'confirmed' => 'Confirmée',
    'production' => 'En production',
    'shipped' => 'Expédiée'
];

$badge_classes = [
    'received' => 'badge-received',
    'validating' => 'badge-validating',
    'confirmed' => 'badge-confirmed',
    'production' => 'badge-production',
    'shipped' => 'badge-shipped'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Commande - FUS Denim</title>
    
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

        .btn-secondary {
            background: var(--white);
            border: 1px solid var(--gray-200);
            color: var(--gray-600);
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: var(--gray-50);
            color: var(--primary);
            text-decoration: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, #EF4444, #DC2626);
            border: none;
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            color: white;
            text-decoration: none;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.2);
            color: white;
            text-decoration: none;
        }

        .order-header-card {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            margin-bottom: 2rem;
        }

        .order-status-badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .badge-received {
            background: rgba(107, 114, 128, 0.1);
            color: var(--gray-600);
        }

        .badge-validating {
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-1);
        }

        .badge-confirmed {
            background: rgba(139, 92, 246, 0.1);
            color: var(--accent-2);
        }

        .badge-production {
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent-5);
        }

        .badge-shipped {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .order-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .meta-label {
            font-size: 0.85rem;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meta-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
        }

        .meta-value small {
            font-size: 0.9rem;
            color: var(--gray-500);
        }

        .items-section {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--accent-1);
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--gray-200);
        }

        .table {
            width: 100%;
            margin-bottom: 0;
        }

        .table thead th {
            background: var(--gray-50);
            color: var(--gray-700);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--gray-100);
        }

        .table tbody tr:hover {
            background: var(--gray-50);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .product-ref {
            font-size: 0.85rem;
            color: var(--gray-500);
        }

        .product-name {
            font-weight: 600;
            color: var(--primary);
        }

        .status-timeline {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            margin-bottom: 2rem;
        }

        .timeline {
            position: relative;
            padding-left: 3rem;
            margin-top: 1.5rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 1.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--gray-200);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 0.25rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background: var(--gray-300);
            border: 3px solid var(--white);
            box-shadow: 0 0 0 3px var(--gray-200);
        }

        .timeline-item.active::before {
            background: var(--accent-1);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .timeline-item.completed::before {
            background: var(--accent-4);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }

        .timeline-date {
            font-size: 0.85rem;
            color: var(--gray-500);
            margin-bottom: 0.25rem;
        }

        .timeline-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .timeline-desc {
            font-size: 0.9rem;
            color: var(--gray-600);
        }

        .notes-section {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            margin-bottom: 2rem;
        }

        .notes-content {
            background: var(--gray-50);
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1rem;
            border-left: 4px solid var(--accent-1);
        }

        .empty-notes {
            color: var(--gray-500);
            font-style: italic;
            text-align: center;
            padding: 2rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
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

        @media (max-width: 768px) {
            .sidebar {
                width: 240px;
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

            .order-meta {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .table thead {
                display: none;
            }

            .table tbody td {
                display: block;
                text-align: right;
                padding: 0.5rem 1rem;
                border-bottom: 1px solid var(--gray-200);
            }

            .table tbody td::before {
                content: attr(data-label);
                float: left;
                font-weight: 600;
                color: var(--gray-700);
                text-transform: uppercase;
                font-size: 0.8rem;
            }

            .table tbody tr {
                margin-bottom: 1rem;
                display: block;
                border: 1px solid var(--gray-200);
                border-radius: 8px;
                padding: 0.5rem;
            }

            .table tbody tr:last-child {
                margin-bottom: 0;
            }

            .product-info {
                text-align: left !important;
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

            .order-header-card,
            .items-section,
            .status-timeline,
            .notes-section {
                padding: 1rem;
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
                    <strong><?php echo htmlspecialchars(substr($_SESSION['company_name'], 0, 20)); ?></strong>
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
                <h1>Détails de la commande</h1>
                <p>Commande #<?php echo htmlspecialchars($order['reference']); ?></p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
                <a href="orders.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour aux commandes
                </a>
            </div>
        </div>

        <div class="order-header-card">
            <span class="order-status-badge <?php echo $badge_classes[$order['status']] ?? 'badge-received'; ?>">
                <?php echo $status_labels[$order['status']] ?? 'Reçue'; ?>
            </span>
            
            <h2>Commande #<?php echo htmlspecialchars($order['reference']); ?></h2>
            
            <div class="order-meta">
                <div class="meta-item">
                    <span class="meta-label">Date de commande</span>
                    <span class="meta-value">
                        <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                        <small><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                    </span>
                </div>
                
                <div class="meta-item">
                    <span class="meta-label">Articles</span>
                    <span class="meta-value"><?php echo $order['total_items']; ?> unités</span>
                </div>
                
                <div class="meta-item">
                    <span class="meta-label">Valeur totale</span>
                    <span class="meta-value"><?php echo number_format($order['total_value'], 2, ',', ' '); ?> €</span>
                </div>
                
                <div class="meta-item">
                    <span class="meta-label">Dernière mise à jour</span>
                    <span class="meta-value">
                        <?php echo date('d/m/Y', strtotime($order['updated_at'])); ?>
                        <small><?php echo date('H:i', strtotime($order['updated_at'])); ?></small>
                    </span>
                </div>
            </div>
        </div>

        <div class="items-section">
            <h3 class="section-title">
                <i class="fas fa-boxes me-2"></i>Articles de la commande
            </h3>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Référence</th>
                            <th>Couleur</th>
                            <th>Taille</th>
                            <th>Type de lavage</th>
                            <th>Quantité</th>
                            <th>Prix unitaire</th>
                            <th>Sous-total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($items_result->num_rows > 0): ?>
                            <?php while ($item = $items_result->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="Produit">
                                        <div class="product-info">
                                            <span class="product-name"><?php echo htmlspecialchars($item['product_name'] ?? 'Produit non spécifié'); ?></span>
                                            <span class="product-ref"><?php echo htmlspecialchars($item['product_ref'] ?? 'N/A'); ?></span>
                                        </div>
                                    </td>
                                    <td data-label="Référence"><?php echo htmlspecialchars($item['product_ref'] ?? 'N/A'); ?></td>
                                    <td data-label="Couleur"><?php echo htmlspecialchars($item['color'] ?? 'Standard'); ?></td>
                                    <td data-label="Taille"><?php echo htmlspecialchars($item['size'] ?? 'Standard'); ?></td>
                                    <td data-label="Type de lavage"><?php echo htmlspecialchars($item['wash_type'] ?? 'Standard'); ?></td>
                                    <td data-label="Quantité"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td data-label="Prix unitaire"><?php echo number_format($item['unit_price'], 2, ',', ' '); ?> €</td>
                                    <td data-label="Sous-total"><?php echo number_format($item['subtotal'], 2, ',', ' '); ?> €</td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    Aucun article trouvé pour cette commande
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="status-timeline">
            <h3 class="section-title">
                <i class="fas fa-history me-2"></i>Progression de la commande
            </h3>
            
            <div class="timeline">
                <?php
                $statuses = [
                    'received' => ['icon' => 'fa-inbox', 'title' => 'Commande reçue', 'desc' => 'Votre commande a été reçue et est en attente de validation.'],
                    'validating' => ['icon' => 'fa-clipboard-check', 'title' => 'Validation en cours', 'desc' => 'L\'équipe FUS vérifie les détails de votre commande.'],
                    'confirmed' => ['icon' => 'fa-check-circle', 'title' => 'Commande confirmée', 'desc' => 'La commande est confirmée et passe en pré-production.'],
                    'production' => ['icon' => 'fa-industry', 'title' => 'En production', 'desc' => 'Les articles sont en cours de fabrication dans notre usine.'],
                    'shipped' => ['icon' => 'fa-truck', 'title' => 'Expédiée', 'desc' => 'La commande a été expédiée et est en transit.']
                ];
                
                $current_status_index = array_search($order['status'], array_keys($statuses));
                
                foreach ($statuses as $status => $info):
                    $status_index = array_search($status, array_keys($statuses));
                    $is_completed = $status_index < $current_status_index;
                    $is_active = $status_index === $current_status_index;
                ?>
                    <div class="timeline-item <?php echo $is_completed ? 'completed' : ($is_active ? 'active' : ''); ?>">
                        <div class="timeline-date">
                            <?php if ($is_completed): ?>
                                <?php 
                                $date_field = $status . '_date';
                                echo isset($order[$date_field]) ? date('d/m/Y H:i', strtotime($order[$date_field])) : 'À déterminer';
                                ?>
                            <?php elseif ($is_active): ?>
                                En cours
                            <?php else: ?>
                                À venir
                            <?php endif; ?>
                        </div>
                        <div class="timeline-title">
                            <i class="fas <?php echo $info['icon']; ?> me-2"></i>
                            <?php echo $info['title']; ?>
                        </div>
                        <div class="timeline-desc">
                            <?php echo $info['desc']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($order['notes'] || $order['shipping_address']): ?>
            <div class="notes-section">
                <h3 class="section-title">
                    <i class="fas fa-sticky-note me-2"></i>Informations complémentaires
                </h3>
                
                <div class="notes-content">
                    <?php if ($order['shipping_address']): ?>
                        <div class="mb-3">
                            <strong><i class="fas fa-map-marker-alt me-2"></i>Adresse de livraison :</strong>
                            <p class="mt-1"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($order['notes']): ?>
                        <div>
                            <strong><i class="fas fa-comment me-2"></i>Notes :</strong>
                            <p class="mt-1"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($order['estimated_delivery']): ?>
                        <div class="mt-3">
                            <strong><i class="fas fa-calendar-check me-2"></i>Livraison estimée :</strong>
                            <p class="mt-1"><?php echo date('d/m/Y', strtotime($order['estimated_delivery'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($order['actual_delivery']): ?>
                        <div class="mt-3">
                            <strong><i class="fas fa-truck me-2"></i>Livraison effectuée :</strong>
                            <p class="mt-1"><?php echo date('d/m/Y', strtotime($order['actual_delivery'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <?php if ($order['status'] === 'received'): ?>
                <button class="btn btn-danger" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                    <i class="fas fa-times me-2"></i>Annuler la commande
                </button>
            <?php endif; ?>
            
           
            
            <?php if ($order['status'] === 'shipped'): ?>
                <button class="btn btn-primary" onclick="trackOrder(<?php echo $order['id']; ?>)">
                    <i class="fas fa-truck me-2"></i>Suivre la livraison
                </button>
            <?php endif; ?>
        </div>

        <div class="footer">
            <div>
                <i class="fas fa-gem" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Détails de commande
            </div>
            <div>
                <span class="system-status">
                    <i class="fas fa-circle" style="color: var(--accent-4);"></i> Statut : <?php echo $status_labels[$order['status']]; ?>
                </span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cancelOrder(orderId) {
            if (confirm('Êtes-vous sûr de vouloir annuler cette commande ? Cette action est irréversible.')) {
                fetch('cancel_order.php?id=' + orderId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Commande annulée avec succès ! Redirection...', 'success');
                            setTimeout(() => location.href = 'orders.php', 1500);
                        } else {
                            showNotification('Erreur : ' + data.message, 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Une erreur est survenue lors de l\'annulation de la commande.', 'danger');
                    });
            }
        }
        
        function trackOrder(orderId) {
            showNotification('Les informations de suivi seront bientôt disponibles pour la commande #' + orderId, 'info');
        }
        
        function showNotification(message, type = 'info') {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
            alert.style.zIndex = '1050';
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alert);
            
            setTimeout(() => alert.remove(), 5000);
        }

        const updateTime = () => {
            const now = new Date();
            const timeDisplay = document.querySelector('.time-display');
            if (timeDisplay) {
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const year = now.getFullYear();
                timeDisplay.innerHTML = `<i class="fas fa-clock me-2"></i>${day}/${month}/${year} • ${hours}:${minutes}`;
            }
        };

        setInterval(updateTime, 1000);
        updateTime();

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.location.href = 'orders.php';
            }
            if (e.key === 'p' && e.ctrlKey) {
                e.preventDefault();
                window.open('print_order.php?id=<?php echo $order["id"]; ?>', '_blank');
            }
        });
    </script>
</body>
</html>