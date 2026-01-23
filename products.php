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
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT is_active FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if ($product) {
        $new_status = $product['is_active'] ? 0 : 1;
        $stmt = $conn->prepare("UPDATE products SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $product_id);
        if ($stmt->execute()) {
            $message = $new_status ? "Produit activé avec succès." : "Produit désactivé avec succès.";
            $message_type = 'success';
        } else {
            $message = "Erreur lors de la modification du produit.";
            $message_type = 'danger';
        }
        $stmt->close();
    }
}
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order_count = $result->fetch_assoc()['count'];
    $stmt->close();
    
    if ($order_count > 0) {
        $message = "Impossible de supprimer ce produit. Il est utilisé dans " . $order_count . " commande(s).";
        $message_type = 'warning';
    } else {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        if ($stmt->execute()) {
            $stmt2 = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
            $stmt2->bind_param("i", $product_id);
            $stmt2->execute();
            $stmt2->close();
            
            $message = "Produit supprimé avec succès.";
            $message_type = 'success';
        } else {
            $message = "Erreur lors de la suppression du produit.";
            $message_type = 'danger';
        }
        $stmt->close();
    }
}
$search = isset($_GET['search']) ? $_GET['search'] : '';
$collection_filter = isset($_GET['collection']) ? intval($_GET['collection']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$moq_filter = isset($_GET['moq']) ? $_GET['moq'] : '';
$query = "SELECT p.*, c.name as collection_name, c.season as collection_season 
          FROM products p 
          LEFT JOIN collections c ON p.collection_id = c.id 
          WHERE 1=1 ";
$params = [];
$types = '';

if ($search) {
    $query .= " AND (p.reference LIKE ? OR p.name LIKE ? OR p.description LIKE ?) ";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

if ($collection_filter) {
    $query .= " AND p.collection_id = ? ";
    $params[] = $collection_filter;
    $types .= 'i';
}

if ($status_filter !== '') {
    $query .= " AND p.is_active = ? ";
    $params[] = $status_filter;
    $types .= 'i';
}

if ($moq_filter) {
    if ($moq_filter == 'low') {
        $query .= " AND p.moq <= 50 ";
    } elseif ($moq_filter == 'medium') {
        $query .= " AND p.moq BETWEEN 51 AND 150 ";
    } elseif ($moq_filter == 'high') {
        $query .= " AND p.moq > 150 ";
    }
}

$query .= " ORDER BY p.created_at DESC";
if ($params) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

$products = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
$collections_result = $conn->query("SELECT id, name, season FROM collections WHERE is_public = 1 ORDER BY name");
$collections = [];
if ($collections_result) {
    while($row = $collections_result->fetch_assoc()) {
        $collections[] = $row;
    }
}
$stats_result = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(is_active = 1) as active,
        SUM(is_active = 0) as inactive,
        AVG(moq) as avg_moq,
        AVG(production_time_days) as avg_production_days
    FROM products
");
$stats = $stats_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produits - Tableau de bord Admin - FUS Denim</title>
    
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
            background: linear-gradient(90deg, var(--accent-4), var(--accent-1));
        }

        .stat-box:nth-child(3)::before {
            background: linear-gradient(90deg, #EF4444, var(--accent-3));
        }

        .stat-box:nth-child(4)::before {
            background: linear-gradient(90deg, var(--accent-5), var(--accent-3));
        }

        .stat-box:nth-child(5)::before {
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
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
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .stat-box:nth-child(3) .stat-icon {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
        }

        .stat-box:nth-child(4) .stat-icon {
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent-5);
        }

        .stat-box:nth-child(5) .stat-icon {
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-1);
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
            color: var(--gray-500);
            font-weight: 500;
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

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        /* Product Card */
        .product-card {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: var(--accent-1);
        }

        .product-image-container {
            position: relative;
            height: 200px;
            overflow: hidden;
            background: var(--gray-100);
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s;
        }

        .product-card:hover .product-image {
            transform: scale(1.1);
        }

        .product-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            backdrop-filter: blur(10px);
        }

        .status-active {
            background: rgba(16, 185, 129, 0.9);
            color: var(--white);
        }

        .status-inactive {
            background: rgba(239, 68, 68, 0.9);
            color: var(--white);
        }

        .product-content {
            padding: 1.5rem;
        }

        .product-header {
            margin-bottom: 1rem;
        }

        .product-ref {
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
            color: var(--accent-1);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .product-collection {
            font-size: 0.85rem;
            color: var(--gray-600);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .product-collection i {
            color: var(--accent-1);
        }

        .product-specs {
            margin-bottom: 1rem;
        }

        .spec-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
        }

        .spec-item i {
            color: var(--accent-1);
            width: 16px;
        }

        .spec-label {
            color: var(--gray-600);
            min-width: 100px;
        }

        .spec-value {
            color: var(--gray-900);
            font-weight: 500;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-100);
            margin-top: 1rem;
        }

        .moq-badge {
            background: var(--gray-100);
            color: var(--gray-700);
            padding: 0.25rem 0.75rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .moq-badge i {
            color: var(--accent-1);
        }

        .production-time {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-600);
            font-size: 0.85rem;
        }

        .production-time i {
            color: var(--accent-1);
        }

        .product-footer {
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
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--gray-200);
            background: var(--white);
            color: var(--gray-600);
            transition: all 0.3s ease;
            text-decoration: none;
            cursor: pointer;
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

        .product-date {
            color: var(--gray-500);
            font-size: 0.8rem;
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

        .alert-modern.alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border-color: rgba(245, 158, 11, 0.3);
            color: var(--accent-5);
        }

        .alert-modern.alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #EF4444;
        }

        /* Info Card */
        .info-card {
            background: rgba(59, 130, 246, 0.05);
            border-radius: 16px;
            padding: 2rem;
            border: 1px solid rgba(59, 130, 246, 0.1);
            margin-bottom: 2rem;
        }

        .info-card i {
            color: var(--accent-1);
            font-size: 1.5rem;
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

        .stat-box, .card-modern, .product-card, .alert-modern {
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

            .products-grid {
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

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .product-footer {
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-value {
                font-size: 1.75rem;
            }

            .card-modern {
                padding: 1.25rem;
            }

            .product-card {
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
            <a href="collection.php" class="nav-item">
                <i class="fas fa-layer-group"></i>
                <span>Collections</span>
            </a>
            <a href="products.php" class="nav-item active">
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
                <h1>Gestion des Produits</h1>
                <p>Gérez le catalogue produits denim</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
                <a href="product_create.php" class="btn-modern">
                    <i class="fas fa-plus-circle"></i> Nouveau produit
                </a>
            </div>
        </div>

        <!-- Alert Message -->
        <?php if ($message): ?>
        <div class="alert-modern alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas <?php 
                    if ($message_type === 'success') echo 'fa-check-circle';
                    elseif ($message_type === 'warning') echo 'fa-exclamation-triangle';
                    else echo 'fa-exclamation-circle';
                ?> me-2"></i>
                <span><?php echo $message; ?></span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Produits total</div>
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-trend">Dans le catalogue</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Produits actifs</div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['active']; ?></div>
                <div class="stat-trend">Visibles</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Produits inactifs</div>
                    <div class="stat-icon">
                        <i class="fas fa-eye-slash"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['inactive']; ?></div>
                <div class="stat-trend">Masqués</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">MOQ moyen</div>
                    <div class="stat-icon">
                        <i class="fas fa-sort-amount-up"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo round($stats['avg_moq']); ?></div>
                <div class="stat-trend">Unité par produit</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Temps production</div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo round($stats['avg_production_days']); ?>j</div>
                <div class="stat-trend">Moyenne</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card-modern">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-filter"></i> Filtres de recherche
                </div>
                <?php if ($search || $collection_filter || $status_filter !== '' || $moq_filter): ?>
                <a href="products.php" class="btn-outline-modern">
                    <i class="fas fa-times"></i> Réinitialiser
                </a>
                <?php endif; ?>
            </div>
            
            <form method="GET" class="row g-3 filter-form">
                <div class="col-lg-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Rechercher un produit..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-lg-2">
                    <select class="form-select" name="collection">
                        <option value="">Toutes collections</option>
                        <?php foreach($collections as $collection): ?>
                        <option value="<?php echo $collection['id']; ?>" 
                                <?php echo $collection_filter == $collection['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($collection['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2">
                    <select class="form-select" name="status">
                        <option value="">Tous statuts</option>
                        <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Actifs</option>
                        <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactifs</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <select class="form-select" name="moq">
                        <option value="">Tous MOQ</option>
                        <option value="low" <?php echo $moq_filter == 'low' ? 'selected' : ''; ?>>MOQ ≤ 50</option>
                        <option value="medium" <?php echo $moq_filter == 'medium' ? 'selected' : ''; ?>>MOQ 51-150</option>
                        <option value="high" <?php echo $moq_filter == 'high' ? 'selected' : ''; ?>>MOQ > 150</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <button type="submit" class="btn-modern w-100">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                </div>
            </form>
        </div>

        <!-- Products -->
        <?php if (empty($products)): ?>
        <div class="empty-state">
            <i class="fas fa-tags"></i>
            <h3>Aucun produit trouvé</h3>
            <p>
                <?php if ($search || $collection_filter || $status_filter !== '' || $moq_filter): ?>
                Essayez de modifier vos critères de recherche pour trouver ce que vous cherchez.
                <?php else: ?>
                Commencez par créer votre premier produit dans le catalogue.
                <?php endif; ?>
            </p>
            <?php if ($search || $collection_filter || $status_filter !== '' || $moq_filter): ?>
            <a href="products.php" class="btn-outline-modern">Voir tous les produits</a>
            <?php else: ?>
            <a href="product_create.php" class="btn-modern">
                <i class="fas fa-plus-circle me-2"></i>Créer un produit
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="products-grid">
            <?php foreach($products as $index => $product): 
                // Récupérer l'image principale du produit
                $image_stmt = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND is_main = 1 LIMIT 1");
                $image_stmt->bind_param("i", $product['id']);
                $image_stmt->execute();
                $image_result = $image_stmt->get_result();
                $image = $image_result->fetch_assoc();
                $image_stmt->close();
            ?>
            <div class="product-card" style="animation-delay: <?php echo ($index * 0.05) + 0.2; ?>s;">
                <div class="product-image-container">
                    <?php if ($image && !empty($image['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="product-image">
                    <?php else: ?>
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-image" style="font-size: 3rem; color: var(--gray-400);"></i>
                    </div>
                    <?php endif; ?>
                    <span class="product-status <?php echo $product['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo $product['is_active'] ? 'Actif' : 'Inactif'; ?>
                    </span>
                </div>
                
                <div class="product-content">
                    <div class="product-header">
                        <div class="product-ref"><?php echo htmlspecialchars($product['reference']); ?></div>
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <?php if ($product['collection_name']): ?>
                        <div class="product-collection">
                            <i class="fas fa-layer-group"></i>
                            <?php echo htmlspecialchars($product['collection_name']); ?>
                            <?php if ($product['collection_season']): ?>
                            <span class="text-muted">(<?php echo $product['collection_season']; ?>)</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-specs">
                        <?php if ($product['weight_oz']): ?>
                        <div class="spec-item">
                            <i class="fas fa-weight"></i>
                            <span class="spec-label">Poids:</span>
                            <span class="spec-value"><?php echo htmlspecialchars($product['weight_oz']); ?> oz</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($product['fabric_composition']): ?>
                        <div class="spec-item">
                            <i class="fas fa-tshirt"></i>
                            <span class="spec-label">Composition:</span>
                            <span class="spec-value"><?php echo htmlspecialchars(substr($product['fabric_composition'], 0, 50)); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($product['available_colors']): ?>
                        <div class="spec-item">
                            <i class="fas fa-palette"></i>
                            <span class="spec-label">Couleurs:</span>
                            <span class="spec-value"><?php 
                                $colors = explode(',', $product['available_colors']);
                                echo htmlspecialchars(implode(', ', array_slice($colors, 0, 3)));
                                if (count($colors) > 3) echo '...';
                            ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-meta">
                        <div class="moq-badge">
                            <i class="fas fa-sort-amount-up"></i>
                            MOQ: <?php echo $product['moq']; ?> unités
                        </div>
                        <div class="production-time">
                            <i class="fas fa-clock"></i>
                            <?php echo $product['production_time_days']; ?> jours
                        </div>
                    </div>
                </div>
                
                <div class="product-footer">
                    <div class="btn-action-group">
                        <a href="product_view.php?id=<?php echo $product['id']; ?>" 
                           class="btn-action btn-view" title="Voir">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="product_edit.php?id=<?php echo $product['id']; ?>" 
                           class="btn-action btn-edit" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php if ($product['is_active']): ?>
                            <a href="?toggle=disable&id=<?php echo $product['id']; ?>" 
                               class="btn-action btn-toggle" 
                               title="Désactiver"
                               onclick="return confirm('Désactiver ce produit ? Il ne sera plus visible dans le catalogue.')">
                                <i class="fas fa-eye-slash"></i>
                            </a>
                        <?php else: ?>
                            <a href="?toggle=enable&id=<?php echo $product['id']; ?>" 
                               class="btn-action btn-toggle" 
                               title="Activer"
                               onclick="return confirm('Activer ce produit ? Il sera visible dans le catalogue.')">
                                <i class="fas fa-eye"></i>
                            </a>
                        <?php endif; ?>
                        <button type="button" 
                                class="btn-action btn-delete" 
                                title="Supprimer"
                                onclick="confirmDelete(<?php echo $product['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    
                    <div class="product-date">
                        <?php echo date('d/m/Y', strtotime($product['created_at'])); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Info Card -->
        <div class="info-card">
            <div class="row align-items-center">
                <div class="col-md-1">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="col-md-11">
                    <h5 class="mb-2">Information sur les produits</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-circle" style="color: var(--accent-4); font-size: 0.7rem;"></i>
                                    <strong class="ms-2">Actif</strong> : Produit visible dans le catalogue clients
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-circle" style="color: #EF4444; font-size: 0.7rem;"></i>
                                    <strong class="ms-2">Inactif</strong> : Produit masqué du catalogue
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-box me-2" style="color: var(--accent-1);"></i>
                                    <strong>MOQ</strong> : Minimum Order Quantity - Quantité minimale de commande
                                </li>
                                <li>
                                    <i class="fas fa-clock me-2" style="color: var(--accent-1);"></i>
                                    <strong>Temps prod.</strong> : Délai de production en jours
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>
                <i class="fas fa-shield-alt" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Back-office Administrateur v1.0
                <?php 
                $active_count = array_reduce($products, function($carry, $product) {
                    return $carry + ($product['is_active'] ? 1 : 0);
                }, 0);
                ?>
                <span class="stats-badge ms-3">
                    <i class="fas fa-tags"></i>
                    <?php echo count($products); ?> produit<?php echo count($products) > 1 ? 's' : ''; ?>
                    (<?php echo $active_count; ?> actif<?php echo $active_count > 1 ? 's' : ''; ?>)
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
        function confirmDelete(productId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?\nCette action est irréversible.')) {
                window.location.href = `?delete=1&id=${productId}`;
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
        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.zIndex = '10';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.zIndex = '1';
            });
        });

        // Confirmation pour l'activation/désactivation
        document.querySelectorAll('a[href*="toggle="]').forEach(link => {
            link.addEventListener('click', function(e) {
                const isActivate = this.querySelector('i').classList.contains('fa-eye');
                const action = isActivate ? 'activer' : 'désactiver';
                
                if (!confirm(`Êtes-vous sûr de vouloir ${action} ce produit ?`)) {
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

        document.querySelectorAll('.product-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(card);
        });

        // Animation des nombres pour les stats
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

        const statsObserver = new IntersectionObserver((entries) => {
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

        document.querySelectorAll('.stat-box').forEach(box => statsObserver.observe(box));
    </script>
</body>
</html>