<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$message = '';
$message_type = '';

if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $client_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT is_active FROM users WHERE id = ?");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $client = $result->fetch_assoc();
    
    if ($client) {
        $new_status = $client['is_active'] ? 0 : 1;
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $client_id);
        if ($stmt->execute()) {
            $message = $new_status ? "Client activé avec succès." : "Client désactivé avec succès.";
            $message_type = 'success';
        } else {
            $message = "Erreur lors de la modification du client.";
            $message_type = 'danger';
        }
        $stmt->close();
    }
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$country_filter = isset($_GET['country']) ? $_GET['country'] : '';

$query = "SELECT id, email, company_name, country, contact_person, phone, 
                 role, is_active, created_at 
          FROM users 
          WHERE role = 'client' ";
$params = [];
$types = '';

if ($search) {
    $query .= " AND (company_name LIKE ? OR email LIKE ? OR contact_person LIKE ?) ";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

if ($status_filter !== '') {
    $query .= " AND is_active = ? ";
    $params[] = $status_filter;
    $types .= 'i';
}

if ($country_filter) {
    $query .= " AND country = ? ";
    $params[] = $country_filter;
    $types .= 's';
}

$query .= " ORDER BY created_at DESC";

if ($params) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

$clients = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }
}

$countries_result = $conn->query("SELECT DISTINCT country FROM users WHERE country IS NOT NULL AND country != '' ORDER BY country");
$countries = [];
if ($countries_result) {
    while($row = $countries_result->fetch_assoc()) {
        $countries[] = $row['country'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Clients - FUS Denim</title>
    
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
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: var(--white);
            border-radius: 16px;
            padding: 1.75rem;
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
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
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
            font-size: 2.5rem;
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

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 1.5rem;
        }

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

        .badge-active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .badge-inactive {
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
            width: 40px;
            height: 40px;
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

        .btn-toggle:hover {
            background: var(--accent-5);
            color: var(--white);
            border-color: var(--accent-5);
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
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
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

        /* Alert Modern */
        .alert-modern {
            border-radius: 12px;
            border: 1px solid;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
        }

        .alert-modern.alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.2);
            color: var(--accent-4);
        }

        .alert-modern.alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.2);
            color: #EF4444;
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

            .content-grid {
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
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .content-grid {
                grid-template-columns: 1fr;
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

            .stat-value {
                font-size: 1.75rem;
            }

            .card-modern {
                padding: 1.25rem;
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
            <a href="order.php" class="nav-item">
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
                <h1>Gestion des Clients</h1>
                <p>Gérez les comptes clients et leurs accès au portail</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
            </div>
        </div>

        <!-- Message d'alerte -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-modern alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-3"></i>
                <div><?php echo $message; ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Clients total</div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo count($clients); ?></div>
                <div class="stat-trend">
                    <?php
                    $active_count = array_reduce($clients, function($carry, $client) {
                        return $carry + ($client['is_active'] ? 1 : 0);
                    }, 0);
                    ?>
                    <span style="color: var(--accent-4);"><?php echo $active_count; ?> actifs</span>
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Nouveaux clients</div>
                    <div class="stat-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                </div>
                <div class="stat-value">
                    <?php
                    $last_30_days = array_filter($clients, function($client) {
                        return strtotime($client['created_at']) > strtotime('-30 days');
                    });
                    echo count($last_30_days);
                    ?>
                </div>
                <div class="stat-trend">Derniers 30 jours</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Clients inactifs</div>
                    <div class="stat-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo count($clients) - $active_count; ?></div>
                <div class="stat-trend">
                    <span style="color: #EF4444;">À surveiller</span>
                </div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Pays</div>
                    <div class="stat-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                </div>
                <div class="stat-value">
                    <?php
                    $unique_countries = array_unique(array_column($clients, 'country'));
                    echo count($unique_countries);
                    ?>
                </div>
                <div class="stat-trend">Pays représentés</div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filter-section card-modern">
            <div class="card-header" style="padding: 0; border-bottom: none; margin-bottom: 1.5rem;">
                <div class="card-title">
                    <i class="fas fa-filter"></i> Filtres
                </div>
            </div>
            
            <form method="GET" class="filter-group">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" name="search" placeholder="Rechercher par société, email ou contact..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div>
                    <label class="form-label" style="font-weight: 600; color: var(--gray-600); margin-bottom: 0.5rem;">Statut</label>
                    <select class="form-select" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Actifs</option>
                        <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactifs</option>
                    </select>
                </div>
                
                <div>
                    <label class="form-label" style="font-weight: 600; color: var(--gray-600); margin-bottom: 0.5rem;">Pays</label>
                    <select class="form-select" name="country">
                        <option value="">Tous les pays</option>
                        <?php foreach($countries as $country): ?>
                        <option value="<?php echo htmlspecialchars($country); ?>" 
                                <?php echo $country_filter === $country ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($country); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="card-action" style="margin: 0; flex: 1;">
                        <i class="fas fa-filter me-2"></i>Filtrer
                    </button>
                    <?php if ($search || $status_filter !== '' || $country_filter): ?>
                    <a href="clients.php" class="card-action" style="background: var(--gray-100); margin: 0;">
                        <i class="fas fa-times me-2"></i>Réinitialiser
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tableau des clients -->
        <div class="card-modern">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-users"></i> Liste des clients
                </div>
                <div>
                    <a href="client_create.php" class="card-action">
                        <i class="fas fa-plus me-2"></i>Nouveau client
                    </a>
                </div>
            </div>
            
            <?php if (empty($clients)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h5 class="mt-3 mb-2">Aucun client trouvé</h5>
                <p class="text-muted mb-3">
                    <?php if ($search || $status_filter !== '' || $country_filter): ?>
                    Essayez de modifier vos critères de recherche
                    <?php else: ?>
                    Commencez par ajouter votre premier client
                    <?php endif; ?>
                </p>
                <?php if ($search || $status_filter !== '' || $country_filter): ?>
                <a href="clients.php" class="card-action" style="display: inline-block;">Voir tous les clients</a>
                <?php else: ?>
                <a href="client_create.php" class="card-action" style="display: inline-block;">
                    <i class="fas fa-user-plus me-2"></i>Ajouter un client
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th width="50">ID</th>
                            <th>Société</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Date création</th>
                            <th>Statut</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($clients as $client): ?>
                        <tr>
                            <td class="text-muted fw-semibold">#<?php echo $client['id']; ?></td>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($client['company_name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($client['country']); ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($client['contact_person']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($client['phone']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($client['email']); ?></td>
                            <td>
                                <div><?php echo date('d/m/Y', strtotime($client['created_at'])); ?></div>
                                <small class="text-muted"><?php echo date('H:i', strtotime($client['created_at'])); ?></small>
                            </td>
                            <td>
                                <?php if ($client['is_active']): ?>
                                    <span class="status-badge badge-active">Actif</span>
                                <?php else: ?>
                                    <span class="status-badge badge-inactive">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-action-group">
                                    <a href="client_orders.php?id=<?php echo $client['id']; ?>" 
                                       class="btn-action btn-view" title="Voir les commandes">
                                        <i class="fas fa-shopping-cart"></i>
                                    </a>
                                    <a href="client_edit.php?id=<?php echo $client['id']; ?>" 
                                       class="btn-action btn-edit" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($client['is_active']): ?>
                                        <a href="?toggle=disable&id=<?php echo $client['id']; ?>" 
                                           class="btn-action btn-toggle" 
                                           title="Désactiver"
                                           onclick="return confirm('Désactiver ce client ? Il ne pourra plus se connecter.')">
                                            <i class="fas fa-user-slash"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="?toggle=enable&id=<?php echo $client['id']; ?>" 
                                           class="btn-action btn-toggle" 
                                           title="Activer"
                                           onclick="return confirm('Activer ce client ? Il pourra de nouveau se connecter.')">
                                            <i class="fas fa-user-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" class="btn-action btn-delete" 
                                            title="Supprimer"
                                            onclick="confirmDelete(<?php echo $client['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pied de tableau -->
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Affichage de <strong><?php echo count($clients); ?></strong> client<?php echo count($clients) > 1 ? 's' : ''; ?>
                    <?php if ($search || $status_filter !== '' || $country_filter): ?>
                    (filtre<?php echo ($search ? 1 : 0) + ($status_filter !== '' ? 1 : 0) + ($country_filter ? 1 : 0) > 1 ? 's' : ''; ?> appliqué<?php echo ($search ? 1 : 0) + ($status_filter !== '' ? 1 : 0) + ($country_filter ? 1 : 0) > 1 ? 's' : ''; ?>)
                    <?php endif; ?>
                </div>
                <div>
                    <a href="access_requests.php" class="card-action">
                        <i class="fas fa-door-open me-2"></i>Voir les demandes d'accès
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="card-modern">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-bolt"></i> Actions rapides
                </div>
            </div>
            
            <div class="quick-actions">
                <a href="client_create.php" class="action-btn">
                    <i class="fas fa-user-plus"></i>
                    <span>Nouveau client</span>
                </a>
                <a href="access_requests.php" class="action-btn">
                    <i class="fas fa-key"></i>
                    <span>Demandes d'accès</span>
                </a>
                <a href="export_clients.php" class="action-btn">
                    <i class="fas fa-download"></i>
                    <span>Exporter données</span>
                </a>
                <a href="messages.php" class="action-btn">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
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
        // Auto-refresh toutes les 60 secondes
        setTimeout(function() {
            location.reload();
        }, 60000);

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
                        const finalValue = parseInt(value.textContent.replace(/\s/g, ''));
                        animateValue(value, 0, finalValue, 800);
                        value.dataset.animated = 'true';
                    }
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.stat-box').forEach(box => observer.observe(box));

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

        function confirmDelete(clientId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce client ? Cette action est irréversible.')) {
                // En production, vous utiliseriez AJAX ou un lien de suppression
                alert('La suppression sera implémentée ici. Client ID: ' + clientId);
            }
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