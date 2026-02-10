<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'new') {
    header('Location: new_order.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$client_id = $_SESSION['user_id'];
$status_filter = $_GET['status'] ?? 'all';
$search_filter = $_GET['search'] ?? '';

$query = "SELECT o.*, 
          (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
          FROM orders o 
          WHERE o.client_id = ?";

$params = [$client_id];
$types = "i";

if ($status_filter !== 'all') {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($search_filter) {
    $query .= " AND (o.reference LIKE ? OR o.notes LIKE ?)";
    $search_param = "%" . $search_filter . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$query .= " ORDER BY o.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders_result = $stmt->get_result();
$orders_count = $orders_result->num_rows;

$stats_query = $conn->prepare("
    SELECT 
        SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as received,
        SUM(CASE WHEN status = 'validating' THEN 1 ELSE 0 END) as validating,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'production' THEN 1 ELSE 0 END) as production,
        SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped,
        COUNT(*) as total
    FROM orders 
    WHERE client_id = ?
");
$stats_query->bind_param("i", $client_id);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();

if (!isset($_SESSION['company_name'])) {
    $_SESSION['company_name'] = '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Commandes - FUS Denim</title>
    
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card:nth-child(1)::before {
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
        }

        .stat-card:nth-child(2)::before {
            background: var(--gray-600);
        }

        .stat-card:nth-child(3)::before {
            background: var(--accent-1);
        }

        .stat-card:nth-child(4)::before {
            background: var(--accent-2);
        }

        .stat-card:nth-child(5)::before {
            background: var(--accent-5);
        }

        .stat-card:nth-child(6)::before {
            background: var(--accent-4);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-card {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
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

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .order-card {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .order-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .order-card.received {
            border-left-color: var(--gray-600);
        }

        .order-card.validating {
            border-left-color: var(--accent-1);
        }

        .order-card.confirmed {
            border-left-color: var(--accent-2);
        }

        .order-card.production {
            border-left-color: var(--accent-5);
        }

        .order-card.shipped {
            border-left-color: var(--accent-4);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .order-title h3 {
            font-size: 1.1rem;
            color: var(--primary);
            margin: 0;
        }

        .order-date {
            color: var(--gray-500);
            font-size: 0.85rem;
            margin: 0.25rem 0 0 0;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
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

        .order-details {
            margin: 1rem 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .detail-label {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .detail-value {
            color: var(--primary);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .order-notes {
            background: var(--gray-50);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            border-left: 3px solid var(--accent-1);
        }

        .notes-label {
            color: var(--gray-600);
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .order-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .btn-view {
            flex: 1;
            padding: 0.75rem;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            color: var(--accent-1);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .btn-view:hover {
            background: var(--accent-1);
            color: white;
            text-decoration: none;
        }

        .btn-cancel {
            padding: 0.75rem;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #EF4444;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .btn-cancel:hover {
            background: #EF4444;
            color: white;
            text-decoration: none;
        }

        .btn-track {
            padding: 0.75rem;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: var(--accent-4);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .btn-track:hover {
            background: var(--accent-4);
            color: white;
            text-decoration: none;
        }

        .empty-state {
            background: var(--white);
            border-radius: 16px;
            padding: 3rem 2rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            margin-bottom: 2rem;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }

        .empty-state h4 {
            color: var(--gray-600);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--gray-500);
            margin-bottom: 1.5rem;
        }

        .status-guide {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            margin-top: 3rem;
        }

        .guide-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .guide-title i {
            color: var(--accent-1);
        }

        .guide-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .guide-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: 8px;
        }

        .guide-info small {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .guide-footer {
            color: var(--gray-500);
            font-size: 0.9rem;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-200);
        }

        .guide-footer i {
            color: var(--accent-5);
        }

        .export-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin: 2rem 0;
        }

        .btn-export {
            padding: 0.75rem 1.5rem;
            background: var(--white);
            border: 1px solid var(--gray-200);
            color: var(--gray-600);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-export:hover {
            background: var(--gray-50);
            color: var(--primary);
            text-decoration: none;
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

            .orders-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }

        @media (max-width: 992px) {
            .orders-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .guide-grid {
                grid-template-columns: 1fr;
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

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .export-actions {
                flex-direction: column;
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .order-actions {
                flex-direction: column;
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
                <h1>Mes commandes</h1>
                <p>Suivez et gérez toutes vos commandes FUS Denim</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
                <a href="new_order.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>Nouvelle commande
                </a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="stat-label">Total commandes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['received'] ?? 0; ?></div>
                <div class="stat-label">Reçues</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['validating'] ?? 0; ?></div>
                <div class="stat-label">Validation</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['confirmed'] ?? 0; ?></div>
                <div class="stat-label">Confirmées</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['production'] ?? 0; ?></div>
                <div class="stat-label">Production</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['shipped'] ?? 0; ?></div>
                <div class="stat-label">Expédiées</div>
            </div>
        </div>

        <div class="filter-card">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Tous les statuts</option>
                        <option value="received" <?php echo $status_filter === 'received' ? 'selected' : ''; ?>>Reçue</option>
                        <option value="validating" <?php echo $status_filter === 'validating' ? 'selected' : ''; ?>>Validation</option>
                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmée</option>
                        <option value="production" <?php echo $status_filter === 'production' ? 'selected' : ''; ?>>Production</option>
                        <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Expédiée</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Recherche</label>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($search_filter); ?>" 
                           placeholder="Référence, notes...">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Filtrer
                    </button>
                </div>
            </form>
        </div>

        <?php if ($orders_count > 0): ?>
            <?php $orders_result->data_seek(0); ?>
            <div class="orders-grid">
                <?php while ($order = $orders_result->fetch_assoc()): 
                    $status_class = strtolower($order['status']);
                    $badge_class = 'badge-' . $status_class;
                    $days_ago = floor((time() - strtotime($order['created_at'])) / (60 * 60 * 24));
                    $is_new = $days_ago < 3;
                ?>
                    <div class="order-card <?php echo $status_class; ?>">
                        <div class="order-header">
                            <div class="order-title">
                                <h3>Commande #<?php echo htmlspecialchars($order['reference']); ?></h3>
                                <p class="order-date">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                                    (<?php echo $days_ago; ?> jour<?php echo $days_ago > 1 ? 's' : ''; ?>)
                                </p>
                            </div>
                            <span class="status-badge <?php echo $badge_class; ?>">
                                <?php 
                                    $status_labels = [
                                        'received' => 'Reçue',
                                        'validating' => 'Validation',
                                        'confirmed' => 'Confirmée',
                                        'production' => 'Production',
                                        'shipped' => 'Expédiée'
                                    ];
                                    echo $status_labels[$order['status']] ?? ucfirst($order['status']);
                                ?>
                            </span>
                        </div>

                        <?php if ($is_new): ?>
                            <div style="margin-bottom: 1rem;">
                                <span class="badge bg-danger">
                                    <i class="fas fa-star me-1"></i>Nouvelle
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="order-details">
                            <div class="detail-row">
                                <span class="detail-label">Articles</span>
                                <span class="detail-value"><?php echo $order['item_count']; ?> produits</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Quantité totale</span>
                                <span class="detail-value"><?php echo $order['total_items']; ?> unités</span>
                            </div>
                            <?php if ($order['total_value']): ?>
                            <div class="detail-row">
                                <span class="detail-label">Valeur totale</span>
                                <span class="detail-value"><?php echo number_format($order['total_value'], 2, ',', ' '); ?> €</span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($order['notes']): ?>
                            <div class="order-notes">
                                <span class="notes-label">Notes :</span>
                                <p style="margin: 0; font-size: 0.9rem; color: var(--gray-600);">
                                    <?php echo nl2br(htmlspecialchars(substr($order['notes'], 0, 100))); ?>...
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="order-actions">
                            <a href="order_details.php?id=<?php echo $order['id']; ?>" 
                               class="btn-view">
                                <i class="fas fa-eye me-1"></i>Voir détails
                            </a>
                            <?php if ($order['status'] === 'received'): ?>
                                <button class="btn-cancel" 
                                        onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-times me-1"></i>Annuler
                                </button>
                            <?php endif; ?>
                            <?php if ($order['status'] === 'shipped'): ?>
                                <button class="btn-track" 
                                        onclick="trackOrder(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-truck me-1"></i>Suivre
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

           
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <h4>Aucune commande trouvée</h4>
                <p class="text-muted mb-4">
                    <?php if ($status_filter !== 'all' || $search_filter): ?>
                        Aucune commande ne correspond à vos critères de recherche.
                    <?php else: ?>
                        Vous n'avez pas encore passé de commande.
                    <?php endif; ?>
                </p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="catalog_prv.php" class="btn btn-primary">
                        <i class="fas fa-tshirt me-2"></i>Explorer le catalogue
                    </a>
                    <a href="new_order.php" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-2"></i>Créer une commande
                    </a>
                    <?php if ($status_filter !== 'all' || $search_filter): ?>
                        <a href="orders.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Effacer les filtres
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="status-guide">
            <h3 class="guide-title">
                <i class="fas fa-info-circle"></i> Guide des statuts
            </h3>
            
            <div class="guide-grid">
                <div class="guide-item">
                    <span class="status-badge badge-received">Reçue</span>
                    <div class="guide-info">
                        <small>Commande reçue, en attente de validation</small>
                    </div>
                </div>
                <div class="guide-item">
                    <span class="status-badge badge-validating">Validation</span>
                    <div class="guide-info">
                        <small>En cours de validation par l'équipe FUS</small>
                    </div>
                </div>
                <div class="guide-item">
                    <span class="status-badge badge-confirmed">Confirmée</span>
                    <div class="guide-info">
                        <small>Commande confirmée, préparation de la production</small>
                    </div>
                </div>
                <div class="guide-item">
                    <span class="status-badge badge-production">Production</span>
                    <div class="guide-info">
                        <small>En production dans notre usine</small>
                    </div>
                </div>
                <div class="guide-item">
                    <span class="status-badge badge-shipped">Expédiée</span>
                    <div class="guide-info">
                        <small>Expédiée, en transit vers vous</small>
                    </div>
                </div>
            </div>
            
            <div class="guide-footer">
                <i class="fas fa-clock me-1"></i>
                Délai moyen de traitement : 3-5 jours ouvrés pour la validation, 15-30 jours pour la production
            </div>
        </div>

        <div class="footer">
            <div>
                <i class="fas fa-gem" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Gestion des commandes
            </div>
            <div>
                <span class="system-status">
                    <i class="fas fa-circle"></i> <?php echo $stats['total'] ?? 0; ?> commandes au total
                </span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cancelOrder(orderId) {
            if (confirm('Êtes-vous sûr de vouloir annuler cette commande ? Cette action est irréversible.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'process_cancel_order.php';
                
                const orderIdInput = document.createElement('input');
                orderIdInput.type = 'hidden';
                orderIdInput.name = 'order_id';
                orderIdInput.value = orderId;
                
                form.appendChild(orderIdInput);
                document.body.appendChild(form);
                form.submit();
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
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
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
            if (e.key === 'n' && e.ctrlKey) {
                e.preventDefault();
                window.location.href = 'new_order.php';
            }
            if (e.key === 'f' && e.ctrlKey) {
                e.preventDefault();
                document.querySelector('input[name="search"]').focus();
            }
            if (e.key === 'r' && e.ctrlKey) {
                e.preventDefault();
                window.location.href = 'orders.php';
            }
            if (e.key === 'e' && e.ctrlKey) {
                e.preventDefault();
                window.location.href = 'export_orders.php';
            }
        });
    </script>
</body>
</html>