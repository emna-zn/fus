<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: catalog.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$product_id = intval($_GET['id']);

$query = "SELECT p.*, c.name as collection_name, c.season, c.description as collection_desc 
          FROM products p 
          LEFT JOIN collections c ON p.collection_id = c.id 
          WHERE p.id = ? AND p.is_active = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: catalog.php');
    exit();
}

$images_query = $conn->prepare("SELECT id, image_url, is_main FROM product_images WHERE product_id = ? ORDER BY is_main DESC");
$images_query->bind_param("i", $product_id);
$images_query->execute();
$images = $images_query->get_result();
$similar_query = $conn->prepare("SELECT p.id, p.reference, p.name, p.moq, p.fabric_composition,
                                (SELECT image_url FROM product_images WHERE product_id = p.id AND is_main = 1 LIMIT 1) as image_url
                                FROM products p 
                                WHERE p.collection_id = ? AND p.id != ? AND p.is_active = 1 
                                LIMIT 4");
$similar_query->bind_param("ii", $product['collection_id'], $product_id);
$similar_query->execute();
$similar_products = $similar_query->get_result();

$washings = [];
$colors = [];
$sizes = [];
try {
    $washings_query = $conn->prepare("SELECT washing_type FROM product_washings WHERE product_id = ?");
    $washings_query->bind_param("i", $product_id);
    $washings_query->execute();
    $washings_result = $washings_query->get_result();
    while ($washing = $washings_result->fetch_assoc()) {
        $washings[] = $washing['washing_type'];
    }
} catch (Exception $e) {
    if (!empty($product['wash_types'])) {
        $washings = explode(',', $product['wash_types']);
        $washings = array_map('trim', $washings);
    }
}

try {
    $stock_query = $conn->prepare("SELECT DISTINCT color, size FROM product_stock WHERE product_id = ?");
    $stock_query->bind_param("i", $product_id);
    $stock_query->execute();
    $stock_result = $stock_query->get_result();
    while ($stock = $stock_result->fetch_assoc()) {
        if (!empty($stock['color']) && !in_array($stock['color'], $colors)) {
            $colors[] = $stock['color'];
        }
        if (!empty($stock['size']) && !in_array($stock['size'], $sizes)) {
            $sizes[] = $stock['size'];
        }
    }
} catch (Exception $e) {
    if (!empty($product['available_colors'])) {
        $colors = explode(',', $product['available_colors']);
        $colors = array_map('trim', $colors);
    }
    if (!empty($product['available_sizes'])) {
        $sizes = explode(',', $product['available_sizes']);
        $sizes = array_map('trim', $sizes);
    }
}

if (empty($colors) && !empty($product['colors'])) {
    $colors = explode(',', $product['colors']);
    $colors = array_map('trim', $colors);
}

if (empty($sizes) && !empty($product['sizes'])) {
    $sizes = explode(',', $product['sizes']);
    $sizes = array_map('trim', $sizes);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Produit - FUS Denim</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- Lightbox -->
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

        /* Product Details */
        .product-detail-card {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            margin-bottom: 2rem;
        }

        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 2rem;
        }

        .breadcrumb-item a {
            color: var(--accent-1);
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumb-item.active {
            color: var(--gray-500);
        }

        .product-image-main {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .product-image-main:hover {
            transform: scale(1.02);
        }

        .product-thumbnails {
            display: flex;
            gap: 0.75rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }

        .product-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .product-thumbnail:hover,
        .product-thumbnail.active {
            border-color: var(--accent-1);
            transform: scale(1.05);
        }

        .product-badges {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .product-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-collection {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: var(--white);
        }

        .badge-moq {
            background: linear-gradient(135deg, var(--accent-5), #FBBF24);
            color: var(--white);
        }

        .badge-fast {
            background: linear-gradient(135deg, var(--accent-4), #34D399);
            color: var(--white);
        }

        .specs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .spec-card {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 1.5rem;
            border-left: 4px solid var(--accent-1);
        }

        .spec-card h5 {
            color: var(--accent-1);
            margin-bottom: 1rem;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .spec-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .spec-item:last-child {
            border-bottom: none;
        }

        .spec-label {
            color: var(--gray-600);
            font-weight: 500;
        }

        .spec-value {
            color: var(--primary);
            font-weight: 600;
            text-align: right;
        }

        .product-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .btn-action {
            flex: 1;
            min-width: 200px;
            padding: 1rem;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-order {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: var(--white);
            border: none;
        }

        .btn-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }

        .btn-outline {
            background: transparent;
            color: var(--accent-1);
            border: 2px solid var(--accent-1);
        }

        .btn-outline:hover {
            background: var(--accent-1);
            color: var(--white);
        }

        /* Similar Products */
        .similar-section {
            margin-top: 3rem;
        }

        .similar-products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .similar-card {
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .similar-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            text-decoration: none;
            color: inherit;
            border-color: var(--accent-1);
        }

        .similar-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .similar-content {
            padding: 1.25rem;
        }

        .similar-name {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .similar-ref {
            color: var(--gray-500);
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        .similar-moq {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: var(--gray-100);
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--accent-5);
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
            .btn-action {
                min-width: 100%;
            }
            .product-image-main {
                height: 300px;
            }
            .specs-grid {
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
            .similar-products {
                grid-template-columns: 1fr;
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
            <i class="fas fa-gem"></i>
            <h2>FUS Client</h2>
        </div>

        <div class="nav-section">
            <div class="nav-label">Menu Principal</div>
            <a href="dashboard_client.php" class="nav-item">
                <i class="fas fa-chart-line"></i>
                <span>Tableau de bord</span>
            </a>
            <a href="catalog.php" class="nav-item active">
                <i class="fas fa-tshirt"></i>
                <span>Catalogue produits</span>
            </a>
            <a href="orders.php" class="nav-item">
                <i class="fas fa-shopping-bag"></i>
                <span>Mes commandes</span>
            </a>
            <a href="collections.php" class="nav-item">
                <i class="fas fa-layer-group"></i>
                <span>Collections</span>
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
                <h1>Détails Produit</h1>
                <p>Informations complètes sur le produit</p>
            </div>
            <a href="catalog.php" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i>Retour au catalogue
            </a>
        </div>

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard_client.php">Tableau de bord</a></li>
                <li class="breadcrumb-item"><a href="catalog.php">Catalogue</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>

        <div class="product-detail-card">
            <div class="row">
                <!-- Images Gallery -->
                <div class="col-lg-6">
                    <?php 
                    $main_image = null;
                    $other_images = [];
                    
                    while ($img = $images->fetch_assoc()) {
                        if ($img['is_main']) {
                            $main_image = $img;
                        } else {
                            $other_images[] = $img;
                        }
                    }
                    
                    if (!$main_image && count($other_images) > 0) {
                        $main_image = $other_images[0];
                        array_shift($other_images);
                    }
                    ?>
                    
                    <a href="<?php echo $main_image ? htmlspecialchars($main_image['image_url']) : 'https://images.unsplash.com/photo-1542272604-787c3835535d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'; ?>" 
                       data-lightbox="product-gallery" 
                       data-title="<?php echo htmlspecialchars($product['name']); ?>">
                        <img src="<?php echo $main_image ? htmlspecialchars($main_image['image_url']) : 'https://images.unsplash.com/photo-1542272604-787c3835535d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'; ?>" 
                             class="product-image-main" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </a>
                    
                    <?php if (count($other_images) > 0): ?>
                        <div class="product-thumbnails">
                            <?php foreach ($other_images as $thumb): ?>
                                <a href="<?php echo htmlspecialchars($thumb['image_url']); ?>" 
                                   data-lightbox="product-gallery"
                                   data-title="<?php echo htmlspecialchars($product['name']); ?>">
                                    <img src="<?php echo htmlspecialchars($thumb['image_url']); ?>" 
                                         class="product-thumbnail" 
                                         alt="Thumbnail">
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Product Details -->
                <div class="col-lg-6">
                    <!-- Badges -->
                    <div class="product-badges">
                        <?php if (!empty($product['collection_name'])): ?>
                            <span class="product-badge badge-collection">
                                <?php echo htmlspecialchars($product['collection_name']); ?> 
                                <?php if (!empty($product['season'])): ?>
                                    • <?php echo htmlspecialchars($product['season']); ?>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                        <span class="product-badge badge-moq">
                            MOQ: <?php echo !empty($product['moq']) ? $product['moq'] : 'N/A'; ?> pièces
                        </span>
                        <?php if (!empty($product['production_time']) && $product['production_time'] <= 30): ?>
                            <span class="product-badge badge-fast">
                                Production rapide
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Title & Reference -->
                    <h2 class="mt-3"><?php echo htmlspecialchars($product['name']); ?></h2>
                    <p class="text-muted mb-4">
                        <i class="fas fa-hashtag me-2"></i>
                        Référence: <strong><?php echo htmlspecialchars($product['reference']); ?></strong>
                    </p>
                    
                    <!-- Description -->
                    <?php if (!empty($product['description'])): ?>
                        <div class="mb-4">
                            <h5><i class="fas fa-align-left me-2"></i>Description</h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Specifications Grid -->
                    <div class="specs-grid">
                        <!-- Informations générales -->
                        <div class="spec-card">
                            <h5><i class="fas fa-info-circle me-2"></i>Informations générales</h5>
                            <div class="spec-item">
                                <span class="spec-label">Référence</span>
                                <span class="spec-value"><?php echo htmlspecialchars($product['reference']); ?></span>
                            </div>
                            <?php if (!empty($product['collection_name'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Collection</span>
                                <span class="spec-value"><?php echo htmlspecialchars($product['collection_name']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($product['season'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Saison</span>
                                <span class="spec-value"><?php echo htmlspecialchars($product['season']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Données techniques -->
                        <div class="spec-card">
                            <h5><i class="fas fa-cogs me-2"></i>Données techniques</h5>
                            <?php if (!empty($product['fabric_composition'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Composition tissu</span>
                                <span class="spec-value"><?php echo htmlspecialchars($product['fabric_composition']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($product['weight'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Grammage</span>
                                <span class="spec-value"><?php echo $product['weight']; ?> oz</span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($washings)): ?>
                            <div class="spec-item">
                                <span class="spec-label">Types de lavages</span>
                                <span class="spec-value"><?php echo implode(', ', $washings); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Couleurs & Tailles -->
                        <div class="spec-card">
                            <h5><i class="fas fa-palette me-2"></i>Couleurs & Tailles</h5>
                            <?php if (!empty($colors)): ?>
                            <div class="spec-item">
                                <span class="spec-label">Couleurs disponibles</span>
                                <span class="spec-value"><?php echo implode(', ', $colors); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($sizes)): ?>
                            <div class="spec-item">
                                <span class="spec-label">Tailles disponibles</span>
                                <span class="spec-value"><?php echo implode(', ', $sizes); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($product['certifications'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Certifications</span>
                                <span class="spec-value"><?php echo htmlspecialchars($product['certifications']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Données commerciales -->
                        <div class="spec-card">
                            <h5><i class="fas fa-chart-line me-2"></i>Données commerciales</h5>
                            <div class="spec-item">
                                <span class="spec-label">MOQ (Quantité min.)</span>
                                <span class="spec-value"><?php echo !empty($product['moq']) ? $product['moq'] : 'N/A'; ?> pièces</span>
                            </div>
                            <?php if (!empty($product['production_time'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Délai de production</span>
                                <span class="spec-value"><?php echo $product['production_time']; ?> jours</span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($product['notes'])): ?>
                            <div class="spec-item">
                                <span class="spec-label">Notes spécifiques</span>
                                <span class="spec-value"><?php echo htmlspecialchars(substr($product['notes'], 0, 100)); ?>...</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="product-actions">
                        <a href="orders.php?action=new&product=<?php echo $product['id']; ?>" 
                           class="btn-action btn-order">
                            <i class="fas fa-shopping-cart"></i>
                            Passer commande
                        </a>
                        
                        <?php if (!empty($product['pdf_url'])): ?>
                            <a href="<?php echo htmlspecialchars($product['pdf_url']); ?>" 
                               class="btn-action btn-outline" 
                               target="_blank">
                                <i class="fas fa-file-pdf"></i>
                                Fiche technique PDF
                            </a>
                        <?php endif; ?>
                        
                        <button type="button" class="btn-action btn-outline" onclick="printDetails()">
                            <i class="fas fa-print"></i>
                            Imprimer
                        </button>
                        
                        <button type="button" class="btn-action btn-outline" onclick="shareProduct()">
                            <i class="fas fa-share-alt"></i>
                            Partager
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Similar Products -->
        <?php if ($similar_products && $similar_products->num_rows > 0): ?>
            <div class="similar-section">
                <h3><i class="fas fa-layer-group me-2"></i>Produits similaires</h3>
                <p class="text-muted mb-4">Découvrez d'autres articles de la même collection</p>
                
                <div class="similar-products">
                    <?php while ($similar = $similar_products->fetch_assoc()): ?>
                        <a href="product_details.php?id=<?php echo $similar['id']; ?>" class="similar-card">
                            <img src="<?php echo !empty($similar['image_url']) ? htmlspecialchars($similar['image_url']) : 'https://images.unsplash.com/photo-1542272604-787c3835535d?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'; ?>" 
                                 class="similar-image" 
                                 alt="<?php echo htmlspecialchars($similar['name']); ?>">
                            <div class="similar-content">
                                <h6 class="similar-name"><?php echo htmlspecialchars($similar['name']); ?></h6>
                                <p class="similar-ref">REF: <?php echo htmlspecialchars($similar['reference']); ?></p>
                                <?php if (!empty($similar['fabric_composition'])): ?>
                                <p class="text-muted small mb-2">
                                    <?php echo htmlspecialchars(substr($similar['fabric_composition'], 0, 50)); ?>...
                                </p>
                                <?php endif; ?>
                                <span class="similar-moq">MOQ: <?php echo $similar['moq']; ?></span>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <div>
                <i class="fas fa-gem" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Détails Produit
            </div>
            <div>
                <span class="system-status">
                    <i class="fas fa-circle"></i> Référence: <?php echo htmlspecialchars($product['reference']); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    
    <script>
        // Configuration Lightbox
        lightbox.option({
            'resizeDuration': 200,
            'wrapAround': true,
            'albumLabel': "Image %1 sur %2",
            'fadeDuration': 300
        });

        // Fonction pour imprimer les détails
        function printDetails() {
            const printContent = `
                <html>
                <head>
                    <title><?php echo htmlspecialchars($product['name']); ?> - Fiche technique</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        h1 { color: #333; }
                        .specs { margin-top: 20px; }
                        .spec-item { margin-bottom: 10px; }
                        .label { font-weight: bold; }
                        @media print {
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    <p><strong>Référence:</strong> <?php echo htmlspecialchars($product['reference']); ?></p>
                    <?php if (!empty($product['collection_name'])): ?>
                    <p><strong>Collection:</strong> <?php echo htmlspecialchars($product['collection_name']); ?> 
                    <?php if (!empty($product['season'])): ?>
                    • <?php echo htmlspecialchars($product['season']); ?>
                    <?php endif; ?>
                    </p>
                    <?php endif; ?>
                    
                    <div class="specs">
                        <h3>Spécifications techniques</h3>
                        <?php if (!empty($product['fabric_composition'])): ?>
                        <p><strong>Composition:</strong> <?php echo htmlspecialchars($product['fabric_composition']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($product['weight'])): ?>
                        <p><strong>Grammage:</strong> <?php echo $product['weight']; ?> oz</p>
                        <?php endif; ?>
                        <p><strong>MOQ:</strong> <?php echo !empty($product['moq']) ? $product['moq'] : 'N/A'; ?> pièces</p>
                        <?php if (!empty($product['production_time'])): ?>
                        <p><strong>Délai production:</strong> <?php echo $product['production_time']; ?> jours</p>
                        <?php endif; ?>
                        <?php if (!empty($colors)): ?>
                        <p><strong>Couleurs:</strong> <?php echo implode(', ', $colors); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($sizes)): ?>
                        <p><strong>Tailles:</strong> <?php echo implode(', ', $sizes); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="no-print">
                        <p><small>Document généré le ${new Date().toLocaleDateString()} à ${new Date().toLocaleTimeString()}</small></p>
                    </div>
                </body>
                </html>
            `;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.print();
        }

        // Fonction pour partager le produit
        function shareProduct() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo htmlspecialchars($product["name"]); ?>',
                    text: 'Découvrez ce produit FUS Denim: <?php echo htmlspecialchars($product["name"]); ?>',
                    url: window.location.href
                })
                .then(() => console.log('Produit partagé avec succès'))
                .catch((error) => console.log('Erreur de partage:', error));
            } else {
                // Fallback pour les navigateurs sans support
                const shareUrl = window.location.href;
                navigator.clipboard.writeText(shareUrl).then(() => {
                    showNotification('Lien copié dans le presse-papier!', 'success');
                });
            }
        }

        // Notification
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
            notification.style.zIndex = '9999';
            notification.style.minWidth = '300px';
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-3"></i>
                    <div>${message}</div>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            // Raccourcis clavier
            document.addEventListener('keydown', function(e) {
                // Échap pour retour au catalogue
                if (e.key === 'Escape') {
                    window.location.href = 'catalog.php';
                }
                // Espace pour commander
                if (e.key === ' ' && !e.target.matches('input, textarea, button, a')) {
                    e.preventDefault();
                    document.querySelector('.btn-order').click();
                }
                // P pour imprimer
                if (e.key === 'p' && (e.ctrlKey || e.metaKey)) {
                    e.preventDefault();
                    printDetails();
                }
            });

            // Animation des cartes au chargement
            const cards = document.querySelectorAll('.spec-card, .similar-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>