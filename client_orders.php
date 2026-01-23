<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
$database = new Database();
$conn = $database->getConnection();
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: clients.php');
    exit();
}

$client_id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT id, company_name, email, contact_person, country, is_active FROM users WHERE id = ? AND role = 'client'");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();
$stmt->close();

if (!$client) {
    header('Location: clients.php');
    exit();
}
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$query = "SELECT o.* FROM orders o WHERE o.client_id = ? ";
$params = [$client_id];
$types = 'i';

if ($search) {
    $query .= " AND (o.reference LIKE ? OR o.shipping_address LIKE ?) ";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ss';
}

if ($status_filter) {
    $query .= " AND o.status = ? ";
    $params[] = $status_filter;
    $types .= 's';
}

if ($date_from) {
    $query .= " AND DATE(o.created_at) >= ? ";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $query .= " AND DATE(o.created_at) <= ? ";
    $params[] = $date_to;
    $types .= 's';
}

$query .= " ORDER BY o.created_at DESC";
$stmt = $conn->prepare($query);
if ($types === 'i') {
    $stmt->bind_param($types, $params[0]);
} else {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$orders = [];

if ($result) {
    while($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
$total_orders = count($orders);
$total_value = array_reduce($orders, function($carry, $order) {
    return $carry + ($order['total_value'] ?: 0);
}, 0);

$status_stats = [
    'received' => 0,
    'production' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

foreach ($orders as $order) {
    if (isset($status_stats[$order['status']])) {
        $status_stats[$order['status']]++;
    }
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes Client - FUS Denim</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
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

        /* Sidebar Navigation */
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
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
        }

        .stat-box:nth-child(2)::before {
            background: linear-gradient(90deg, var(--accent-5), var(--accent-3));
        }

        .stat-box:nth-child(3)::before {
            background: linear-gradient(90deg, var(--accent-2), var(--accent-3));
        }

        .stat-box:nth-child(4)::before {
            background: linear-gradient(90deg, var(--accent-4), var(--accent-1));
        }

        .stat-box:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-label {
            color: var(--gray-500);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .stat-box:nth-child(1) .stat-icon {
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-1);
        }

        .stat-box:nth-child(2) .stat-icon {
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent-5);
        }

        .stat-box:nth-child(3) .stat-icon {
            background: rgba(139, 92, 246, 0.1);
            color: var(--accent-2);
        }

        .stat-box:nth-child(4) .stat-icon {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-trend {
            font-size: 0.85rem;
            color: var(--accent-4);
            font-weight: 600;
        }

        /* Client Info */
        .client-info {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .client-avatar {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--white);
            flex-shrink: 0;
        }

        .client-details {
            flex: 1;
        }

        .client-details h3 {
            margin-bottom: 0.5rem;
            color: var(--primary);
        }

        .client-details p {
            color: var(--gray-500);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .client-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .client-status.active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .client-status.inactive {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
        }

        /* Filter Section */
        .filter-section {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
        }

        .filter-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .search-box {
            position: relative;
        }

        .search-box .form-control {
            padding-left: 3rem;
            border-radius: 10px;
            border: 1px solid var(--gray-300);
            transition: all 0.3s;
        }

        .search-box .form-control:focus {
            border-color: var(--accent-1);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            z-index: 2;
        }

        .form-select {
            border-radius: 10px;
            border: 1px solid var(--gray-300);
            transition: all 0.3s;
        }

        .form-select:focus {
            border-color: var(--accent-1);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.1);
        }

        /* Card Modern */
        .card-modern {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            transition: all 0.3s ease;
        }

        .card-modern:hover {
            box-shadow: var(--shadow-lg);
            border-color: var(--gray-200);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-100);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title i {
            color: var(--accent-1);
        }

        .card-action {
            padding: 0.5rem 1rem;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            color: var(--accent-1);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .card-action:hover {
            background: var(--accent-1);
            color: var(--white);
            border-color: var(--accent-1);
        }

        /* Table */
        .table-wrapper {
            overflow-x: auto;
        }

        .table-modern {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .table-modern thead th {
            background: var(--gray-50);
            padding: 1rem;
            text-align: left;
            font-weight: 700;
            color: var(--gray-600);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--gray-200);
        }

        .table-modern tbody tr {
            border-bottom: 1px solid var(--gray-100);
            transition: background 0.2s ease;
        }

        .table-modern tbody tr:hover {
            background: var(--gray-50);
        }

        .table-modern td {
            padding: 1rem;
            color: var(--gray-700);
            vertical-align: middle;
        }

        /* Status Badge */
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
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent-5);
        }

        .badge-production {
            background: rgba(139, 92, 246, 0.1);
            color: var(--accent-2);
        }

        .badge-shipped {
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-1);
        }

        .badge-delivered {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .badge-cancelled {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
        }

        /* Action Buttons */
        .btn-action-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: nowrap;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--gray-200);
            background: var(--white);
            color: var(--gray-600);
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .btn-view:hover {
            background: var(--accent-1);
            color: var(--white);
            border-color: var(--accent-1);
        }

        .btn-edit:hover {
            background: var(--accent-4);
            color: var(--white);
            border-color: var(--accent-4);
        }

        .btn-delete:hover {
            background: #EF4444;
            color: var(--white);
            border-color: #EF4444;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--gray-400);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state p {
            color: var(--gray-500);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .action-btn {
            padding: 1.5rem 1rem;
            background: linear-gradient(135deg, var(--gray-50), var(--gray-100));
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: var(--primary);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .action-btn:hover {
            transform: translateY(-4px);
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: var(--white);
            border-color: var(--accent-1);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.2);
        }

        .action-btn i {
            display: block;
            font-size: 1.75rem;
            margin-bottom: 0.75rem;
        }

        .action-btn span {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Footer */
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

        .system-status {
            color: var(--accent-4);
            font-weight: 600;
        }

        /* Animations */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-box {
            animation: slideInUp 0.5s ease-out forwards;
        }

        .stat-box:nth-child(1) { animation-delay: 0.1s; }
        .stat-box:nth-child(2) { animation-delay: 0.2s; }
        .stat-box:nth-child(3) { animation-delay: 0.3s; }
        .stat-box:nth-child(4) { animation-delay: 0.4s; }

        .card-modern {
            animation: slideInUp 0.5s ease-out forwards;
            animation-delay: 0.5s;
        }

        /* Responsive */
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

            .client-info {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }

            .filter-group {
                grid-template-columns: 1fr;
            }

            .btn-action-group {
                flex-wrap: wrap;
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

            .stat-value {
                font-size: 1.5rem;
            }

            .client-avatar {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-bolt"></i>
            <h2>FUS Admin</h2>
        </div>

        <div class="nav-section">
            <div class="nav-label">Menu Principal</div>
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-chart-line"></i>
                <span>Tableau de bord</span>
            </a>
            <a href="clients.php" class="nav-item active">
                <i class="fas fa-users"></i>
                <span>Clients</span>
            </a>
            <a href="orders.php" class="nav-item">
                <i class="fas fa-shopping-bag"></i>
                <span>Commandes</span>
            </a>
            <a href="collection.php" class="nav-item">
                <i class="fas fa-layer-group"></i>
                <span>Collections</span>
            </a>
            <a href="products.php" class="nav-item">
                <i class="fas fa-box"></i>
                <span>Produits</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-label">Gestion</div>
            <a href="access_requests.php" class="nav-item">
                <i class="fas fa-key"></i>
                <span>Accès</span>
            </a>
            <a href="messages.php" class="nav-item">
                <i class="fas fa-envelope"></i>
                <span>Messages</span>
            </a>
            
            <a href="settings.php" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Paramètres</span>
            </a>
        </div>

        <div class="sidebar-user">
            <div class="user-card">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-info">
                    <small>Connecté</small>
                    <strong><?php echo htmlspecialchars(substr($_SESSION['user_email'], 0, 20)); ?></strong>
                </div>
            </div>
            <a href="login.php?action=logout" class="logout-btn">
                <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <h1>Commandes du client</h1>
                <p>Historique et gestion des commandes</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
            </div>
        </div>

        <!-- Informations client -->
        <div class="client-info">
            <div class="client-avatar">
                <i class="fas fa-building"></i>
            </div>
            <div class="client-details">
                <h3><?php echo htmlspecialchars($client['company_name']); ?></h3>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($client['email']); ?></p>
                <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($client['contact_person']); ?></p>
                <p><i class="fas fa-globe"></i> <?php echo htmlspecialchars($client['country']); ?></p>
                <div class="mt-2">
                    <span class="client-status <?php echo $client['is_active'] ? 'active' : 'inactive'; ?>">
                        <i class="fas fa-circle"></i>
                        <?php echo $client['is_active'] ? 'Actif' : 'Inactif'; ?>
                    </span>
                    <a href="client_edit.php?id=<?php echo $client_id; ?>" class="card-action ms-3">
                        <i class="fas fa-edit me-2"></i>Modifier
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Total commandes</div>
                    <div class="stat-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $total_orders; ?></div>
                <div class="stat-trend">Historique complet</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Valeur totale</div>
                    <div class="stat-icon">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($total_value, 0, ',', ' '); ?> €</div>
                <div class="stat-trend">Depuis création</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">En production</div>
                    <div class="stat-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $status_stats['production']; ?></div>
                <div class="stat-trend">En cours</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Livrées</div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $status_stats['delivered'] + $status_stats['shipped']; ?></div>
                <div class="stat-trend">Terminées</div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filter-section">
            <form method="GET" class="filter-group">
                <input type="hidden" name="id" value="<?php echo $client_id; ?>">
                
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" name="search" placeholder="Rechercher par référence ou adresse..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div>
                    <label class="form-label" style="font-weight: 600; color: var(--gray-600); margin-bottom: 0.5rem;">Statut</label>
                    <select class="form-select" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="received" <?php echo $status_filter === 'received' ? 'selected' : ''; ?>>Reçue</option>
                        <option value="production" <?php echo $status_filter === 'production' ? 'selected' : ''; ?>>En production</option>
                        <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Expédiée</option>
                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Livrée</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Annulée</option>
                    </select>
                </div>
                
                <div>
                    <label class="form-label" style="font-weight: 600; color: var(--gray-600); margin-bottom: 0.5rem;">Du</label>
                    <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                
                <div>
                    <label class="form-label" style="font-weight: 600; color: var(--gray-600); margin-bottom: 0.5rem;">Au</label>
                    <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="card-action" style="margin: 0; flex: 1;">
                        <i class="fas fa-filter me-2"></i>Filtrer
                    </button>
                    <?php if ($search || $status_filter || $date_from || $date_to): ?>
                    <a href="client_orders.php?id=<?php echo $client_id; ?>" class="card-action" style="background: var(--gray-100); margin: 0;">
                        <i class="fas fa-times me-2"></i>Réinitialiser
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tableau des commandes -->
        <div class="card-modern">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-list"></i> Liste des commandes
                </div>
                <a href="order.php?action=new&client_id=<?php echo $client_id; ?>" class="card-action">
                    <i class="fas fa-plus me-2"></i>Nouvelle commande
                </a>
            </div>
            
            <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <h5 class="mt-3 mb-2">Aucune commande trouvée</h5>
                <p class="text-muted mb-3">
                    <?php if ($search || $status_filter || $date_from || $date_to): ?>
                    Essayez de modifier vos critères de recherche
                    <?php else: ?>
                    Ce client n'a pas encore passé de commande
                    <?php endif; ?>
                </p>
                <?php if ($search || $status_filter || $date_from || $date_to): ?>
                <a href="client_orders.php?id=<?php echo $client_id; ?>" class="card-action" style="display: inline-block;">
                    Voir toutes les commandes
                </a>
                <?php else: ?>
                <a href="order.php?action=new&client_id=<?php echo $client_id; ?>" class="card-action" style="display: inline-block;">
                    <i class="fas fa-plus me-2"></i>Créer une commande
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Date</th>
                            <th>Produits</th>
                            <th>Valeur</th>
                            <th>Statut</th>
                            <th>Livraison</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $order): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($order['reference']); ?></strong>
                            </td>
                            <td>
                                <div><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></div>
                                <small class="text-muted"><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                            </td>
                            <td>
                                <?php 
                                // Simuler le nombre de produits (à adapter selon votre structure)
                                $product_count = rand(1, 10);
                                ?>
                                <span class="text-muted"><?php echo $product_count; ?> produit<?php echo $product_count > 1 ? 's' : ''; ?></span>
                            </td>
                            <td>
                                <strong><?php echo number_format($order['total_value'] ?: 0, 2, ',', ' '); ?> €</strong>
                            </td>
                            <td>
                                <?php
                                $status_classes = [
                                    'received' => 'badge-received',
                                    'production' => 'badge-production',
                                    'shipped' => 'badge-shipped',
                                    'delivered' => 'badge-delivered',
                                    'cancelled' => 'badge-cancelled'
                                ];
                                $status_labels = [
                                    'received' => 'Reçue',
                                    'production' => 'Production',
                                    'shipped' => 'Expédiée',
                                    'delivered' => 'Livrée',
                                    'cancelled' => 'Annulée'
                                ];
                                ?>
                                <span class="status-badge <?php echo $status_classes[$order['status']] ?? 'badge-received'; ?>">
                                    <?php echo $status_labels[$order['status']] ?? 'Reçue'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($order['estimated_delivery']): ?>
                                <div><?php echo date('d/m/Y', strtotime($order['estimated_delivery'])); ?></div>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-action-group">
                                    <a href="order_view.php?id=<?php echo $order['id']; ?>" 
                                       class="btn-action btn-view" title="Voir détails">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="order_edit.php?id=<?php echo $order['id']; ?>" 
                                       class="btn-action btn-edit" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="order_print.php?id=<?php echo $order['id']; ?>" 
                                       target="_blank" class="btn-action" title="Imprimer" style="color: var(--accent-5);">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Statistiques détaillées -->
            <div class="mt-4 pt-4 border-top">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-3" style="color: var(--gray-600);">Répartition par statut</h6>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach($status_stats as $status => $count): ?>
                            <?php if ($count > 0): ?>
                            <?php
                            $status_labels = [
                                'received' => 'Reçues',
                                'production' => 'En production',
                                'shipped' => 'Expédiées',
                                'delivered' => 'Livrées',
                                'cancelled' => 'Annulées'
                            ];
                            $percentage = $total_orders > 0 ? ($count / $total_orders * 100) : 0;
                            ?>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted"><?php echo $status_labels[$status] ?? $status; ?></span>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width: 100px; height: 6px; background: var(--gray-200); border-radius: 3px; overflow: hidden;">
                                        <div style="width: <?php echo $percentage; ?>%; height: 100%; background: <?php 
                                            echo $status == 'received' ? 'var(--accent-5)' : 
                                                 ($status == 'production' ? 'var(--accent-2)' : 
                                                 ($status == 'shipped' ? 'var(--accent-1)' : 
                                                 ($status == 'delivered' ? 'var(--accent-4)' : '#EF4444'))); 
                                        ?>;"></div>
                                    </div>
                                    <span style="font-weight: 600; min-width: 40px; text-align: right;"><?php echo $count; ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-3" style="color: var(--gray-600);">Export des données</h6>
                        <div class="quick-actions">
                            <a href="export_orders.php?client_id=<?php echo $client_id; ?>&type=csv" class="action-btn">
                                <i class="fas fa-file-csv"></i>
                                <span>Export CSV</span>
                            </a>
                            <a href="export_orders.php?client_id=<?php echo $client_id; ?>&type=pdf" class="action-btn">
                                <i class="fas fa-file-pdf"></i>
                                <span>Export PDF</span>
                            </a>
                            
                            <a href="clients.php" class="action-btn">
                                <i class="fas fa-arrow-left"></i>
                                <span>Retour clients</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>
                <i class="fas fa-shield-alt" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Back-office Administrateur v1.0
            </div>
            <div>
                <span class="system-status">
                    <i class="fas fa-circle"></i> Système opérationnel
                </span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mise à jour de l'heure en temps réel
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

        // Active nav item based on current page
        const currentPage = window.location.pathname.split('/').pop() || 'dashboard.php';
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.getAttribute('href') === currentPage) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Animations des nombres
        const animateValue = (element, start, end, duration) => {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                element.textContent = Math.floor(progress * (end - start) + start);
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        };

        // Observer pour déclencher les animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const value = entry.target.querySelector('.stat-value');
                    if (value && !value.dataset.animated) {
                        const text = value.textContent;
                        const finalValue = parseInt(text.replace(/\s/g, '').replace('€', ''));
                        if (!isNaN(finalValue)) {
                            animateValue(value, 0, finalValue, 800);
                            value.dataset.animated = 'true';
                        }
                    }
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.stat-box').forEach(box => observer.observe(box));

        // Date pickers defaults
        const dateFromInput = document.querySelector('input[name="date_from"]');
        const dateToInput = document.querySelector('input[name="date_to"]');
        
        if (dateToInput && !dateToInput.value) {
            const today = new Date();
            dateToInput.value = today.toISOString().split('T')[0];
        }
        
        if (dateFromInput && !dateFromInput.value) {
            const thirtyDaysAgo = new Date();
            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
            dateFromInput.value = thirtyDaysAgo.toISOString().split('T')[0];
        }

        // Recherche en temps réel avec délai
        let searchTimeout;
        const searchInput = document.querySelector('input[name="search"]');
        
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length > 2 || this.value.length === 0) {
                        this.form.submit();
                    }
                }, 500);
            });
        }
    </script>
</body>
</html>