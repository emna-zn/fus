<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$client_id = $_SESSION['user_id'];

$collection_filter = $_GET['collection'] ?? '';
$search_query = $_GET['search'] ?? '';

$query = "SELECT p.*, c.name as collection_name, c.season 
          FROM products p 
          LEFT JOIN collections c ON p.collection_id = c.id 
          WHERE p.is_active = 1";

$params = [];
$types = "";

if ($collection_filter) {
    $query .= " AND p.collection_id = ?";
    $params[] = $collection_filter;
    $types .= "i";
}

if ($search_query) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.reference LIKE ?)";
    $search_param = "%" . $search_query . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$products_result = $stmt->get_result();
$products_count = $products_result->num_rows;
$collections_result = $conn->query("SELECT id, name, season FROM collections WHERE is_public = 1 ORDER BY season DESC");
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
            margin-bottom: 2rem;
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
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.2);
        }

        .btn-outline-secondary {
            border: 1px solid var(--gray-300);
            color: var(--gray-600);
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        /* Filter Sidebar */
        .filter-sidebar {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            margin-bottom: 1.5rem;
        }

        .filter-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-title i {
            color: var(--accent-1);
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid var(--gray-300);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-1);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Stats Card */
        .stats-card {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            margin-top: 1.5rem;
        }

        .stats-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stats-title i {
            color: var(--accent-1);
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .stat-value {
            color: var(--primary);
            font-weight: 700;
            font-size: 0.9rem;
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            background: var(--white);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--gray-200);
        }

        .product-image {
            height: 250px;
            overflow: hidden;
            position: relative;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-badges {
            position: absolute;
            top: 1rem;
            left: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-collection {
            background: var(--accent-1);
            color: white;
        }

        .badge-moq {
            background: var(--accent-5);
            color: white;
        }

        .product-content {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .product-reference {
            color: var(--gray-500);
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .product-description {
            color: var(--gray-600);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }

        .product-details {
            margin-top: auto;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            color: var(--gray-600);
        }

        .detail-item i {
            color: var(--accent-1);
            width: 16px;
            text-align: center;
        }

        .product-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .btn-view {
            flex: 1;
            padding: 0.75rem;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            color: var(--accent-1);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .btn-view:hover {
            background: var(--accent-1);
            color: white;
            text-decoration: none;
        }

        .btn-order {
            flex: 1;
            padding: 0.75rem;
            background: linear-gradient(135deg, var(--accent-4), var(--accent-1));
            border: none;
            color: white;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .btn-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.2);
            color: white;
            text-decoration: none;
        }

        /* Empty State */
        .empty-state {
            background: var(--white);
            border-radius: 16px;
            padding: 3rem 2rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }

        .empty-state h4 {
            color: var(--gray-600);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--gray-500);
            margin-bottom: 1.5rem;
        }

        /* Results Header */
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .results-count {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
        }

        .results-count i {
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
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 992px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
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

            .results-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .product-grid {
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

            .product-image {
                height: 200px;
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
                <h1>Catalogue produits</h1>
                <p>Découvrez notre collection de denim premium</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
                <a href="dashboard_client.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Filtres -->
            <div class="col-lg-3">
                <div class="filter-sidebar">
                    <h5 class="filter-title">
                        <i class="fas fa-filter"></i> Filtres
                    </h5>
                    
                    <form method="GET" action="">
                        <!-- Recherche -->
                        <div class="mb-3">
                            <label class="form-label">Recherche</label>
                            <input type="text" class="form-control" name="search" 
                                   value="<?php echo htmlspecialchars($search_query); ?>" 
                                   placeholder="Nom, référence, description...">
                        </div>
                        
                        <!-- Filtre par collection -->
                        <div class="mb-3">
                            <label class="form-label">Collection</label>
                            <select class="form-select" name="collection">
                                <option value="">Toutes les collections</option>
                                <?php while ($collection = $collections_result->fetch_assoc()): ?>
                                    <option value="<?php echo $collection['id']; ?>" 
                                        <?php echo ($collection_filter == $collection['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($collection['name'] . ' - ' . $collection['season']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <!-- Boutons -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Appliquer les filtres
                            </button>
                            <a href="catalog.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Effacer les filtres
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Statistiques -->
                <div class="stats-card">
                    <h6 class="stats-title">
                        <i class="fas fa-chart-bar"></i> Statistiques catalogue
                    </h6>
                    <div class="stat-item">
                        <span class="stat-label">Produits</span>
                        <span class="stat-value"><?php echo $products_count; ?></span>
                    </div>
                    <?php
                    $col_count = $conn->query("SELECT COUNT(*) as count FROM collections WHERE is_public = 1")->fetch_assoc();
                    ?>
                    <div class="stat-item">
                        <span class="stat-label">Collections</span>
                        <span class="stat-value"><?php echo $col_count['count']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Filtres actifs</span>
                        <span class="stat-value">
                            <?php echo ($collection_filter || $search_query) ? 'Oui' : 'Non'; ?>
                        </span>
                    </div>
                </div>

                <!-- Filtres rapides -->
                <div class="stats-card">
                    <h6 class="stats-title">
                        <i class="fas fa-bolt"></i> Filtres rapides
                    </h6>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="quickFilter('low-moq')">
                            <i class="fas fa-box me-2"></i>MOQ ≤ 100
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="quickFilter('fast-production')">
                            <i class="fas fa-clock me-2"></i>Production ≤ 30 jours
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="quickFilter('sustainable')">
                            <i class="fas fa-leaf me-2"></i>Éco-responsable
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Liste des produits -->
            <div class="col-lg-9">
                <!-- En-tête résultats -->
                <div class="results-header">
                    <div class="results-count">
                        <i class="fas fa-tshirt"></i>
                        <span><?php echo $products_count; ?> produits trouvés</span>
                    </div>
                    
                    <?php if ($collection_filter || $search_query): ?>
                    <div class="active-filters">
                        <small class="text-muted">
                            Filtres actifs: 
                            <?php if ($collection_filter): ?>
                                <span class="badge bg-primary me-1">Collection</span>
                            <?php endif; ?>
                            <?php if ($search_query): ?>
                                <span class="badge bg-primary">Recherche: "<?php echo htmlspecialchars($search_query); ?>"</span>
                            <?php endif; ?>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Grille de produits -->
                <?php if ($products_count > 0): ?>
                    <?php $products_result->data_seek(0); ?>
                    <div class="product-grid">
                        <?php while ($product = $products_result->fetch_assoc()): ?>
                            <?php
                            // Récupérer l'image principale
                            $img_query = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND is_main = 1 LIMIT 1");
                            $img_query->bind_param("i", $product['id']);
                            $img_query->execute();
                            $img_result = $img_query->get_result();
                            $image = $img_result->fetch_assoc();
                            ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="<?php echo $image ? htmlspecialchars($image['image_url']) : 'https://via.placeholder.com/400x250?text=Denim+Product'; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <div class="product-badges">
                                        <span class="badge badge-collection">
                                            <?php echo htmlspecialchars($product['collection_name']); ?>
                                        </span>
                                        <span class="badge badge-moq">
                                            MOQ: <?php echo $product['moq']; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="product-content">
                                    <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <div class="product-reference">
                                        Réf: <?php echo htmlspecialchars($product['reference']); ?>
                                    </div>
                                    
                                    <p class="product-description">
                                        <?php echo substr(htmlspecialchars($product['description']), 0, 120); ?>...
                                    </p>
                                    
                                    <div class="product-details">
                                        <div class="detail-item">
                                            <i class="fas fa-weight"></i>
                                            <span><strong>Poids:</strong> <?php echo htmlspecialchars($product['weight_oz']); ?> oz</span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-clock"></i>
                                            <span><strong>Production:</strong> <?php echo $product['production_time_days']; ?> jours</span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-palette"></i>
                                            <span><strong>Couleurs:</strong> <?php echo substr(htmlspecialchars($product['available_colors']), 0, 25); ?>...</span>
                                        </div>
                                    </div>
                                    
                                    <div class="product-actions">
                                        <a href="catalog_product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn-view">
                                            <i class="fas fa-eye me-1"></i>Détails
                                        </a>
                                        <a href="orders.php?action=new&product=<?php echo $product['id']; ?>" 
                                           class="btn-order">
                                            <i class="fas fa-cart-plus me-1"></i>Commander
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-tshirt"></i>
                        <h4>Aucun produit trouvé</h4>
                        <p>Essayez d'ajuster vos critères de recherche</p>
                        <a href="catalog.php" class="btn btn-primary">
                            <i class="fas fa-redo me-2"></i>Réinitialiser les filtres
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>
                <i class="fas fa-gem" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Catalogue produits
            </div>
            <div>
                <span class="system-status">
                    <i class="fas fa-circle"></i> <?php echo $products_count; ?> produits disponibles
                </span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filtres rapides
        function quickFilter(filterType) {
            let url = 'catalog.php?';
            
            switch(filterType) {
                case 'low-moq':
                    url += 'search=MOQ%3A100';
                    break;
                case 'fast-production':
                    url += 'search=production%3A30';
                    break;
                case 'sustainable':
                    url += 'search=organic%20ou%20écologique';
                    break;
                default:
                    url = 'catalog.php';
            }
            
            window.location.href = url;
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
        const currentPage = window.location.pathname.split('/').pop() || 'catalog.php';
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.getAttribute('href') === currentPage) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Animation des cartes produits au défilement
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        // Appliquer l'animation aux cartes produits
        document.querySelectorAll('.product-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>