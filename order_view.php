<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$query = "
    SELECT o.*, u.company_name, u.contact_person, u.email, u.phone, u.country
    FROM orders o 
    JOIN users u ON o.client_id = u.id 
    WHERE o.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: order.php');
    exit();
}

$query = "
    SELECT oi.*, p.name as product_name, p.reference as product_reference, 
           p.weight_oz, p.moq, pi.image_url
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

$notes = [];
$table_check = $conn->query("SHOW TABLES LIKE 'order_notes'");
if ($table_check->num_rows > 0) {
    $query = "SELECT * FROM order_notes WHERE order_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $notes_result = $stmt->get_result();
    while ($row = $notes_result->fetch_assoc()) {
        $notes[] = $row;
    }
    $stmt->close();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $admin_notes = trim($_POST['admin_notes'] ?? '');
   
    $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        if (!empty($admin_notes)) {
            $table_check = $conn->query("SHOW TABLES LIKE 'order_notes'");
            if ($table_check->num_rows == 0) {
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
            }
            
            $stmt2 = $conn->prepare("INSERT INTO order_notes (order_id, note, added_by) VALUES (?, ?, 'admin')");
            if ($stmt2) {
                $stmt2->bind_param("is", $order_id, $admin_notes);
                $stmt2->execute();
                $stmt2->close();
            }
        }
        
        header('Location: order_view.php?id=' . $order_id . '&updated=1');
        exit();
    }
    $stmt->close();
}

$total_items = array_reduce($items, function($carry, $item) {
    return $carry + ($item['quantity'] ?? 0);
}, 0);

$total_value = array_reduce($items, function($carry, $item) {
    return $carry + ($item['subtotal'] ?? 0);
}, 0);

if ($total_value == 0 && isset($order['total_value'])) {
    $total_value = $order['total_value'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande <?php echo htmlspecialchars($order['reference']); ?> - FUS Denim</title>
    
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

        /* Order Header Card */
        .order-header-card {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            margin-bottom: 2rem;
        }

        .order-ref-large {
            font-family: 'Inter', monospace;
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--primary);
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        /* Order Grid */
        .order-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 992px) {
            .order-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Order Card */
        .order-card {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            margin-bottom: 2rem;
        }

        .order-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-100);
        }

        .order-card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .order-card-title i {
            color: var(--accent-1);
        }

        /* Status Badge */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .badge-received {
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent-5);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .badge-validating {
            background: rgba(13, 202, 240, 0.1);
            color: #0c6b7e;
            border: 1px solid rgba(13, 202, 240, 0.2);
        }

        .badge-confirmed {
            background: rgba(25, 135, 84, 0.1);
            color: #146c43;
            border: 1px solid rgba(25, 135, 84, 0.2);
        }

        .badge-production {
            background: rgba(108, 117, 125, 0.1);
            color: #4a5158;
            border: 1px solid rgba(108, 117, 125, 0.2);
        }

        .badge-shipped {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .items-table th {
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

        .items-table td {
            padding: 1rem;
            color: var(--gray-700);
            vertical-align: middle;
            border-bottom: 1px solid var(--gray-100);
        }

        .items-table tr:hover {
            background: var(--gray-50);
        }

        .product-image-small {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            background: var(--gray-100);
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .info-item {
            background: var(--gray-50);
            border-radius: 10px;
            padding: 1rem;
        }

        .info-label {
            font-size: 0.85rem;
            color: var(--gray-500);
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary);
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 2rem;
            margin-top: 1rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--gray-200);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-100);
        }

        .timeline-item:last-child {
            margin-bottom: 0;
            border-bottom: none;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--accent-1);
            border: 2px solid var(--white);
            box-shadow: 0 0 0 3px var(--accent-1);
        }

        .timeline-item.completed::before {
            background: var(--accent-4);
            box-shadow: 0 0 0 3px var(--accent-4);
        }

        .timeline-date {
            font-size: 0.85rem;
            color: var(--gray-500);
            margin-bottom: 0.25rem;
        }

        .timeline-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .timeline-description {
            font-size: 0.9rem;
            color: var(--gray-600);
        }

        /* Notes */
        .note-card {
            background: var(--gray-50);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--accent-1);
        }

        .note-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .note-author {
            font-weight: 600;
            color: var(--accent-1);
            font-size: 0.9rem;
        }

        .note-date {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .note-content {
            color: var(--gray-700);
            font-size: 0.9rem;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

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
            cursor: pointer;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
            color: var(--white);
            text-decoration: none;
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
            text-decoration: none;
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

            .order-grid {
                gap: 1rem;
            }

            .order-card {
                padding: 1.5rem;
            }

            .items-table {
                font-size: 0.875rem;
            }

            .items-table th,
            .items-table td {
                padding: 0.75rem;
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

            .order-ref-large {
                font-size: 1.25rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-modern, .btn-outline-modern {
                width: 100%;
                justify-content: center;
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
                <h1>Commande #<?php echo htmlspecialchars($order['reference']); ?></h1>
                <p>Détails complets de la commande client</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
                <a href="order.php" class="btn-outline-modern">
                    <i class="fas fa-arrow-left me-2"></i>Retour aux commandes
                </a>
            </div>
        </div>

        <!-- Alert Message -->
        <?php if (isset($_GET['updated'])): ?>
        <div class="alert-modern alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle me-2"></i>
                <span>Commande mise à jour avec succès</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Order Header -->
        <div class="order-header-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="order-ref-large"><?php echo htmlspecialchars($order['reference']); ?></div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="status-badge badge-<?php echo $order['status']; ?>">
                            <?php 
                            $status_labels = [
                                'received' => 'Reçue',
                                'validating' => 'En validation',
                                'confirmed' => 'Confirmée',
                                'production' => 'En production',
                                'shipped' => 'Expédiée'
                            ];
                            echo $status_labels[$order['status']];
                            ?>
                        </span>
                        <span class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            Créée le <?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?>
                        </span>
                    </div>
                </div>
                <div class="text-end">
                    <div class="text-muted mb-1">Valeur totale</div>
                    <div class="h3 fw-bold text-primary">
                        <?php echo number_format($total_value, 2, ',', ' '); ?> €
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Details Grid -->
        <div class="order-grid">
            <!-- Left Column: Items and Client Info -->
            <div>
                <!-- Items Card -->
                <div class="order-card">
                    <div class="order-card-header">
                        <div class="order-card-title">
                            <i class="fas fa-boxes"></i>
                            Articles de la commande
                            <span class="ms-2 text-muted" style="font-size: 0.9rem; font-weight: 400;">
                                (<?php echo $total_items; ?> pièce<?php echo $total_items > 1 ? 's' : ''; ?>)
                            </span>
                        </div>
                    </div>
                    
                    <?php if (empty($items)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-shopping-cart fa-2x text-muted mb-3"></i>
                        <p class="text-muted">Aucun article dans cette commande</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th width="60"></th>
                                    <th>Produit</th>
                                    <th>Référence</th>
                                    <th>Couleur</th>
                                    <th>Taille</th>
                                    <th>Quantité</th>
                                    <th>Prix unitaire</th>
                                    <th>Sous-total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($item['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                 class="product-image-small">
                                        <?php else: ?>
                                            <div class="product-image-small d-flex align-items-center justify-content-center">
                                                <i class="fas fa-tshirt text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        <small class="text-muted">MOQ: <?php echo $item['moq']; ?> • <?php echo $item['weight_oz']; ?> oz</small>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($item['product_reference']); ?></code>
                                    </td>
                                    <td><?php echo !empty($item['color']) ? htmlspecialchars($item['color']) : '<span class="text-muted">—</span>'; ?></td>
                                    <td><?php echo !empty($item['size']) ? htmlspecialchars($item['size']) : '<span class="text-muted">—</span>'; ?></td>
                                    <td>
                                        <span class="badge bg-primary rounded-pill"><?php echo $item['quantity']; ?></span>
                                    </td>
                                    <td><?php echo number_format($item['unit_price'], 2, ',', ' '); ?> €</td>
                                    <td class="fw-bold"><?php echo number_format($item['subtotal'], 2, ',', ' '); ?> €</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7" class="text-end fw-bold">Total commande:</td>
                                    <td class="fw-bold text-primary"><?php echo number_format($total_value, 2, ',', ' '); ?> €</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Client Info Card -->
                <div class="order-card">
                    <div class="order-card-header">
                        <div class="order-card-title">
                            <i class="fas fa-user-tie"></i>
                            Informations client
                        </div>
                        <a href="clients.php?id=<?php echo $order['client_id']; ?>" class="btn-outline-modern btn-sm">
                            <i class="fas fa-external-link-alt"></i> Profil
                        </a>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Société</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['company_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Contact</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['contact_person']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value">
                                <a href="mailto:<?php echo htmlspecialchars($order['email']); ?>">
                                    <?php echo htmlspecialchars($order['email']); ?>
                                </a>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Téléphone</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['phone']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Pays</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['country']); ?></div>
                        </div>
                        <?php if (!empty($order['shipping_address'])): ?>
                        <div class="info-item" style="grid-column: span 2;">
                            <div class="info-label">Adresse de livraison</div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Status, Timeline, Notes -->
            <div>
                <!-- Status Update Card -->
                <div class="order-card">
                    <div class="order-card-header">
                        <div class="order-card-title">
                            <i class="fas fa-sync-alt"></i>
                            Mettre à jour le statut
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold mb-2">Statut actuel</label>
                            <select name="status" class="form-select form-select-lg" required>
                                <option value="received" <?php echo $order['status'] == 'received' ? 'selected' : ''; ?>>Reçue</option>
                                <option value="validating" <?php echo $order['status'] == 'validating' ? 'selected' : ''; ?>>En validation</option>
                                <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmée</option>
                                <option value="production" <?php echo $order['status'] == 'production' ? 'selected' : ''; ?>>En production</option>
                                <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Expédiée</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold mb-2">Ajouter une note</label>
                            <textarea name="admin_notes" class="form-control" rows="3" 
                                      placeholder="Ajoutez une note interne sur cette commande..."></textarea>
                            <div class="form-text">Cette note sera visible uniquement par les administrateurs</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="update_status" class="btn-modern">
                                <i class="fas fa-save me-2"></i>Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Timeline Card -->
                <div class="order-card">
                    <div class="order-card-header">
                        <div class="order-card-title">
                            <i class="fas fa-history"></i>
                            Historique du statut
                        </div>
                    </div>
                    
                    <div class="timeline">
                        <?php
                        $status_order = ['received', 'validating', 'confirmed', 'production', 'shipped'];
                        $current_status_index = array_search($order['status'], $status_order);
                        
                        foreach ($status_order as $index => $status):
                            $is_completed = $index <= $current_status_index;
                            $status_label = [
                                'received' => 'Commande reçue',
                                'validating' => 'Validation en cours',
                                'confirmed' => 'Commande confirmée',
                                'production' => 'Production démarrée',
                                'shipped' => 'Commande expédiée'
                            ][$status];
                        ?>
                        <div class="timeline-item <?php echo $is_completed ? 'completed' : ''; ?>">
                            <div class="timeline-date">
                                <?php 
                                if ($is_completed) {
                                    echo date('d/m/Y H:i', strtotime($order['created_at']));
                                } else {
                                    echo 'En attente';
                                }
                                ?>
                            </div>
                            <div class="timeline-title"><?php echo $status_label; ?></div>
                            <div class="timeline-description">
                                <?php 
                                if ($is_completed && $status == $order['status']) {
                                    echo 'Statut actuel de la commande';
                                } elseif ($is_completed) {
                                    echo 'Étape terminée';
                                } else {
                                    echo 'Étape à venir';
                                }
                                ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Notes Card -->
                <div class="order-card">
                    <div class="order-card-header">
                        <div class="order-card-title">
                            <i class="fas fa-comment-dots"></i>
                            Notes internes
                        </div>
                    </div>
                    
                    <?php if (empty($notes)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-comment-slash fa-2x text-muted mb-3"></i>
                        <p class="text-muted">Aucune note pour cette commande</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($notes as $note): ?>
                        <div class="note-card">
                            <div class="note-header">
                                <div class="note-author">
                                    <i class="fas fa-user-circle me-1"></i>
                                    <?php echo htmlspecialchars($note['added_by']); ?>
                                </div>
                                <div class="note-date">
                                    <?php echo date('d/m/Y H:i', strtotime($note['created_at'])); ?>
                                </div>
                            </div>
                            <div class="note-content">
                                <?php echo nl2br(htmlspecialchars($note['note'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="order_edit.php?id=<?php echo $order_id; ?>" class="btn-modern">
                <i class="fas fa-edit me-2"></i>Modifier la commande
            </a>
            <a href="order.php" class="btn-outline-modern">
                <i class="fas fa-arrow-left me-2"></i>Retour aux commandes
            </a>
           
           
        </div>

        <!-- Footer -->
        <div class="footer mt-4">
            <div>
                <i class="fas fa-shield-alt" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Détails de commande
            </div>
            <div>
                <span class="system-status">
                    <i class="fas fa-circle"></i> Dernière mise à jour: <?php echo date('d/m/Y H:i', strtotime($order['updated_at'])); ?>
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

        // Auto-refresh toutes les 2 minutes pour les mises à jour
        setTimeout(function() {
            location.reload();
        }, 120000);

        // Confirmation avant changement de statut
        document.querySelector('select[name="status"]').addEventListener('change', function() {
            const newStatus = this.options[this.selectedIndex].text;
            if (!confirm(`Êtes-vous sûr de vouloir changer le statut en "${newStatus}" ?`)) {
                this.value = '<?php echo $order["status"]; ?>';
            }
        });

        // Impression améliorée
        function printOrder() {
            const printContent = document.querySelector('.main-content').innerHTML;
            const originalContent = document.body.innerHTML;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>FUS Denim - Commande <?php echo htmlspecialchars($order['reference']); ?></title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { padding: 20px; font-family: 'Inter', sans-serif; }
                        .print-header { margin-bottom: 30px; text-align: center; border-bottom: 2px solid #3B82F6; padding-bottom: 20px; }
                        .print-header h1 { color: #3B82F6; font-weight: 700; }
                        .print-section { margin-bottom: 30px; page-break-inside: avoid; }
                        .print-table { font-size: 12px; width: 100%; }
                        .print-table th { background: #f8f9fa; padding: 8px; }
                        .print-table td { padding: 8px; border-bottom: 1px solid #dee2e6; }
                        @media print {
                            .no-print { display: none; }
                            .print-break { page-break-before: always; }
                        }
                    </style>
                </head>
                <body>
                    <div class="print-header">
                        <h1>FUS Denim - Commande <?php echo htmlspecialchars($order['reference']); ?></h1>
                        <p>Imprimé le <?php echo date('d/m/Y H:i'); ?></p>
                        <p>Statut: <?php echo $status_labels[$order['status']]; ?></p>
                    </div>
                    ${printContent}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => printWindow.print(), 500);
        }
    </script>
</body>
</html>