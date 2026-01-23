<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: catalog_prv.php');
    exit();
}

$product_id = intval($_GET['id']);
$database = new Database();
$conn = $database->getConnection();
$stmt = $conn->prepare("
    SELECT p.*, c.name as collection_name, c.season as collection_season, c.description as collection_description
    FROM products p 
    LEFT JOIN collections c ON p.collection_id = c.id 
    WHERE p.id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: catalog_prv.php');
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();
$stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$images_result = $stmt->get_result();
$images = [];
while ($image = $images_result->fetch_assoc()) {
    $images[] = $image;
}
$stmt->close();
$materials = [];
if (!empty($product['material_ids'])) {
    $material_ids = explode(',', $product['material_ids']);
    $material_ids = array_filter($material_ids, 'is_numeric');
    
    if (!empty($material_ids)) {
        $placeholders = str_repeat('?,', count($material_ids) - 1) . '?';
        $stmt = $conn->prepare("SELECT id, name, code, type, supplier, sustainability_rating FROM materials WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($material_ids)), ...$material_ids);
        $stmt->execute();
        $materials_result = $stmt->get_result();
        while ($material = $materials_result->fetch_assoc()) {
            $materials[] = $material;
        }
        $stmt->close();
    }
}

$is_admin = ($_SESSION['role'] === 'admin');
$similar_products = [];
$similar_stmt = $conn->prepare("
    SELECT p.id, p.reference, p.name, p.weight_oz, p.fabric_composition, p.moq, 
           (SELECT image_url FROM product_images WHERE product_id = p.id ORDER BY is_main DESC LIMIT 1) as main_image
    FROM products p 
    WHERE p.collection_id = ? AND p.id != ? AND p.is_active = 1 
    LIMIT 4
");
$similar_stmt->bind_param("ii", $product['collection_id'], $product_id);
$similar_stmt->execute();
$similar_result = $similar_stmt->get_result();
while ($row = $similar_result->fetch_assoc()) {
    $similar_products[] = $row;
}
$similar_stmt->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - FUS Denim</title>
    
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

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .header-title h1 {
            font-size: 1.8rem;
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

        /* Breadcrumb */
        .breadcrumb {
            background: var(--white);
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
        }

        .breadcrumb a {
            color: var(--accent-1);
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Product Details Container */
        .product-details-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        @media (max-width: 992px) {
            .product-details-container {
                grid-template-columns: 1fr;
            }
        }

        /* Image Gallery */
        .image-gallery {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
        }

        .main-image {
            width: 100%;
            height: 400px;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1rem;
            background: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .thumbnail-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.75rem;
        }

        .thumbnail {
            width: 100%;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .thumbnail:hover {
            border-color: var(--accent-1);
        }

        .thumbnail.active {
            border-color: var(--accent-1);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Product Info */
        .product-info {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
        }

        .product-header {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-100);
        }

        .product-ref {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            color: var(--accent-1);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .product-name {
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .product-collection {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-600);
            font-size: 0.95rem;
        }

        .product-collection i {
            color: var(--accent-1);
        }

        /* Status Badge */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.8rem;
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

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        .info-card {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--gray-200);
        }

        .info-card-title {
            font-size: 0.9rem;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-card-title i {
            color: var(--accent-1);
        }

        .info-item {
            margin-bottom: 0.75rem;
        }

        .info-label {
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-size: 0.95rem;
            color: var(--gray-800);
            font-weight: 500;
        }

        /* Tags */
        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .tag {
            background: var(--gray-100);
            color: var(--gray-700);
            padding: 0.3rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .tag-color {
            background: var(--accent-1);
            color: var(--white);
        }

        /* Description Card */
        .description-card {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
        }

        .description-card h3 {
            font-size: 1.25rem;
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .description-card h3 i {
            color: var(--accent-1);
        }

        .description-content {
            color: var(--gray-700);
            line-height: 1.8;
            white-space: pre-line;
        }

        /* Materials Grid */
        .materials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .material-card {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
        }

        .material-card:hover {
            border-color: var(--accent-1);
            box-shadow: var(--shadow-sm);
        }

        .material-name {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .material-code {
            font-family: 'Courier New', monospace;
            color: var(--gray-500);
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        .material-type {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            background: var(--accent-1);
            color: var(--white);
            border-radius: 12px;
            font-size: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .sustainability-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.75rem;
            font-size: 0.85rem;
        }

        .sustainability-rating i {
            color: var(--accent-4);
        }

        /* Specifications Table */
        .specs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .specs-table tr {
            border-bottom: 1px solid var(--gray-100);
        }

        .specs-table td {
            padding: 0.75rem 0;
            color: var(--gray-700);
        }

        .specs-table td:first-child {
            font-weight: 500;
            color: var(--gray-600);
            width: 40%;
        }

        /* Commercial Info */
        .commercial-card {
            background: linear-gradient(135deg, var(--gray-50), var(--gray-100));
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--gray-200);
        }

        .commercial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .commercial-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .commercial-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.25rem;
        }

        .commercial-content h4 {
            font-size: 0.9rem;
            color: var(--gray-600);
            margin-bottom: 0.25rem;
        }

        .commercial-content p {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }

        /* Similar Products */
        .similar-products {
            margin-top: 3rem;
        }

        .section-title {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--accent-1);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .similar-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
        }

        .similar-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            text-decoration: none;
            color: inherit;
            border-color: var(--accent-1);
        }

        .similar-image {
            width: 100%;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            background: var(--gray-100);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .similar-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .similar-ref {
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
            color: var(--accent-1);
            margin-bottom: 0.25rem;
        }

        .similar-name {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .similar-specs {
            font-size: 0.85rem;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
        }

        .similar-moq {
            font-size: 0.8rem;
            color: var(--accent-4);
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

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-100);
        }

        .btn-download {
            padding: 0.75rem 1.5rem;
            background: var(--white);
            color: var(--accent-1);
            border: 1px solid var(--accent-1);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
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

            .action-buttons {
                flex-direction: column;
            }

            .thumbnail-grid {
                grid-template-columns: repeat(3, 1fr);
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

            .main-image {
                height: 300px;
            }

            .product-name {
                font-size: 1.5rem;
            }

            .footer {
                flex-direction: column;
                gap: 1rem;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .product-info, .image-gallery, .description-card, .commercial-card {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
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
                <h1>Détails du produit</h1>
                <p>Informations complètes et spécifications techniques</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
                <a href="catalog_prv.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Retour au catalogue
                </a>
            </div>
        </div>

        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="dashboard_client.php">Tableau de bord</a> &gt;
            <a href="catalog_prv.php">Catalogue</a> &gt;
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </nav>

        <!-- Product Details -->
        <div class="product-details-container">
            <!-- Image Gallery -->
            <div class="image-gallery">
                <div class="main-image" id="mainImage">
                    <?php if (!empty($images)): ?>
                        <img src="<?php echo htmlspecialchars($images[0]['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             id="currentImage">
                    <?php else: ?>
                        <i class="fas fa-image fa-4x" style="color: var(--gray-400);"></i>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($images)): ?>
                <div class="thumbnail-grid">
                    <?php foreach($images as $index => $image): ?>
                    <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                         onclick="changeMainImage('<?php echo htmlspecialchars($image['image_url']); ?>', this)">
                        <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                             alt="Miniature <?php echo $index + 1; ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <?php if (!empty($product['pdf_spec_url'])): ?>
                    <a href="<?php echo htmlspecialchars($product['pdf_spec_url']); ?>" 
                       class="btn-download" 
                       target="_blank"
                       download="Fiche_technique_<?php echo $product['reference']; ?>.pdf">
                        <i class="fas fa-download"></i> Télécharger la fiche PDF
                    </a>
                    <?php else: ?>
                    <span class="btn-download disabled">
                        <i class="fas fa-download"></i> Fiche PDF indisponible
                    </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['techpack_url'])): ?>
                    <a href="<?php echo htmlspecialchars($product['techpack_url']); ?>" 
                       class="btn-download" 
                       target="_blank">
                        <i class="fas fa-file-alt"></i> Voir le techpack
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Information -->
            <div class="product-info">
                <div class="product-header">
                    <div class="product-ref"><?php echo htmlspecialchars($product['reference']); ?></div>
                    <h2 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h2>
                    
                    <?php if ($product['collection_name']): ?>
                    <div class="product-collection">
                        <i class="fas fa-layer-group"></i>
                        <?php echo htmlspecialchars($product['collection_name']); ?>
                        <?php if ($product['collection_season']): ?>
                        <span class="text-muted">(<?php echo $product['collection_season']; ?>)</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-2">
                        <span class="status-badge <?php echo $product['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                            <?php echo $product['is_active'] ? 'Actif' : 'Inactif'; ?>
                        </span>
                    </div>
                </div>

                <!-- Specifications -->
                <div class="info-grid">
                    <!-- Fabric Specifications -->
                    <div class="info-card">
                        <h3 class="info-card-title">
                            <i class="fas fa-tshirt"></i> Spécifications tissu
                        </h3>
                        <div class="info-item">
                            <div class="info-label">Grammage</div>
                            <div class="info-value"><?php echo !empty($product['weight_oz']) ? htmlspecialchars($product['weight_oz']) . ' oz' : 'N/A'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Composition</div>
                            <div class="info-value"><?php echo !empty($product['fabric_composition']) ? htmlspecialchars($product['fabric_composition']) : 'N/A'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Largeur</div>
                            <div class="info-value"><?php echo !empty($product['fabric_width']) ? htmlspecialchars($product['fabric_width']) . ' cm' : 'N/A'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Type de tissage</div>
                            <div class="info-value"><?php echo !empty($product['weave_type']) ? htmlspecialchars($product['weave_type']) : 'N/A'; ?></div>
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="info-card">
                        <h3 class="info-card-title">
                            <i class="fas fa-cog"></i> Options disponibles
                        </h3>
                        <?php if (!empty($product['available_colors'])): ?>
                        <div class="info-item">
                            <div class="info-label">Couleurs</div>
                            <div class="tags-container">
                                <?php 
                                $colors = explode(',', $product['available_colors']);
                                foreach($colors as $color): 
                                    if (trim($color)): ?>
                                <span class="tag tag-color"><?php echo trim($color); ?></span>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($product['available_sizes'])): ?>
                        <div class="info-item">
                            <div class="info-label">Tailles</div>
                            <div class="tags-container">
                                <?php 
                                $sizes = explode(',', $product['available_sizes']);
                                foreach($sizes as $size): 
                                    if (trim($size)): ?>
                                <span class="tag"><?php echo trim($size); ?></span>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($product['wash_types'])): ?>
                        <div class="info-item">
                            <div class="info-label">Lavages</div>
                            <div class="tags-container">
                                <?php 
                                $washes = explode(',', $product['wash_types']);
                                foreach($washes as $wash): 
                                    if (trim($wash)): ?>
                                <span class="tag"><?php echo trim($wash); ?></span>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Technical Specifications -->
                <div class="info-card mb-3">
                    <h3 class="info-card-title">
                        <i class="fas fa-microscope"></i> Caractéristiques techniques
                    </h3>
                    <table class="specs-table">
                        <?php if (!empty($product['stretch_percentage'])): ?>
                        <tr>
                            <td>Élasticité</td>
                            <td><?php echo htmlspecialchars($product['stretch_percentage']); ?>%</td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (!empty($product['shrinkage_rate'])): ?>
                        <tr>
                            <td>Rétraction</td>
                            <td><?php echo htmlspecialchars($product['shrinkage_rate']); ?>%</td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (!empty($product['tensile_strength'])): ?>
                        <tr>
                            <td>Résistance à la traction</td>
                            <td><?php echo htmlspecialchars($product['tensile_strength']); ?> N</td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (!empty($product['abrasion_resistance'])): ?>
                        <tr>
                            <td>Résistance à l'abrasion</td>
                            <td><?php echo htmlspecialchars($product['abrasion_resistance']); ?> cycles</td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (!empty($product['colorfastness'])): ?>
                        <tr>
                            <td>Solidité de la teinture</td>
                            <td>Classe <?php echo htmlspecialchars($product['colorfastness']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Commercial Info -->
                <div class="commercial-card">
                    <div class="commercial-grid">
                        <div class="commercial-item">
                            <div class="commercial-icon">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="commercial-content">
                                <h4>Quantité minimale</h4>
                                <p><?php echo $product['moq']; ?> unités</p>
                            </div>
                        </div>
                        
                        <div class="commercial-item">
                            <div class="commercial-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="commercial-content">
                                <h4>Délai de production</h4>
                                <p><?php echo $product['production_time_days']; ?> jours</p>
                            </div>
                        </div>
                        
                        <?php if (!empty($product['unit_price'])): ?>
                        <div class="commercial-item">
                            <div class="commercial-icon">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div class="commercial-content">
                                <h4>Prix unitaire</h4>
                                <p><?php echo number_format($product['unit_price'], 2, ',', ' '); ?> €</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($product['minimum_order_value'])): ?>
                        <div class="commercial-item">
                            <div class="commercial-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="commercial-content">
                                <h4>Valeur minimale</h4>
                                <p><?php echo number_format($product['minimum_order_value'], 2, ',', ' '); ?> €</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description -->
        <?php if (!empty($product['description'])): ?>
        <div class="description-card">
            <h3>
                <i class="fas fa-align-left"></i> Description
            </h3>
            <div class="description-content">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Materials -->
        <?php if (!empty($materials)): ?>
        <div class="description-card">
            <h3>
                <i class="fas fa-cubes"></i> Matériaux
            </h3>
            <div class="materials-grid">
                <?php foreach($materials as $material): ?>
                <div class="material-card">
                    <div class="material-name"><?php echo htmlspecialchars($material['name']); ?></div>
                    <div class="material-code">Code: <?php echo htmlspecialchars($material['code']); ?></div>
                    <div class="material-type"><?php echo htmlspecialchars($material['type']); ?></div>
                    
                    <?php if (!empty($material['supplier'])): ?>
                    <div class="info-item">
                        <div class="info-label">Fournisseur</div>
                        <div class="info-value"><?php echo htmlspecialchars($material['supplier']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($material['sustainability_rating'])): ?>
                    <div class="sustainability-rating">
                        <i class="fas fa-leaf"></i>
                        <span>Éco-score: <?php echo htmlspecialchars($material['sustainability_rating']); ?>/10</span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Certifications -->
        <?php if (!empty($product['certifications'])): ?>
        <div class="description-card">
            <h3>
                <i class="fas fa-certificate"></i> Certifications
            </h3>
            <div class="tags-container">
                <?php 
                $certs = explode(',', $product['certifications']);
                foreach($certs as $cert): 
                    if (trim($cert)): ?>
                <span class="tag"><?php echo trim($cert); ?></span>
                <?php endif; endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Notes -->
        <?php if (!empty($product['client_notes'])): ?>
        <div class="description-card">
            <h3>
                <i class="fas fa-sticky-note"></i> Notes client
            </h3>
            <div class="description-content">
                <?php echo nl2br(htmlspecialchars($product['client_notes'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Similar Products -->
        <?php if (!empty($similar_products)): ?>
        <div class="similar-products">
            <h3 class="section-title">
                <i class="fas fa-random"></i> Produits similaires
            </h3>
            <div class="products-grid">
                <?php foreach($similar_products as $similar): ?>
                <a href="product_view.php?id=<?php echo $similar['id']; ?>" class="similar-card">
                    <div class="similar-image">
                        <?php if (!empty($similar['main_image'])): ?>
                        <img src="<?php echo htmlspecialchars($similar['main_image']); ?>" 
                             alt="<?php echo htmlspecialchars($similar['name']); ?>">
                        <?php else: ?>
                        <i class="fas fa-image fa-2x" style="color: var(--gray-400);"></i>
                        <?php endif; ?>
                    </div>
                    <div class="similar-ref"><?php echo htmlspecialchars($similar['reference']); ?></div>
                    <div class="similar-name"><?php echo htmlspecialchars($similar['name']); ?></div>
                    <?php if (!empty($similar['weight_oz'])): ?>
                    <div class="similar-specs"><?php echo htmlspecialchars($similar['weight_oz']); ?> oz</div>
                    <?php endif; ?>
                    <?php if (!empty($similar['fabric_composition'])): ?>
                    <div class="similar-specs"><?php echo htmlspecialchars(substr($similar['fabric_composition'], 0, 30)); ?>...</div>
                    <?php endif; ?>
                    <div class="similar-moq">MOQ: <?php echo $similar['moq']; ?> unités</div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <div>
                <i class="fas fa-gem" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Détails produit v1.0
            </div>
            <div>
                <span class="system-status">
                    <i class="fas fa-circle"></i> Connecté en tant que <?php echo htmlspecialchars($_SESSION['company_name']); ?>
                </span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script>
        // Changer l'image principale
        function changeMainImage(imageUrl, thumbnail) {
            const mainImage = document.getElementById('currentImage');
            mainImage.src = imageUrl;
            
            // Mettre à jour les miniatures actives
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            thumbnail.classList.add('active');
        }

        // Lightbox pour les images
        document.querySelectorAll('#currentImage, .thumbnail img').forEach(img => {
            img.addEventListener('click', function(e) {
                if (e.target.id === 'currentImage') {
                    lightbox.start(e.target);
                }
            });
        });

        // Mise à jour de l'heure
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

        // Navigation active
        const currentPage = window.location.pathname.split('/').pop() || 'product_view.php';
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.getAttribute('href') === 'catalog_prv.php') {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Confirmation pour les téléchargements
        document.querySelectorAll('.btn-download').forEach(btn => {
            if (!btn.classList.contains('disabled')) {
                btn.addEventListener('click', function(e) {
                    if (this.textContent.includes('Télécharger')) {
                        if (!confirm('Télécharger la fiche technique PDF ?')) {
                            e.preventDefault();
                        }
                    }
                });
            }
        });

        // Zoom sur l'image principale
        const mainImage = document.getElementById('mainImage');
        const currentImage = document.getElementById('currentImage');
        
        if (mainImage && currentImage) {
            mainImage.addEventListener('click', function() {
                if (currentImage.src) {
                    lightbox.start(currentImage);
                }
            });
        }

        // Animation au défilement
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.description-card, .commercial-card, .similar-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.transitionDelay = `${index * 0.1}s`;
            observer.observe(card);
        });
    </script>
</body>
</html>