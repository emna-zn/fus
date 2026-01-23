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
    $collection_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT is_public FROM collections WHERE id = ?");
    $stmt->bind_param("i", $collection_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $collection = $result->fetch_assoc();
    
    if ($collection) {
        $new_status = $collection['is_public'] ? 0 : 1;
        $stmt = $conn->prepare("UPDATE collections SET is_public = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $collection_id);
        if ($stmt->execute()) {
            $message = $new_status ? "Collection publiée avec succès." : "Collection dépubliée.";
            $message_type = 'success';
        } else {
            $message = "Erreur lors de la modification.";
            $message_type = 'danger';
        }
        $stmt->close();
    }
}

if (isset($_GET['delete']) && isset($_GET['id'])) {
    $collection_id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM collections WHERE id = ?");
    $stmt->bind_param("i", $collection_id);
    if ($stmt->execute()) {
        $message = "Collection supprimée avec succès.";
        $message_type = 'success';
    } else {
        $message = "Erreur lors de la suppression.";
        $message_type = 'danger';
    }
    $stmt->close();
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$season_filter = isset($_GET['season']) ? $_GET['season'] : '';

$query = "
    SELECT c.*, COUNT(p.id) as product_count 
    FROM collections c 
    LEFT JOIN products p ON c.id = p.collection_id 
    WHERE 1=1
";
$params = [];
$types = '';

if ($search) {
    $query .= " AND (c.name LIKE ? OR c.description LIKE ?) ";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ss';
}

if ($status_filter !== '') {
    $query .= " AND c.is_public = ? ";
    $params[] = $status_filter;
    $types .= 'i';
}

if ($season_filter) {
    $query .= " AND c.season = ? ";
    $params[] = $season_filter;
    $types .= 's';
}

$query .= " GROUP BY c.id ORDER BY c.created_at DESC";

if ($params) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

$collections = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $collections[] = $row;
    }
}

$seasons_result = $conn->query("SELECT DISTINCT season FROM collections WHERE season IS NOT NULL AND season != '' ORDER BY season DESC");
$seasons = [];
if ($seasons_result) {
    while($row = $seasons_result->fetch_assoc()) {
        $seasons[] = $row['season'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collections - Tableau de bord Admin - FUS Denim</title>
    
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

        /* Collections Grid */
        .collections-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        @media (max-width: 768px) {
            .collections-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Collection Card */
        .collection-card {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .collection-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: var(--accent-1);
        }

        .collection-header {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(139, 92, 246, 0.05));
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
            position: relative;
            overflow: hidden;
        }

        .collection-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
        }

        .collection-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
            line-height: 1.3;
        }

        .collection-status {
            padding: 0.4rem 1rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-public {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1));
            color: var(--accent-4);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-private {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.1));
            color: var(--accent-5);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .collection-body {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .collection-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .season-badge {
            background: var(--gray-100);
            color: var(--gray-700);
            padding: 0.4rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .season-badge i {
            color: var(--accent-1);
        }

        .product-count {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-600);
            font-size: 0.95rem;
            font-weight: 500;
        }

        .product-count i {
            color: var(--accent-1);
        }

        .collection-description {
            color: var(--gray-600);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1rem;
            flex-grow: 1;
        }

        .collection-footer {
            padding: 1.5rem;
            background: var(--gray-50);
            border-top: 1px solid var(--gray-100);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        /* Action Buttons */
        .btn-action-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s;
            border: 1px solid;
            cursor: pointer;
            background: transparent;
        }

        .btn-action-sm {
            padding: 0.4rem 0.75rem;
            font-size: 0.8rem;
        }

        .btn-edit {
            color: var(--accent-1);
            border-color: rgba(59, 130, 246, 0.3);
            background: rgba(59, 130, 246, 0.1);
        }

        .btn-edit:hover {
            background: var(--accent-1);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
        }

        .btn-publish {
            color: var(--accent-4);
            border-color: rgba(16, 185, 129, 0.3);
            background: rgba(16, 185, 129, 0.1);
        }

        .btn-publish:hover {
            background: var(--accent-4);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-unpublish {
            color: var(--accent-5);
            border-color: rgba(245, 158, 11, 0.3);
            background: rgba(245, 158, 11, 0.1);
        }

        .btn-unpublish:hover {
            background: var(--accent-5);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3);
        }

        .btn-delete {
            color: #EF4444;
            border-color: rgba(239, 68, 68, 0.3);
            background: rgba(239, 68, 68, 0.1);
        }

        .btn-delete:hover {
            background: #EF4444;
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
        }

        /* Alert */
        .alert-modern {
            border-radius: 12px;
            border: 1px solid;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            animation: slideInUp 0.5s ease-out;
        }

        .alert-modern.alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.3);
            color: var(--accent-4);
        }

        .alert-modern.alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #EF4444;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-400);
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            margin-bottom: 3rem;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            color: var(--gray-600);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--gray-500);
            max-width: 400px;
            margin: 0 auto 2rem;
            line-height: 1.6;
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

        .card-modern, .collection-card, .alert-modern {
            animation: slideInUp 0.5s ease-out forwards;
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

            .collections-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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
                justify-content: space-between;
            }

            .collections-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .collection-footer {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-action-group {
                justify-content: center;
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

            .card-modern {
                padding: 1.25rem;
            }

            .collection-card {
                border-radius: 16px;
            }

            .footer {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }

        /* Stats Badge */
        .stats-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            background: var(--gray-100);
            border-radius: 8px;
            font-size: 0.9rem;
            color: var(--gray-700);
        }

        .stats-badge i {
            color: var(--accent-1);
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
            <a href="order.php" class="nav-item">
                <i class="fas fa-shopping-bag"></i>
                <span>Commandes</span>
            </a>
            <a href="collection.php" class="nav-item active">
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
                <h1>Gestion des Collections</h1>
                <p>Gérez vos collections de produits et leur visibilité</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
                <a href="collection_create.php" class="btn-modern">
                    <i class="fas fa-plus-circle"></i> Nouvelle collection
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

        <!-- Filters -->
        <div class="card-modern">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-filter"></i> Filtres de recherche
                </div>
                <?php if ($search || $status_filter !== '' || $season_filter): ?>
                <a href="collection.php" class="btn-outline-modern">
                    <i class="fas fa-times"></i> Réinitialiser
                </a>
                <?php endif; ?>
            </div>
            
            <form method="GET" class="row g-3 filter-form">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Rechercher une collection..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Public</option>
                        <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Privé</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="season">
                        <option value="">Toutes les saisons</option>
                        <?php foreach($seasons as $season): ?>
                        <option value="<?php echo htmlspecialchars($season); ?>" 
                                <?php echo $season_filter === $season ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($season); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn-modern w-100">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                </div>
            </form>
        </div>

        <!-- Collections -->
        <?php if (empty($collections)): ?>
        <div class="empty-state">
            <i class="fas fa-layer-group"></i>
            <h3>Aucune collection trouvée</h3>
            <p>
                <?php if ($search || $status_filter !== '' || $season_filter): ?>
                Essayez de modifier vos critères de recherche pour trouver ce que vous cherchez.
                <?php else: ?>
                Commencez par créer votre première collection de produits.
                <?php endif; ?>
            </p>
            <?php if ($search || $status_filter !== '' || $season_filter): ?>
            <a href="collection.php" class="btn-outline-modern">Voir toutes les collections</a>
            <?php else: ?>
            <a href="collection_create.php" class="btn-modern">
                <i class="fas fa-plus-circle me-2"></i>Créer une collection
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="collections-grid">
            <?php foreach($collections as $index => $collection): ?>
            <div class="collection-card" style="animation-delay: <?php echo ($index * 0.1) + 0.2; ?>s;">
                <div class="collection-header">
                    <h3 class="collection-title"><?php echo htmlspecialchars($collection['name']); ?></h3>
                    <span class="collection-status <?php echo $collection['is_public'] ? 'status-public' : 'status-private'; ?>">
                        <?php echo $collection['is_public'] ? 'Public' : 'Privé'; ?>
                    </span>
                </div>
                
                <div class="collection-body">
                    <div class="collection-meta">
                        <span class="season-badge">
                            <i class="fas fa-calendar-alt me-2"></i><?php echo htmlspecialchars($collection['season']); ?>
                        </span>
                        <span class="product-count">
                            <i class="fas fa-box"></i>
                            <?php echo $collection['product_count']; ?> produit<?php echo $collection['product_count'] > 1 ? 's' : ''; ?>
                        </span>
                    </div>
                    
                    <p class="collection-description">
                        <?php echo htmlspecialchars($collection['description'] ? substr($collection['description'], 0, 150) . (strlen($collection['description']) > 150 ? '...' : '') : 'Aucune description'); ?>
                    </p>
                </div>
                
                <div class="collection-footer">
                    <div class="btn-action-group">
                        <a href="collection_edit.php?id=<?php echo $collection['id']; ?>" 
                           class="btn-action btn-edit" title="Modifier">
                            <i class="fas fa-edit"></i>
                            <span class="d-none d-md-inline">Modifier</span>
                        </a>
                        <?php if ($collection['is_public']): ?>
                            <a href="?toggle=private&id=<?php echo $collection['id']; ?>" 
                               class="btn-action btn-unpublish" 
                               title="Dépublier"
                               onclick="return confirm('Dépublier cette collection ? Elle ne sera plus visible par les clients.')">
                                <i class="fas fa-eye-slash"></i>
                                <span class="d-none d-md-inline">Dépublier</span>
                            </a>
                        <?php else: ?>
                            <a href="?toggle=public&id=<?php echo $collection['id']; ?>" 
                               class="btn-action btn-publish" 
                               title="Publier"
                               onclick="return confirm('Publier cette collection ? Elle sera visible par les clients.')">
                                <i class="fas fa-eye"></i>
                                <span class="d-none d-md-inline">Publier</span>
                            </a>
                        <?php endif; ?>
                        <button type="button" 
                                class="btn-action btn-delete" 
                                title="Supprimer"
                                onclick="confirmDelete(<?php echo $collection['id']; ?>)">
                            <i class="fas fa-trash"></i>
                            <span class="d-none d-md-inline">Supprimer</span>
                        </button>
                    </div>
                    
                    <div class="text-muted" style="font-size: 0.85rem;">
                        <?php echo date('d/m/Y', strtotime($collection['created_at'])); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <div>
                <i class="fas fa-shield-alt" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Back-office Administrateur v1.0
                <?php 
                $public_count = array_reduce($collections, function($carry, $collection) {
                    return $carry + ($collection['is_public'] ? 1 : 0);
                }, 0);
                ?>
                <span class="stats-badge ms-3">
                    <i class="fas fa-layer-group"></i>
                    <?php echo count($collections); ?> collection<?php echo count($collections) > 1 ? 's' : ''; ?>
                    (<?php echo $public_count; ?> public<?php echo $public_count > 1 ? 's' : ''; ?>)
                </span>
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

        // Fonction de confirmation de suppression
        function confirmDelete(collectionId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette collection ?\nCette action est irréversible et affectera tous les produits associés.')) {
                window.location.href = `?delete=1&id=${collectionId}`;
            }
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

        // Recherche en temps réel avec délai
        let searchTimeout;
        const searchInput = document.querySelector('input[name="search"]');
        
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length >= 3 || this.value.length === 0) {
                        this.closest('form').submit();
                    }
                }, 500);
            });
        }

        // Animation des cartes au survol
        document.querySelectorAll('.collection-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.zIndex = '10';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.zIndex = '1';
            });
        });

        // Confirmation pour la publication/dépublication
        document.querySelectorAll('a[href*="toggle="]').forEach(link => {
            link.addEventListener('click', function(e) {
                const isPublish = this.classList.contains('btn-publish');
                const action = isPublish ? 'publier' : 'dépublier';
                
                if (!confirm(`Êtes-vous sûr de vouloir ${action} cette collection ?`)) {
                    e.preventDefault();
                }
            });
        });

        // Animation pour l'apparition des cartes
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.collection-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>