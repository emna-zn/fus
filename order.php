<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
$database = new Database();
$conn = $database->getConnection();

$message = '';
$message_type = '';
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        if (!empty($admin_notes)) {
            $conn->query("
                CREATE TABLE IF NOT EXISTS order_notes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT,
                    note TEXT,
                    added_by VARCHAR(50),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
                )
            ");
            
            $stmt2 = $conn->prepare("INSERT INTO order_notes (order_id, note, added_by) VALUES (?, ?, 'admin')");
            $stmt2->bind_param("is", $order_id, $admin_notes);
            $stmt2->execute();
            $stmt2->close();
        }
        
        $message = "Statut de la commande mis à jour.";
        $message_type = 'success';
    } else {
        $message = "Erreur lors de la mise à jour du statut.";
        $message_type = 'danger';
    }
    $stmt->close();
}
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$client_filter = isset($_GET['client_id']) ? intval($_GET['client_id']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$query = "
    SELECT o.*, u.company_name, u.contact_person, u.email, u.phone, u.country 
    FROM orders o 
    JOIN users u ON o.client_id = u.id 
    WHERE 1=1
";

$params = [];
$types = '';

if ($search) {
    $query .= " AND (o.reference LIKE ? OR u.company_name LIKE ? OR u.contact_person LIKE ?) ";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

if ($status_filter) {
    $query .= " AND o.status = ? ";
    $params[] = $status_filter;
    $types .= 's';
}

if ($client_filter) {
    $query .= " AND o.client_id = ? ";
    $params[] = $client_filter;
    $types .= 'i';
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
if ($params) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

$orders = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
$stats_result = $conn->query("
    SELECT status, COUNT(*) as count 
    FROM orders 
    GROUP BY status
");
$status_stats = [];
if ($stats_result) {
    while($row = $stats_result->fetch_assoc()) {
        $status_stats[$row['status']] = $row['count'];
    }
}
$clients_result = $conn->query("
    SELECT id, company_name 
    FROM users 
    WHERE role = 'client' AND is_active = 1 
    ORDER BY company_name
");
$clients = [];
if ($clients_result) {
    while($row = $clients_result->fetch_assoc()) {
        $clients[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes - Tableau de bord Admin - FUS Denim</title>
    
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

        .nav-badge {
            margin-left: auto;
            background: linear-gradient(135deg, var(--accent-3), var(--accent-1));
            color: var(--white);
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
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

        .stat-box:nth-child(5)::before {
            background: linear-gradient(90deg, var(--accent-3), var(--accent-2));
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
            font-size: 1.25rem;
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
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .stat-box:nth-child(4) .stat-icon {
            background: rgba(139, 92, 246, 0.1);
            color: var(--accent-2);
        }

        .stat-box:nth-child(5) .stat-icon {
            background: rgba(236, 72, 153, 0.1);
            color: var(--accent-3);
        }

        .stat-value {
            font-size: 2rem;
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

        /* Card Modern */
        .card-modern {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            transition: all 0.3s ease;
            margin-bottom: 2rem;
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

        /* Filter Form */
        .filter-form .form-control {
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .filter-form .form-control:focus {
            border-color: var(--accent-1);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-box {
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            z-index: 1;
        }

        .search-box .form-control {
            padding-left: 2.5rem;
        }

        /* Table */
        .table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--gray-200);
        }

        .table-modern {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .table-modern thead th {
            background: var(--gray-50);
            padding: 1rem 1.5rem;
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
            padding: 1rem 1.5rem;
            color: var(--gray-700);
            vertical-align: middle;
        }

        .table-modern a {
            color: var(--accent-1);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .table-modern a:hover {
            color: var(--accent-2);
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

        .badge-validating {
            background: rgba(13, 202, 240, 0.1);
            color: #0c6b7e;
        }

        .badge-confirmed {
            background: rgba(25, 135, 84, 0.1);
            color: #146c43;
        }

        .badge-production {
            background: rgba(108, 117, 125, 0.1);
            color: #4a5158;
        }

        .badge-shipped {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .badge-active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .badge-inactive {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
        }

        /* Order Reference */
        .order-ref {
            font-family: 'Inter', monospace;
            font-weight: 600;
            color: var(--primary);
            background: var(--gray-100);
            padding: 0.25rem 0.75rem;
            border-radius: 8px;
            display: inline-block;
            font-size: 0.9rem;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-600);
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            color: var(--white);
            background: var(--accent-1);
            border-color: var(--accent-1);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }

        /* Status Select */
        .status-select {
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            background: var(--white);
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .status-select:focus {
            outline: none;
            border-color: var(--accent-1);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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

        /* Modern Buttons */
        .btn-modern {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: var(--white);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
            color: var(--white);
        }

        .btn-outline-modern {
            border: 1px solid var(--gray-300);
            background: transparent;
            color: var(--gray-600);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-outline-modern:hover {
            border-color: var(--accent-1);
            color: var(--accent-1);
            background: rgba(59, 130, 246, 0.05);
        }

        /* Alert */
        .alert-modern {
            border-radius: 12px;
            border: 1px solid;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            animation: slideInUp 0.5s ease-out;
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

        .stat-box, .card-modern, .alert-modern {
            animation: slideInUp 0.5s ease-out forwards;
        }

        .stat-box:nth-child(1) { animation-delay: 0.1s; }
        .stat-box:nth-child(2) { animation-delay: 0.2s; }
        .stat-box:nth-child(3) { animation-delay: 0.3s; }
        .stat-box:nth-child(4) { animation-delay: 0.4s; }
        .stat-box:nth-child(5) { animation-delay: 0.5s; }

        /* Responsive */
        @media (max-width: 1200px) {
            .sidebar {
                width: 260px;
            }

            .main-content {
                margin-left: 260px;
                padding: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
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
                gap: 1rem;
            }

            .table-wrapper {
                font-size: 0.875rem;
            }

            .table-modern thead th,
            .table-modern td {
                padding: 0.75rem;
            }

            .card-modern {
                padding: 1.25rem;
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
                font-size: 1.75rem;
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
            <a href="clients.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Clients</span>
            </a>
            <a href="order.php" class="nav-item active">
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
            <a href="login.php" class="logout-btn">
                <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <h1>Gestion des Commandes</h1>
                <p>Suivez et gérez toutes les commandes clients</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
                <a href="order_create.php" class="btn-modern">
                    <i class="fas fa-plus-circle"></i> Nouvelle commande
                </a>
            </div>
        </div>

        <!-- Alert Message -->
        <?php if ($message): ?>
        <div class="alert-modern alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                <span><?php echo $message; ?></span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Commandes reçues</div>
                    <div class="stat-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $status_stats['received'] ?? 0; ?></div>
                <div class="stat-trend">En attente</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">En validation</div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $status_stats['validating'] ?? 0; ?></div>
                <div class="stat-trend">À valider</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Confirmées</div>
                    <div class="stat-icon">
                        <i class="fas fa-check-double"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $status_stats['confirmed'] ?? 0; ?></div>
                <div class="stat-trend">Validées</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">En production</div>
                    <div class="stat-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $status_stats['production'] ?? 0; ?></div>
                <div class="stat-trend">En cours</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Expédiées</div>
                    <div class="stat-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $status_stats['shipped'] ?? 0; ?></div>
                <div class="stat-trend">Livrées</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card-modern">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-filter"></i> Filtres de recherche
                </div>
                <?php if ($search || $status_filter || $client_filter || $date_from || $date_to): ?>
                <a href="order.php" class="btn-outline-modern">
                    <i class="fas fa-times"></i> Réinitialiser
                </a>
                <?php endif; ?>
            </div>
            
            <form method="GET" class="row g-3 filter-form">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Rechercher une commande..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="received" <?php echo $status_filter == 'received' ? 'selected' : ''; ?>>Reçue</option>
                        <option value="validating" <?php echo $status_filter == 'validating' ? 'selected' : ''; ?>>En validation</option>
                        <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmée</option>
                        <option value="production" <?php echo $status_filter == 'production' ? 'selected' : ''; ?>>En production</option>
                        <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Expédiée</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="client_id">
                        <option value="">Tous les clients</option>
                        <?php foreach($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>" 
                                <?php echo $client_filter == $client['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client['company_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" name="date_from" 
                           value="<?php echo htmlspecialchars($date_from); ?>" 
                           placeholder="Date de début">
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" name="date_to" 
                           value="<?php echo htmlspecialchars($date_to); ?>" 
                           placeholder="Date de fin">
                </div>
                <div class="col-md-12 mt-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-modern">
                            <i class="fas fa-filter"></i> Appliquer les filtres
                        </button>
                        <a href="export_orders.php" class="btn-outline-modern">
                            <i class="fas fa-download"></i> Exporter
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="card-modern">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-shopping-bag"></i> Liste des commandes
                    <span class="ms-2 text-muted" style="font-size: 0.9rem; font-weight: 400;">
                        (<?php echo count($orders); ?> commande<?php echo count($orders) > 1 ? 's' : ''; ?>)
                    </span>
                </div>
                <a href="order_create.php" class="card-action">
                    <i class="fas fa-plus me-1"></i> Nouvelle
                </a>
            </div>
            
            <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <h5 class="mt-3 mb-2">Aucune commande trouvée</h5>
                <p class="text-muted mb-4">Commencez par créer une nouvelle commande</p>
                <?php if ($search || $status_filter || $client_filter || $date_from || $date_to): ?>
                <a href="order.php" class="btn-outline-modern">
                    Voir toutes les commandes
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th width="120">Référence</th>
                            <th>Client</th>
                            <th width="100">Articles</th>
                            <th width="120">Valeur</th>
                            <th width="120">Date</th>
                            <th width="150">Statut</th>
                            <th width="120">Livraison</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $order): ?>
                        <tr>
                            <td>
                                <span class="order-ref"><?php echo htmlspecialchars($order['reference']); ?></span>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars(substr($order['company_name'], 0, 25)); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars(substr($order['contact_person'], 0, 20)); ?></small>
                            </td>
                            <td><?php echo $order['total_items']; ?> pièces</td>
                            <td>
                                <strong><?php echo number_format($order['total_value'], 2, ',', ' '); ?> €</strong>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                            <td>
                                <form method="POST" class="d-inline-block">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <select name="status" class="status-select" 
                                            onchange="if(confirm('Changer le statut de cette commande ?')) this.form.submit()"
                                            data-order-id="<?php echo $order['id']; ?>">
                                        <option value="received" <?php echo $order['status'] == 'received' ? 'selected' : ''; ?>>Reçue</option>
                                        <option value="validating" <?php echo $order['status'] == 'validating' ? 'selected' : ''; ?>>Validation</option>
                                        <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmée</option>
                                        <option value="production" <?php echo $order['status'] == 'production' ? 'selected' : ''; ?>>Production</option>
                                        <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Expédiée</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                                <span class="badge badge-<?php echo $order['status']; ?> ms-2 mt-1 d-inline-block">
                                    <?php 
                                    $status_labels = [
                                        'received' => 'Reçue',
                                        'validating' => 'Validation',
                                        'confirmed' => 'Confirmée',
                                        'production' => 'Production',
                                        'shipped' => 'Expédiée'
                                    ];
                                    echo $status_labels[$order['status']];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($order['estimated_delivery']): ?>
                                    <?php 
                                    $delivery_date = new DateTime($order['estimated_delivery']);
                                    $today = new DateTime();
                                    $interval = $today->diff($delivery_date);
                                    $days_left = $interval->format('%r%a');
                                    
                                    if ($days_left > 0) {
                                        echo '<span class="text-success fw-semibold">' . date('d/m/Y', strtotime($order['estimated_delivery'])) . '</span>';
                                        echo '<br><small class="text-muted">(' . $days_left . ' jours)</small>';
                                    } elseif ($days_left == 0) {
                                        echo '<span class="text-warning fw-semibold">Aujourd\'hui</span>';
                                    } else {
                                        echo '<span class="text-danger fw-semibold">' . date('d/m/Y', strtotime($order['estimated_delivery'])) . '</span>';
                                        echo '<br><small class="text-danger">retard</small>';
                                    }
                                    ?>
                                <?php else: ?>
                                    <span class="text-muted">Non définie</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="order_view.php?id=<?php echo $order['id']; ?>" 
                                       class="btn-action" title="Détails">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="order_edit.php?id=<?php echo $order['id']; ?>" 
                                       class="btn-action" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn-action" 
                                            title="Notes" 
                                            onclick="showNotesModal(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-comment"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    <i class="fas fa-chart-bar me-2"></i>
                    Total : <?php echo count($orders); ?> commande<?php echo count($orders) > 1 ? 's' : ''; ?>
                    <?php 
                    $total_value = array_reduce($orders, function($carry, $order) {
                        return $carry + $order['total_value'];
                    }, 0);
                    ?>
                    • CA : <?php echo number_format($total_value, 2, ',', ' '); ?> €
                </div>
                <div>
                    <button class="btn-outline-modern btn-sm" onclick="printOrders()">
                        <i class="fas fa-print me-2"></i>Imprimer
                    </button>
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

    <!-- Modal pour les notes -->
    <div class="modal fade" id="notesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Notes pour la commande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="notesForm">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="modalOrderId">
                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">Ajouter une note</label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" 
                                      placeholder="Ajoutez une note interne..."></textarea>
                            <div class="form-text">Cette note sera visible uniquement par les administrateurs</div>
                        </div>
                        <div id="existingNotes"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" name="update_status" class="btn-modern">Enregistrer la note</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh toutes les 60 secondes
        setTimeout(function() {
            location.reload();
        }, 60000);

        // Modal pour les notes
        function showNotesModal(orderId) {
            document.getElementById('modalOrderId').value = orderId;
            
            // Charger les notes existantes
            fetch('get_order_notes.php?order_id=' + orderId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('existingNotes').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('existingNotes').innerHTML = 
                        '<div class="alert alert-info">Aucune note pour le moment</div>';
                });
            
            const modal = new bootstrap.Modal(document.getElementById('notesModal'));
            modal.show();
        }
        
        // Impression
        function printOrders() {
            const printContent = document.querySelector('.card-modern').outerHTML;
            const originalContent = document.body.innerHTML;
            
            document.body.innerHTML = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>FUS Denim - Commandes</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { padding: 20px; font-family: 'Inter', sans-serif; }
                        .table { font-size: 12px; }
                        .print-header { margin-bottom: 30px; text-align: center; border-bottom: 2px solid #3B82F6; padding-bottom: 20px; }
                        .print-header h3 { color: #3B82F6; font-weight: 700; }
                    </style>
                </head>
                <body>
                    <div class="print-header">
                        <h3>FUS Denim - Liste des commandes</h3>
                        <p>Imprimé le <?php echo date('d/m/Y H:i'); ?></p>
                    </div>
                    ${printContent}
                </body>
                </html>
            `;
            
            window.print();
            document.body.innerHTML = originalContent;
            window.location.reload();
        }

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

        // Animation pour les statistiques
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
                        const finalValue = parseInt(value.textContent.replace(/\s/g, ''));
                        animateValue(value, 0, finalValue, 800);
                        value.dataset.animated = 'true';
                    }
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.stat-box').forEach(box => observer.observe(box));
    </script>
</body>
</html>