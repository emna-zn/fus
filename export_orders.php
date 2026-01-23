<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
$database = new Database();
$conn = $database->getConnection();
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$type = isset($_GET['type']) && in_array($_GET['type'], ['csv', 'pdf', 'excel']) ? $_GET['type'] : 'csv';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$query = "SELECT o.*, u.company_name, u.contact_person, u.country 
          FROM orders o 
          JOIN users u ON o.client_id = u.id 
          WHERE u.role = 'client' ";
$params = [];
$types = '';

if ($client_id > 0) {
    $query .= " AND o.client_id = ? ";
    $params[] = $client_id;
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

if ($status) {
    $query .= " AND o.status = ? ";
    $params[] = $status;
    $types .= 's';
}

$query .= " ORDER BY o.created_at DESC";

// Exécuter la requête
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
if ($type === 'pdf') {
    require_once 'autoload.php';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="commandes_' . date('Y-m-d') . '.pdf"');
    
    $pdf_content = "<h1>Export des commandes - FUS Denim</h1>";
    $pdf_content .= "<p>Date d'export : " . date('d/m/Y H:i') . "</p>";
    $pdf_content .= "<p>Nombre de commandes : " . count($orders) . "</p>";
    echo $pdf_content;
    exit;
}
if ($type === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="commandes_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Référence',
        'Date',
        'Client',
        'Contact',
        'Pays',
        'Produits',
        'Valeur HT',
        'Valeur TTC',
        'Statut',
        'Date livraison estimée',
        'Adresse livraison'
    ], ';');
    foreach ($orders as $order) {
        fputcsv($output, [
            $order['reference'],
            date('d/m/Y', strtotime($order['created_at'])),
            $order['company_name'],
            $order['contact_person'],
            $order['country'],
            'Produits', 
            number_format($order['total_value'] * 0.8, 2, ',', ''),
            number_format($order['total_value'], 2, ',', ''), 
            ucfirst($order['status']),
            $order['estimated_delivery'] ? date('d/m/Y', strtotime($order['estimated_delivery'])) : '',
            substr($order['shipping_address'], 0, 100)
        ], ';');
    }
    
    fclose($output);
    exit;
}
if ($type === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="commandes_' . date('Y-m-d') . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr>";
    echo "<th>Référence</th>";
    echo "<th>Date</th>";
    echo "<th>Client</th>";
    echo "<th>Contact</th>";
    echo "<th>Pays</th>";
    echo "<th>Valeur TTC</th>";
    echo "<th>Statut</th>";
    echo "<th>Date livraison estimée</th>";
    echo "</tr>";
    
    foreach ($orders as $order) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($order['reference']) . "</td>";
        echo "<td>" . date('d/m/Y', strtotime($order['created_at'])) . "</td>";
        echo "<td>" . htmlspecialchars($order['company_name']) . "</td>";
        echo "<td>" . htmlspecialchars($order['contact_person']) . "</td>";
        echo "<td>" . htmlspecialchars($order['country']) . "</td>";
        echo "<td>" . number_format($order['total_value'], 2, ',', ' ') . " €</td>";
        echo "<td>" . ucfirst($order['status']) . "</td>";
        echo "<td>" . ($order['estimated_delivery'] ? date('d/m/Y', strtotime($order['estimated_delivery'])) : '') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export des Commandes - FUS Denim</title>
    
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
            text-align: center;
        }

        .stat-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-box:nth-child(1)::before {
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

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 1rem;
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

        .stat-label {
            color: var(--gray-500);
            font-size: 0.9rem;
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

        /* Form Styling */
        .form-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1.5rem;
            padding-left: 1rem;
            border-left: 4px solid var(--accent-1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--accent-1);
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid var(--gray-300);
            padding: 0.875rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-1);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.1);
            outline: none;
        }

        /* Export Options */
        .export-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .export-option {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 2rem;
            border: 2px solid var(--gray-200);
            transition: all 0.3s ease;
            text-align: center;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .export-option:hover {
            transform: translateY(-5px);
            border-color: var(--accent-1);
            box-shadow: var(--shadow-lg);
        }

        .export-option.selected {
            border-color: var(--accent-1);
            background: rgba(59, 130, 246, 0.05);
        }

        .export-icon {
            width: 70px;
            height: 70px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }

        .export-csv .export-icon {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .export-excel .export-icon {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .export-pdf .export-icon {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
        }

        .export-option h5 {
            margin-bottom: 0.75rem;
            color: var(--primary);
        }

        .export-option p {
            color: var(--gray-500);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .export-features {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: left;
        }

        .export-features li {
            padding: 0.25rem 0;
            color: var(--gray-600);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .export-features li i {
            color: var(--accent-4);
            font-size: 0.8rem;
        }

        /* Preview Table */
        .preview-table {
            background: var(--white);
            border-radius: 12px;
            border: 1px solid var(--gray-200);
            overflow: hidden;
            margin-top: 2rem;
        }

        .table-header {
            background: var(--gray-50);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            font-weight: 600;
            color: var(--gray-700);
        }

        .table-content {
            max-height: 300px;
            overflow-y: auto;
        }

        .table-modern {
            width: 100%;
            border-collapse: collapse;
        }

        .table-modern th {
            background: var(--gray-50);
            padding: 1rem;
            text-align: left;
            font-weight: 700;
            color: var(--gray-600);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--gray-200);
            position: sticky;
            top: 0;
        }

        .table-modern td {
            padding: 1rem;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-100);
        }

        .table-modern tr:hover {
            background: var(--gray-50);
        }

        /* Export Button */
        .export-btn {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: var(--white);
            border: none;
            padding: 1rem 3rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3);
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 2rem auto 0;
            cursor: pointer;
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.4);
        }

        .export-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Alert Modern */
        .alert-modern {
            border-radius: 12px;
            border: 1px solid;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
        }

        .alert-modern.alert-info {
            background: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.2);
            color: var(--accent-1);
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

            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }

            .export-options {
                grid-template-columns: 1fr;
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
            <a href="export_orders.php" class="nav-item active">
                <i class="fas fa-download"></i>
                <span>Export</span>
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
                <h1>Export des Commandes</h1>
                <p>Exportez vos données dans différents formats</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-value"><?php echo count($orders); ?></div>
                <div class="stat-label">Commandes trouvées</div>
            </div>

            <div class="stat-box">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value">
                    <?php
                    $unique_clients = array_unique(array_column($orders, 'company_name'));
                    echo count($unique_clients);
                    ?>
                </div>
                <div class="stat-label">Clients</div>
            </div>

            <div class="stat-box">
                <div class="stat-icon">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="stat-value">
                    <?php
                    $total_value = array_reduce($orders, function($carry, $order) {
                        return $carry + ($order['total_value'] ?: 0);
                    }, 0);
                    echo number_format($total_value, 0, ',', ' ');
                    ?>
                </div>
                <div class="stat-label">Valeur totale (€)</div>
            </div>

            <div class="stat-box">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-value">
                    <?php
                    $dates = array_column($orders, 'created_at');
                    if (!empty($dates)) {
                        $min_date = min($dates);
                        $max_date = max($dates);
                        echo date('d/m', strtotime($min_date)) . ' - ' . date('d/m', strtotime($max_date));
                    } else {
                        echo '-';
                    }
                    ?>
                </div>
                <div class="stat-label">Période</div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="card-modern">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-filter"></i> Filtres d'export
                </div>
            </div>
            
            <form id="exportForm" method="GET">
                <input type="hidden" name="type" id="exportType" value="csv">
                
                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-calendar"></i> Période
                    </h4>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="date_from" class="form-label">
                                <i class="fas fa-calendar-plus"></i> Date de début
                            </label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?php echo $date_from ?: date('Y-m-d', strtotime('-30 days')); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="date_to" class="form-label">
                                <i class="fas fa-calendar-minus"></i> Date de fin
                            </label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?php echo $date_to ?: date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-tags"></i> Filtres avancés
                    </h4>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="client_id" class="form-label">
                                <i class="fas fa-user"></i> Client spécifique
                            </label>
                            <select class="form-select" id="client_id" name="client_id">
                                <option value="">Tous les clients</option>
                                <?php
                                $clients_result = $conn->query("SELECT id, company_name FROM users WHERE role = 'client' ORDER BY company_name");
                                if ($clients_result) {
                                    while($client_row = $clients_result->fetch_assoc()) {
                                        $selected = $client_id == $client_row['id'] ? 'selected' : '';
                                        echo '<option value="' . $client_row['id'] . '" ' . $selected . '>' . htmlspecialchars($client_row['company_name']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="status" class="form-label">
                                <i class="fas fa-info-circle"></i> Statut
                            </label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Tous les statuts</option>
                                <option value="received" <?php echo $status === 'received' ? 'selected' : ''; ?>>Reçue</option>
                                <option value="production" <?php echo $status === 'production' ? 'selected' : ''; ?>>En production</option>
                                <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Expédiée</option>
                                <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Livrée</option>
                                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Annulée</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Options d'export -->
                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-file-export"></i> Format d'export
                    </h4>
                    
                    <div class="export-options">
                        <div class="export-option export-csv" onclick="selectExportType('csv')">
                            <div class="export-icon">
                                <i class="fas fa-file-csv"></i>
                            </div>
                            <h5>CSV</h5>
                            <p>Format compatible Excel et logiciels de gestion</p>
                            <ul class="export-features">
                                <li><i class="fas fa-check"></i> Compatible Excel</li>
                                <li><i class="fas fa-check"></i> Léger et rapide</li>
                                <li><i class="fas fa-check"></i> Import facile</li>
                            </ul>
                        </div>
                        
                        <div class="export-option export-excel" onclick="selectExportType('excel')">
                            <div class="export-icon">
                                <i class="fas fa-file-excel"></i>
                            </div>
                            <h5>Excel</h5>
                            <p>Format natif Microsoft Excel avec mise en forme</p>
                            <ul class="export-features">
                                <li><i class="fas fa-check"></i> Format natif Excel</li>
                                <li><i class="fas fa-check"></i> Mise en forme</li>
                                <li><i class="fas fa-check"></i> Graphiques inclus</li>
                            </ul>
                        </div>
                        
                        <div class="export-option export-pdf" onclick="selectExportType('pdf')">
                            <div class="export-icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <h5>PDF</h5>
                            <p>Document professionnel pour impression</p>
                            <ul class="export-features">
                                <li><i class="fas fa-check"></i> Format imprimable</li>
                                <li><i class="fas fa-check"></i> Design professionnel</li>
                                <li><i class="fas fa-check"></i> Sécurisé</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Aperçu des données -->
                <?php if (!empty($orders)): ?>
                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-eye"></i> Aperçu des données
                    </h4>
                    
                    <div class="preview-table">
                        <div class="table-header">
                            <?php echo count($orders); ?> commandes trouvées (affichage limité à 10)
                        </div>
                        <div class="table-content">
                            <table class="table-modern">
                                <thead>
                                    <tr>
                                        <th>Référence</th>
                                        <th>Date</th>
                                        <th>Client</th>
                                        <th>Valeur</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $preview_limit = min(10, count($orders));
                                    for ($i = 0; $i < $preview_limit; $i++): 
                                        $order = $orders[$i];
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['reference']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($order['company_name']); ?></td>
                                        <td><?php echo number_format($order['total_value'], 2, ',', ' '); ?> €</td>
                                        <td>
                                            <span style="padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600; 
                                                  background: <?php 
                                                  echo $order['status'] == 'received' ? 'rgba(245, 158, 11, 0.1)' : 
                                                         ($order['status'] == 'production' ? 'rgba(139, 92, 246, 0.1)' : 
                                                         ($order['status'] == 'shipped' ? 'rgba(59, 130, 246, 0.1)' : 
                                                         ($order['status'] == 'delivered' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)')));
                                                  ?>; 
                                                  color: <?php
                                                  echo $order['status'] == 'received' ? 'var(--accent-5)' : 
                                                         ($order['status'] == 'production' ? 'var(--accent-2)' : 
                                                         ($order['status'] == 'shipped' ? 'var(--accent-1)' : 
                                                         ($order['status'] == 'delivered' ? 'var(--accent-4)' : '#EF4444')));
                                                  ?>;">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endfor; ?>
                                    <?php if (count($orders) > 10): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            ... et <?php echo count($orders) - 10; ?> autres commandes
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Bouton d'export -->
                <div class="text-center">
                    <button type="submit" class="export-btn" id="exportButton" <?php echo empty($orders) ? 'disabled' : ''; ?>>
                        <i class="fas fa-download"></i>
                        <span id="exportButtonText">
                            <?php echo empty($orders) ? 'Aucune donnée à exporter' : 'Exporter les données'; ?>
                        </span>
                    </button>
                    <p class="text-muted mt-2">
                        <small>
                            <i class="fas fa-info-circle"></i> 
                            L'export contiendra <?php echo count($orders); ?> commande<?php echo count($orders) > 1 ? 's' : ''; ?> 
                            pour une valeur totale de <?php echo number_format($total_value, 0, ',', ' '); ?> €
                        </small>
                    </p>
                </div>
            </form>
        </div>

        <!-- Informations -->
        <div class="alert alert-info alert-modern">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle me-3" style="font-size: 1.2rem;"></i>
                <div>
                    <strong>Instructions d'export :</strong>
                    <ul class="mb-0 mt-2">
                        <li>Sélectionnez la période et les filtres souhaités</li>
                        <li>Choisissez le format d'export adapté à vos besoins</li>
                        <li>Le fichier sera téléchargé automatiquement</li>
                        <li>Les données sont exportées en temps réel depuis la base</li>
                        <li>Conservez une copie de vos exports pour l'archivage</li>
                    </ul>
                </div>
            </div>
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

        // Sélection du type d'export
        let selectedType = 'csv';

        function selectExportType(type) {
            selectedType = type;
            document.getElementById('exportType').value = type;
            
            // Mettre à jour les styles
            document.querySelectorAll('.export-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            document.querySelector(`.export-${type}`).classList.add('selected');
            
            // Mettre à jour le texte du bouton
            const buttonText = document.getElementById('exportButtonText');
            const exportButton = document.getElementById('exportButton');
            const orderCount = <?php echo count($orders); ?>;
            
            if (orderCount > 0) {
                const typeNames = {
                    'csv': 'CSV',
                    'excel': 'Excel',
                    'pdf': 'PDF'
                };
                buttonText.textContent = `Exporter en ${typeNames[type]} (${orderCount} commandes)`;
                exportButton.disabled = false;
            }
        }

        // Initialiser la sélection
        document.addEventListener('DOMContentLoaded', function() {
            selectExportType('csv');
            
            // Vérifier s'il y a des données
            const orderCount = <?php echo count($orders); ?>;
            const exportButton = document.getElementById('exportButton');
            const buttonText = document.getElementById('exportButtonText');
            
            if (orderCount === 0) {
                exportButton.disabled = true;
                buttonText.textContent = 'Aucune donnée à exporter';
            }
        });

        // Validation du formulaire
        document.getElementById('exportForm').addEventListener('submit', function(e) {
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = document.getElementById('date_to').value;
            
            if (dateFrom && dateTo && new Date(dateFrom) > new Date(dateTo)) {
                e.preventDefault();
                alert('La date de début doit être antérieure à la date de fin.');
                return;
            }
            
            const orderCount = <?php echo count($orders); ?>;
            if (orderCount === 0) {
                e.preventDefault();
                alert('Aucune commande trouvée avec les filtres sélectionnés.');
                return;
            }
            
            // Afficher un message de chargement
            const exportButton = document.getElementById('exportButton');
            const originalHTML = exportButton.innerHTML;
            exportButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Préparation de l\'export...';
            exportButton.disabled = true;
            
            setTimeout(() => {
                exportButton.innerHTML = originalHTML;
                exportButton.disabled = false;
            }, 3000);
        });

        // Mettre à jour l'aperçu lors du changement de filtre
        document.querySelectorAll('#exportForm select, #exportForm input').forEach(element => {
            element.addEventListener('change', function() {
                // Vous pourriez ajouter une prévisualisation AJAX ici
                console.log('Filtre modifié');
            });
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
    </script>
</body>
</html>