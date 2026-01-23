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
    header('Location: products.php');
    exit();
}

$product_id = intval($_GET['id']);
$stmt = $conn->prepare("
    SELECT p.*, c.name as collection_name, c.season as collection_season 
    FROM products p 
    LEFT JOIN collections c ON p.collection_id = c.id 
    WHERE p.id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();
if (!$product) {
    header('Location: products.php');
    exit();
}
$images_stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, upload_date DESC");
$images_stmt->bind_param("i", $product_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result();
$product_images = [];
while ($image = $images_result->fetch_assoc()) {
    $product_images[] = $image;
}
$images_stmt->close();

$orders_stmt = $conn->prepare("
    SELECT COUNT(DISTINCT o.id) as order_count 
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.id 
    WHERE oi.product_id = ? AND o.status IN ('received', 'production', 'shipped')
");
$orders_stmt->bind_param("i", $product_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$order_stats = $orders_result->fetch_assoc();
$orders_stmt->close();
$recent_orders_stmt = $conn->prepare("
    SELECT o.id, o.reference as order_ref, o.status, o.created_at, 
           u.company_name, SUM(oi.quantity) as total_quantity 
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.id 
    JOIN users u ON o.client_id = u.id 
    WHERE oi.product_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$recent_orders_stmt->bind_param("i", $product_id);
$recent_orders_stmt->execute();
$recent_orders_result = $recent_orders_stmt->get_result();
$recent_orders = [];
while ($order = $recent_orders_result->fetch_assoc()) {
    $recent_orders[] = $order;
}
$recent_orders_stmt->close();
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

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .btn-modern {
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            border: none;
            color: var(--white);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.2);
            color: var(--white);
            text-decoration: none;
        }

        .btn-outline-secondary {
            border: 1px solid var(--gray-300);
            color: var(--gray-600);
        }

        .btn-outline-secondary:hover {
            background: var(--gray-100);
            color: var(--primary);
            text-decoration: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, #EF4444, #DC2626);
            border: none;
            color: var(--white);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.2);
            color: var(--white);
            text-decoration: none;
        }

        /* Product Overview */
        .product-overview {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--gray-100);
        }

        .product-title h2 {
            font-size: 1.75rem;
            color: var(--primary);
            margin: 0;
        }

        .product-reference {
            color: var(--gray-500);
            font-size: 1rem;
            margin: 0.5rem 0 0 0;
        }

        .product-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .status-inactive {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
        }

        .product-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        /* Image Gallery */
        .image-gallery {
            position: relative;
        }

        .main-image {
            width: 100%;
            height: 400px;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1rem;
            border: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-thumbnails {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 0.5rem;
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
        }

        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Product Details */
        .product-details {
            display: grid;
            gap: 1.5rem;
        }

        .detail-section {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--gray-200);
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--accent-1);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .detail-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 0.95rem;
            color: var(--gray-700);
        }

        .detail-value.empty {
            color: var(--gray-400);
            font-style: italic;
        }

        /* Tags Display */
        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .tag {
            background: var(--accent-1);
            color: var(--white);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.25rem;
        }

        .stat-card:nth-child(1) .stat-icon {
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-1);
        }

        .stat-card:nth-child(2) .stat-icon {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .stat-card:nth-child(3) .stat-icon {
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent-5);
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

        /* Recent Orders Table */
        .recent-orders {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
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
            font-size: 0.9rem;
        }

        .table-modern a {
            color: var(--accent-1);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .table-modern a:hover {
            color: var(--accent-2);
            text-decoration: underline;
        }

        /* Status Badges */
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
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .badge-completed {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .badge-cancelled {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
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

        /* Responsive */
        @media (max-width: 1200px) {
            .sidebar {
                width: 260px;
            }

            .main-content {
                margin-left: 260px;
                padding: 1.5rem;
            }

            .product-grid {
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

            .action-buttons {
                flex-wrap: wrap;
            }

            .product-header {
                flex-direction: column;
                gap: 1rem;
            }

            .main-image {
                height: 300px;
            }

            .stats-cards {
                grid-template-columns: 1fr;
            }

            .detail-grid {
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

            .product-title h2 {
                font-size: 1.25rem;
            }

            .product-overview,
            .recent-orders {
                padding: 1.25rem;
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
                <p>Informations complètes et statistiques</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="products.php" class="btn-modern btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Retour aux produits
            </a>
            <a href="product_edit.php?id=<?php echo $product_id; ?>" class="btn-modern btn-primary">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <button type="button" class="btn-modern btn-danger" onclick="confirmDelete()">
                <i class="fas fa-trash"></i> Supprimer
            </button>
        </div>

        <!-- Product Overview -->
        <div class="product-overview">
            <div class="product-header">
                <div class="product-title">
                    <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                    <p class="product-reference">Référence: <?php echo htmlspecialchars($product['reference']); ?></p>
                </div>
                <div class="product-status <?php echo $product['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                    <i class="fas fa-circle"></i>
                    <?php echo $product['is_active'] ? 'Actif' : 'Inactif'; ?>
                </div>
            </div>

            <div class="product-grid">
                <!-- Image Gallery -->
                <div class="image-gallery">
                    <?php if (!empty($product_images)): ?>
                    <div class="main-image" id="mainImage">
                        <img src="<?php echo htmlspecialchars($product_images[0]['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             id="currentImage">
                    </div>
                    <div class="image-thumbnails">
                        <?php foreach($product_images as $index => $image): ?>
                        <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                             onclick="changeImage('<?php echo htmlspecialchars($image['image_url']); ?>', this)">
                            <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                                 alt="Image <?php echo $index + 1; ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="main-image" id="mainImage">
                        <div class="empty-state">
                            <i class="fas fa-image"></i>
                            <p>Aucune image disponible</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Product Details -->
                <div class="product-details">
                    <div class="detail-section">
                        <h3 class="section-title">
                            <i class="fas fa-info-circle"></i> Description
                        </h3>
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>

                    <div class="detail-section">
                        <h3 class="section-title">
                            <i class="fas fa-tag"></i> Caractéristiques
                        </h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Collection</span>
                                <span class="detail-value">
                                    <?php echo htmlspecialchars($product['collection_name']); ?> 
                                    (<?php echo htmlspecialchars($product['collection_season']); ?>)
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Composition</span>
                                <span class="detail-value <?php echo empty($product['fabric_composition']) ? 'empty' : ''; ?>">
                                    <?php echo !empty($product['fabric_composition']) ? htmlspecialchars($product['fabric_composition']) : 'Non spécifiée'; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Poids</span>
                                <span class="detail-value <?php echo empty($product['weight_oz']) ? 'empty' : ''; ?>">
                                    <?php echo !empty($product['weight_oz']) ? htmlspecialchars($product['weight_oz']) . ' oz' : 'Non spécifié'; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Certifications</span>
                                <div class="tags-container">
                                    <?php if (!empty($product['certifications'])): 
                                        $certs = explode(',', $product['certifications']);
                                        foreach($certs as $cert):
                                            if (trim($cert)): ?>
                                    <span class="tag"><?php echo trim($cert); ?></span>
                                    <?php endif; endforeach; else: ?>
                                    <span class="detail-value empty">Aucune certification</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h3 class="section-title">
                            <i class="fas fa-palette"></i> Options disponibles
                        </h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Tailles</span>
                                <div class="tags-container">
                                    <?php if (!empty($product['available_sizes'])): 
                                        $sizes = explode(',', $product['available_sizes']);
                                        foreach($sizes as $size):
                                            if (trim($size)): ?>
                                    <span class="tag"><?php echo trim($size); ?></span>
                                    <?php endif; endforeach; else: ?>
                                    <span class="detail-value empty">Non spécifiées</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Couleurs</span>
                                <div class="tags-container">
                                    <?php if (!empty($product['available_colors'])): 
                                        $colors = explode(',', $product['available_colors']);
                                        foreach($colors as $color):
                                            if (trim($color)): ?>
                                    <span class="tag"><?php echo trim($color); ?></span>
                                    <?php endif; endforeach; else: ?>
                                    <span class="detail-value empty">Non spécifiées</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Lavages</span>
                                <div class="tags-container">
                                    <?php if (!empty($product['wash_types'])): 
                                        $washes = explode(',', $product['wash_types']);
                                        foreach($washes as $wash):
                                            if (trim($wash)): ?>
                                    <span class="tag"><?php echo trim($wash); ?></span>
                                    <?php endif; endforeach; else: ?>
                                    <span class="detail-value empty">Non spécifiés</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-value"><?php echo $product['moq']; ?></div>
                    <div class="stat-label">MOQ</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value"><?php echo $product['production_time_days']; ?></div>
                    <div class="stat-label">Jours de production</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-value"><?php echo $order_stats['order_count'] ?? 0; ?></div>
                    <div class="stat-label">Commandes</div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="recent-orders">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-history"></i> Commandes récentes
                </div>
                <a href="orders.php?product=<?php echo $product_id; ?>" class="btn-modern btn-outline-secondary">
                    Voir tout
                </a>
            </div>
            
            <?php if (!empty($recent_orders)): ?>
            <div class="table-wrapper">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Commande</th>
                            <th>Client</th>
                            <th>Quantité</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_orders as $order): ?>
                        <tr>
                            <td>
                                <a href="order_view.php?id=<?php echo $order['id']; ?>">
                                    <?php echo htmlspecialchars($order['order_ref']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars(substr($order['company_name'], 0, 25)); ?></td>
                            <td><?php echo $order['total_quantity']; ?> unités</td>
                            <td>
                                <span class="status-badge badge-<?php echo $order['status']; ?>">
                                    <?php 
                                        $status_labels = [
                                            'received' => 'Reçue',
                                            'production' => 'Production',
                                            'shipped' => 'Expédiée',
                                            'completed' => 'Terminée',
                                            'cancelled' => 'Annulée'
                                        ];
                                        echo $status_labels[$order['status']] ?? ucfirst($order['status']);
                                    ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <p>Aucune commande pour ce produit</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>
                <i class="fas fa-shield-alt" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Détails produit • <?php echo htmlspecialchars($product['reference']); ?>
            </div>
            <div>
                <span class="system-status">
                    <i class="fas fa-circle"></i> Consultation
                </span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Changement d'image dans la galerie
    function changeImage(imageUrl, element) {
        // Mettre à jour l'image principale
        document.getElementById('currentImage').src = imageUrl;
        
        // Mettre à jour les thumbnails actifs
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.classList.remove('active');
        });
        element.classList.add('active');
    }

    // Confirmation de suppression
    function confirmDelete() {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ? Cette action est irréversible.')) {
            window.location.href = 'product_delete.php?id=<?php echo $product_id; ?>';
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

    // Zoom sur les images au clic
    document.getElementById('currentImage').addEventListener('click', function() {
        const src = this.src;
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            cursor: zoom-out;
        `;
        
        const img = document.createElement('img');
        img.src = src;
        img.style.cssText = `
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        `;
        
        modal.appendChild(img);
        modal.addEventListener('click', () => modal.remove());
        document.body.appendChild(modal);
    });
    </script>
</body>
</html>