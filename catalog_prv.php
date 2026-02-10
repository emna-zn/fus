<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
$database = new Database();
$conn = $database->getConnection();

$is_admin = ($_SESSION['role'] === 'admin');
if ($is_admin) {
    $query = "SELECT p.*, c.name as collection_name, c.season as collection_season 
              FROM products p 
              LEFT JOIN collections c ON p.collection_id = c.id 
              WHERE c.is_public = 1 
              ORDER BY p.created_at DESC";
} else {
    $query = "SELECT p.*, c.name as collection_name, c.season as collection_season 
              FROM products p 
              LEFT JOIN collections c ON p.collection_id = c.id 
              WHERE p.is_active = 1 AND c.is_public = 1 
              ORDER BY p.created_at DESC";
}
$search = isset($_GET['search']) ? $_GET['search'] : '';
$collection_filter = isset($_GET['collection']) ? intval($_GET['collection']) : '';
$weight_filter = isset($_GET['weight']) ? $_GET['weight'] : '';
$wash_filter = isset($_GET['wash']) ? $_GET['wash'] : '';

if ($search || $collection_filter || $weight_filter || $wash_filter) {
    $where_clauses = [];
    $params = [];
    $types = '';
    
    if ($is_admin) {
        $base_query = "SELECT p.*, c.name as collection_name, c.season as collection_season 
                       FROM products p 
                       LEFT JOIN collections c ON p.collection_id = c.id 
                       WHERE c.is_public = 1 ";
    } else {
        $base_query = "SELECT p.*, c.name as collection_name, c.season as collection_season 
                       FROM products p 
                       LEFT JOIN collections c ON p.collection_id = c.id 
                       WHERE p.is_active = 1 AND c.is_public = 1 ";
    }
    
    if ($search) {
        $where_clauses[] = "(p.reference LIKE ? OR p.name LIKE ? OR p.description LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'sss';
    }
    
    if ($collection_filter) {
        $where_clauses[] = "p.collection_id = ?";
        $params[] = $collection_filter;
        $types .= 'i';
    }
    
    if ($weight_filter) {
        if ($weight_filter == 'light') {
            $where_clauses[] = "(p.weight_oz < 10 OR p.weight_oz REGEXP '^[0-9]+$' AND CAST(p.weight_oz AS DECIMAL) < 10)";
        } elseif ($weight_filter == 'medium') {
            $where_clauses[] = "((p.weight_oz REGEXP '^[0-9]+(\\.[0-9]+)?$' AND CAST(p.weight_oz AS DECIMAL) BETWEEN 10 AND 14) OR p.weight_oz LIKE '%10-14%' OR p.weight_oz LIKE '%medium%')";
        } elseif ($weight_filter == 'heavy') {
            $where_clauses[] = "(p.weight_oz > 14 OR p.weight_oz REGEXP '^[0-9]+$' AND CAST(p.weight_oz AS DECIMAL) > 14)";
        }
    }
    
    if ($wash_filter && $wash_filter !== '') {
        $where_clauses[] = "(p.wash_types LIKE ?)";
        $params[] = "%$wash_filter%";
        $types .= 's';
    }
    
    if (!empty($where_clauses)) {
        $query = $base_query . " AND " . implode(" AND ", $where_clauses);
    }
    
    $query .= " ORDER BY p.created_at DESC";
    
    if ($params) {
        $stmt = $conn->prepare($query);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }
} else {
    $result = $conn->query($query);
}

$products = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $product_id = $row['id'];
        $images_stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC");
        $images_stmt->bind_param("i", $product_id);
        $images_stmt->execute();
        $images_result = $images_stmt->get_result();
        $images = [];
        while ($image = $images_result->fetch_assoc()) {
            $images[] = $image;
        }
        $images_stmt->close();
        $row['images'] = $images;
        
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue Produits - FUS Denim</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- Lightbox pour les images -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1F2937;
            --secondary: #111827;
            --accent-1: #3B82F6;
            --accent-2: #8B5CF6;
            --accent-3: #EC4899;
            --accent-4: #10B981;
            --accent-5: #F59E0B;
            --denim-blue: #1560BD;
            --indigo: #2C3E50;
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

        /* Sidebar Navigation (compatible avec le dashboard) */
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

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }

        /* Header Main */
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

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.2);
        }

        /* Filter Card (modifié pour correspondre au style dashboard) */
        .filter-card {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
        }

        .filter-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .filter-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .filter-title i {
            color: var(--accent-1);
        }

        /* Filter Form */
        .filter-form .form-control,
        .filter-form .form-select {
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .filter-form .form-control:focus,
        .filter-form .form-select:focus {
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

        .btn-filter {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
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
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: var(--accent-1);
        }

        .product-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            z-index: 2;
            padding: 0.4rem 0.75rem;
            background: linear-gradient(135deg, var(--accent-4), var(--accent-1));
            color: var(--white);
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .product-image-container {
            position: relative;
            height: 250px;
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

        .product-image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .image-count {
            color: var(--white);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .view-gallery {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: var(--white);
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .view-gallery:hover {
            background: rgba(255, 255, 255, 0.3);
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
            color: var(--primary);
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

        /* Specifications Grid */
        .specs-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .spec-item {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .spec-item i {
            color: var(--accent-1);
            width: 16px;
            margin-top: 0.2rem;
            flex-shrink: 0;
        }

        .spec-content {
            flex: 1;
        }

        .spec-label {
            font-size: 0.75rem;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.1rem;
        }

        .spec-value {
            font-size: 0.85rem;
            color: var(--gray-800);
            font-weight: 500;
        }

        /* Tags Display */
        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-top: 0.5rem;
        }

        .tag {
            background: var(--gray-100);
            color: var(--gray-700);
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .tag-color {
            background: var(--accent-1);
            color: var(--white);
        }

        /* Commercial Info */
        .commercial-info {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            border: 1px solid var(--gray-200);
        }

        .commercial-header {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .commercial-header i {
            color: var(--accent-4);
        }

        .commercial-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .commercial-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .commercial-item i {
            color: var(--accent-5);
            width: 16px;
        }

        .commercial-label {
            font-size: 0.75rem;
            color: var(--gray-600);
        }

        .commercial-value {
            font-size: 0.85rem;
            color: var(--gray-800);
            font-weight: 600;
        }

        /* Product Footer */
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

        .btn-details {
            padding: 0.6rem 1.2rem;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-details:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
            color: var(--white);
            text-decoration: none;
        }

        .btn-download {
            padding: 0.6rem 1.2rem;
            background: var(--white);
            color: var(--accent-1);
            border: 1px solid var(--accent-1);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-download:hover {
            background: var(--accent-1);
            color: var(--white);
            text-decoration: none;
        }

        .btn-download.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            border-color: var(--gray-300);
            color: var(--gray-500);
        }

        .btn-download.disabled:hover {
            background: var(--white);
            color: var(--gray-500);
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

        .product-count {
            color: var(--accent-1);
            font-weight: 600;
        }

        /* Modal pour la galerie d'images */
        .gallery-modal .modal-content {
            border-radius: 16px;
            overflow: hidden;
            border: none;
        }

        .gallery-modal .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--white);
            border: none;
        }

        .gallery-modal .modal-body {
            padding: 0;
        }

        .gallery-slider {
            position: relative;
        }

        .gallery-slide {
            display: none;
            width: 100%;
            height: 400px;
            object-fit: contain;
            background: var(--gray-100);
        }

        .gallery-slide.active {
            display: block;
        }

        .gallery-thumbnails {
            display: flex;
            gap: 0.5rem;
            padding: 1rem;
            overflow-x: auto;
            background: var(--gray-50);
        }

        .gallery-thumb {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            flex-shrink: 0;
        }

        .gallery-thumb.active {
            border-color: var(--accent-1);
        }

        .gallery-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .product-card {
            animation: fadeInUp 0.5s ease-out forwards;
            animation-delay: calc(var(--index, 0) * 0.05s);
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

            .filter-form .row > div {
                margin-bottom: 1rem;
            }

            .product-footer {
                flex-direction: column;
                align-items: stretch;
            }

            .specs-grid,
            .commercial-grid {
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

            .footer {
                flex-direction: column;
                gap: 1rem;
            }

            .gallery-slide {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar (identique au dashboard) -->
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
            <a href="catalog_prv.php" class="nav-item active">
                <i class="fas fa-tshirt"></i>
                <span>Catalogue produits</span>
            </a>
            <a href="orders.php" class="nav-item">
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

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <h1>Catalogue Produits</h1>
                <p>Explorez notre collection exclusive de denim professionnel</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
                <a href="dashboard_client.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Retour au dashboard
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <div class="filter-header">
                <div class="filter-title">
                    <i class="fas fa-filter"></i> Filtres avancés
                </div>
                <?php if ($search || $collection_filter || $weight_filter || $wash_filter): ?>
                <a href="catalog_prv.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Réinitialiser
                </a>
                <?php endif; ?>
            </div>
            
            <form method="GET" class="row g-3 filter-form">
                <div class="col-lg-3">
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
                    <select class="form-select" name="weight">
                        <option value="">Tous grammages</option>
                        <option value="light" <?php echo $weight_filter == 'light' ? 'selected' : ''; ?>>Léger (< 10 oz)</option>
                        <option value="medium" <?php echo $weight_filter == 'medium' ? 'selected' : ''; ?>>Moyen (10-14 oz)</option>
                        <option value="heavy" <?php echo $weight_filter == 'heavy' ? 'selected' : ''; ?>>Lourd (> 14 oz)</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <select class="form-select" name="wash">
                        <option value="">Tous lavages</option>
                        <option value="raw" <?php echo $wash_filter == 'raw' ? 'selected' : ''; ?>>Raw / Brut</option>
                        <option value="stone" <?php echo $wash_filter == 'stone' ? 'selected' : ''; ?>>Stone Wash</option>
                        <option value="acid" <?php echo $wash_filter == 'acid' ? 'selected' : ''; ?>>Acid Wash</option>
                        <option value="enzyme" <?php echo $wash_filter == 'enzyme' ? 'selected' : ''; ?>>Enzyme Wash</option>
                    </select>
                </div>
                <div class="col-lg-3">
                    <button type="submit" class="btn-filter w-100">
                        <i class="fas fa-filter"></i> Filtrer résultats
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
                <?php if ($search || $collection_filter || $weight_filter || $wash_filter): ?>
                Essayez de modifier vos critères de recherche pour trouver ce que vous cherchez.
                <?php else: ?>
                Le catalogue est en cours de préparation. Revenez bientôt !
                <?php endif; ?>
            </p>
            <?php if ($search || $collection_filter || $weight_filter || $wash_filter): ?>
            <a href="catalog_prv.php" class="btn btn-outline-primary">Voir tous les produits</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="products-grid">
            <?php foreach($products as $index => $product): ?>
            <div class="product-card" style="--index: <?php echo $index; ?>">
                <?php if (!$product['is_active'] && $is_admin): ?>
                <div class="product-badge">INACTIF</div>
                <?php endif; ?>
                
                <div class="product-image-container">
                    <?php if (!empty($product['images'])): ?>
                    <img src="<?php echo htmlspecialchars($product['images'][0]['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="product-image">
                    <div class="product-image-overlay">
                        <div class="image-count">
                            <i class="fas fa-images"></i>
                            <?php echo count($product['images']); ?> photos
                        </div>
                        <button class="view-gallery" 
                                data-bs-toggle="modal" 
                                data-bs-target="#galleryModal<?php echo $product['id']; ?>">
                            Voir galerie
                        </button>
                    </div>
                    <?php else: ?>
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-image" style="font-size: 3rem; color: var(--gray-400);"></i>
                    </div>
                    <?php endif; ?>
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
                    
                    <!-- Informations générales -->
                    <div class="specs-grid">
                        <div class="spec-item">
                            <i class="fas fa-weight"></i>
                            <div class="spec-content">
                                <div class="spec-label">Grammage</div>
                                <div class="spec-value"><?php echo !empty($product['weight_oz']) ? htmlspecialchars($product['weight_oz']) . ' oz' : 'N/A'; ?></div>
                            </div>
                        </div>
                        
                        <div class="spec-item">
                            <i class="fas fa-tshirt"></i>
                            <div class="spec-content">
                                <div class="spec-label">Composition</div>
                                <div class="spec-value"><?php echo !empty($product['fabric_composition']) ? htmlspecialchars(substr($product['fabric_composition'], 0, 20)) . '...' : 'N/A'; ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Options disponibles -->
                    <?php if (!empty($product['available_colors']) || !empty($product['available_sizes']) || !empty($product['wash_types'])): ?>
                    <div class="specs-grid">
                        <?php if (!empty($product['available_colors'])): ?>
                        <div class="spec-item">
                            <i class="fas fa-palette"></i>
                            <div class="spec-content">
                                <div class="spec-label">Couleurs</div>
                                <div class="tags-container">
                                    <?php 
                                    $colors = explode(',', $product['available_colors']);
                                    foreach(array_slice($colors, 0, 2) as $color): 
                                        if (trim($color)): ?>
                                    <span class="tag tag-color"><?php echo trim($color); ?></span>
                                    <?php endif; endforeach; 
                                    if (count($colors) > 2): ?>
                                    <span class="tag">+<?php echo count($colors) - 2; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($product['available_sizes'])): ?>
                        <div class="spec-item">
                            <i class="fas fa-ruler"></i>
                            <div class="spec-content">
                                <div class="spec-label">Tailles</div>
                                <div class="tags-container">
                                    <?php 
                                    $sizes = explode(',', $product['available_sizes']);
                                    foreach(array_slice($sizes, 0, 3) as $size): 
                                        if (trim($size)): ?>
                                    <span class="tag"><?php echo trim($size); ?></span>
                                    <?php endif; endforeach; 
                                    if (count($sizes) > 3): ?>
                                    <span class="tag">+<?php echo count($sizes) - 3; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Certifications -->
                    <?php if (!empty($product['certifications'])): ?>
                    <div class="spec-item mt-2">
                        <i class="fas fa-certificate"></i>
                        <div class="spec-content">
                            <div class="spec-label">Certifications</div>
                            <div class="tags-container">
                                <?php 
                                $certs = explode(',', $product['certifications']);
                                foreach(array_slice($certs, 0, 3) as $cert): 
                                    if (trim($cert)): ?>
                                <span class="tag"><?php echo trim($cert); ?></span>
                                <?php endif; endforeach; 
                                if (count($certs) > 3): ?>
                                <span class="tag">+<?php echo count($certs) - 3; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Données commerciales -->
                    <div class="commercial-info">
                        <div class="commercial-header">
                            <i class="fas fa-chart-line"></i> Données commerciales
                        </div>
                        <div class="commercial-grid">
                            <div class="commercial-item">
                                <i class="fas fa-box"></i>
                                <div>
                                    <div class="commercial-label">MOQ</div>
                                    <div class="commercial-value"><?php echo $product['moq']; ?> unités</div>
                                </div>
                            </div>
                            <div class="commercial-item">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <div class="commercial-label">Délai</div>
                                    <div class="commercial-value"><?php echo $product['production_time_days']; ?> jours</div>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($product['client_notes'])): ?>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-sticky-note"></i> 
                                <?php echo htmlspecialchars(substr($product['client_notes'], 0, 60)); ?>...
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="product-footer">
                    <a href="det_product.php?id=<?php echo $product['id']; ?>" class="btn-details">
                        <i class="fas fa-eye"></i> Détails complets
                    </a>
                    
                    <?php if (!empty($product['pdf_spec_url'])): ?>
                   
                    <?php else: ?>
                    <span class="btn-download disabled">
                        <i class="fas fa-download"></i> PDF indisponible
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Gallery Modal -->
            <?php if (!empty($product['images'])): ?>
            <div class="modal fade gallery-modal" id="galleryModal<?php echo $product['id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-images me-2"></i>
                                <?php echo htmlspecialchars($product['name']); ?> - Galerie photos
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="gallery-slider">
                                <?php foreach($product['images'] as $img_index => $image): ?>
                                <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                                     class="gallery-slide <?php echo $img_index === 0 ? 'active' : ''; ?>" 
                                     alt="Photo <?php echo $img_index + 1; ?>">
                                <?php endforeach; ?>
                            </div>
                            <div class="gallery-thumbnails">
                                <?php foreach($product['images'] as $img_index => $image): ?>
                                <div class="gallery-thumb <?php echo $img_index === 0 ? 'active' : ''; ?>" 
                                     onclick="changeSlide(<?php echo $product['id']; ?>, <?php echo $img_index; ?>)">
                                    <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                                         alt="Miniature <?php echo $img_index + 1; ?>">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <div>
                <i class="fas fa-gem" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Catalogue Produits v1.0
            </div>
            <div>
                <span class="system-status">
                    <i class="fas fa-circle"></i> Connecté en tant que <?php echo htmlspecialchars($_SESSION['company_name']); ?>
                    <span class="product-count ms-2">• <?php echo count($products); ?> produit<?php echo count($products) > 1 ? 's' : ''; ?></span>
                </span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script>
        // Fonction pour changer les slides dans la galerie
        function changeSlide(productId, slideIndex) {
            const modal = document.getElementById(`galleryModal${productId}`);
            const slides = modal.querySelectorAll('.gallery-slide');
            const thumbs = modal.querySelectorAll('.gallery-thumb');
            
            // Désactiver toutes les slides
            slides.forEach(slide => slide.classList.remove('active'));
            thumbs.forEach(thumb => thumb.classList.remove('active'));
            
            // Activer la slide sélectionnée
            slides[slideIndex].classList.add('active');
            thumbs[slideIndex].classList.add('active');
        }

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

        // Animation des cartes au scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.product-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.transitionDelay = `${index * 0.05}s`;
            observer.observe(card);
        });

        // Auto-play pour les galeries
        document.querySelectorAll('.gallery-modal').forEach(modal => {
            modal.addEventListener('shown.bs.modal', function() {
                const slides = this.querySelectorAll('.gallery-slide');
                if (slides.length <= 1) return;
                
                let currentSlide = 0;
                
                const autoPlay = setInterval(() => {
                    slides[currentSlide].classList.remove('active');
                    const thumbs = this.querySelectorAll('.gallery-thumb');
                    thumbs[currentSlide].classList.remove('active');
                    
                    currentSlide = (currentSlide + 1) % slides.length;
                    
                    slides[currentSlide].classList.add('active');
                    thumbs[currentSlide].classList.add('active');
                }, 5000);
                
                // Arrêter l'auto-play quand la modal est fermée
                this.addEventListener('hidden.bs.modal', () => {
                    clearInterval(autoPlay);
                });
            });
        });

        // Confirmation pour les téléchargements
        

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
        const currentPage = window.location.pathname.split('/').pop() || 'catalog_prv.php';
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.getAttribute('href') === currentPage) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    </script>
</body>
</html>