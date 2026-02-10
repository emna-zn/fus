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

$clients_result = $conn->query("
    SELECT id, company_name, contact_person, email 
    FROM users 
    WHERE role = 'client' AND is_active = 1 
    ORDER BY company_name
");
$clients = [];
if ($clients_result) {
    while($row = $clients_result->fetch_assoc()) {
        $clients[] = $row;
    }
}

$products_result = $conn->query("
    SELECT p.id, p.reference, p.name, p.unit_price, p.moq,
           c.name as collection_name
    FROM products p
    LEFT JOIN collections c ON p.collection_id = c.id
    WHERE p.is_active = 1
    ORDER BY p.collection_id, p.reference
");
$products = [];
if ($products_result) {
    while($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $client_id = intval($_POST['client_id']);
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $estimated_delivery = $_POST['estimated_delivery'] ?? null;
    $order_notes = trim($_POST['order_notes'] ?? '');
    
    $reference = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    
    $total_items = 0;
    $total_value = 0.00;
    $order_items = [];
    
    if (isset($_POST['product_id']) && is_array($_POST['product_id'])) {
        foreach ($_POST['product_id'] as $index => $product_id) {
            $product_id = intval($product_id);
            $quantity = intval($_POST['quantity'][$index] ?? 0);
            $color = trim($_POST['color'][$index] ?? '');
            $size = trim($_POST['size'][$index] ?? '');
            $wash_type = trim($_POST['wash_type'][$index] ?? '');
            $item_notes = trim($_POST['item_notes'][$index] ?? '');
            
            if ($product_id > 0 && $quantity > 0) {
                $price_stmt = $conn->prepare("SELECT unit_price FROM products WHERE id = ?");
                $price_stmt->bind_param("i", $product_id);
                $price_stmt->execute();
                $price_result = $price_stmt->get_result();
                $product = $price_result->fetch_assoc();
                $price_stmt->close();
                
                $unit_price = $product['unit_price'] ?? 0.00;
                $subtotal = $unit_price * $quantity;
                
                $order_items[] = [
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                    'color' => $color,
                    'size' => $size,
                    'wash_type' => $wash_type,
                    'unit_price' => $unit_price,
                    'subtotal' => $subtotal,
                    'notes' => $item_notes
                ];
                
                $total_items += $quantity;
                $total_value += $subtotal;
            }
        }
    }
    
    if (empty($order_items)) {
        $message = "Veuillez ajouter au moins un article à la commande.";
        $message_type = 'danger';
    } elseif ($client_id <= 0) {
        $message = "Veuillez sélectionner un client.";
        $message_type = 'danger';
    } else {
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO orders (client_id, reference, status, total_items, total_value, 
                                  shipping_address, estimated_delivery, notes, created_at, updated_at)
                VALUES (?, ?, 'received', ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->bind_param(
                "isddsss",
                $client_id,
                $reference,
                $total_items,
                $total_value,
                $shipping_address,
                $estimated_delivery,
                $order_notes
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Erreur lors de la création de la commande.");
            }
            
            $order_id = $stmt->insert_id;
            $stmt->close();
            
            $item_stmt = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, color, size, wash_type, 
                                       quantity, unit_price, subtotal, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($order_items as $item) {
                $item_stmt->bind_param(
                    "iisssidds",
                    $order_id,
                    $item['product_id'],
                    $item['color'],
                    $item['size'],
                    $item['wash_type'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['subtotal'],
                    $item['notes']
                );
                
                if (!$item_stmt->execute()) {
                    throw new Exception("Erreur lors de l'ajout des articles.");
                }
            }
            $item_stmt->close();
            
            $conn->commit();
            
            $message = "Commande créée avec succès ! Référence : " . $reference;
            $message_type = 'success';
            
            header("Refresh: 3; url=order_view.php?id=" . $order_id);
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Erreur : " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Commande - Tableau de bord Admin - FUS Denim</title>
    
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

        /* Form Styles */
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--gray-100);
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-1);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .required::after {
            content: " *";
            color: #EF4444;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }

        .items-table thead th {
            background: var(--gray-50);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--gray-600);
            font-size: 0.85rem;
            text-transform: uppercase;
            border-bottom: 2px solid var(--gray-200);
        }

        .items-table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--gray-100);
        }

        .items-table tbody tr:hover {
            background: var(--gray-50);
        }

        /* Button Groups */
        .btn-group-modern {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-100);
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

        /* Alert */
        .alert-modern {
            border-radius: 12px;
            border: 1px solid;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            animation: slideInUp 0.5s ease-out;
        }

        /* Summary Box */
        .summary-box {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
            border: 1px solid var(--gray-200);
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .summary-item:last-child {
            border-bottom: none;
            font-weight: 700;
            color: var(--primary);
            font-size: 1.1rem;
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

            .header-actions {
                width: 100%;
            }

            .card-modern {
                padding: 1.25rem;
            }

            .items-table {
                display: block;
                overflow-x: auto;
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
                <h1>Nouvelle Commande</h1>
                <p>Créez une nouvelle commande pour un client</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
                <a href="order.php" class="btn-outline-modern">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>

        <!-- Alert Message -->
        <?php if ($message): ?>
        <div class="alert-modern alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                <span><?php echo $message; ?></span>
                <?php if ($message_type != 'success'): ?>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Order Creation Form -->
        <form method="POST" action="">
            <div class="card-modern">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-file-invoice"></i> Informations de la commande
                    </div>
                    <div class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Tous les champs marqués d'un * sont obligatoires
                    </div>
                </div>

                <!-- Client Information -->
                <div class="form-section">
                    <h5 class="mb-3"><i class="fas fa-user-tie me-2"></i>Client</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Client</label>
                            <select name="client_id" class="form-select" required>
                                <option value="">Sélectionner un client...</option>
                                <?php foreach($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>">
                                    <?php echo htmlspecialchars($client['company_name']); ?> - 
                                    <?php echo htmlspecialchars($client['contact_person']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Shipping Information -->
                <div class="form-section">
                    <h5 class="mb-3"><i class="fas fa-truck me-2"></i>Livraison</h5>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Adresse de livraison</label>
                            <textarea name="shipping_address" class="form-control" rows="3" 
                                      placeholder="Adresse complète de livraison..."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date de livraison estimée</label>
                            <input type="date" name="estimated_delivery" class="form-control" 
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="form-section">
                    <h5 class="mb-3"><i class="fas fa-boxes me-2"></i>Articles de la commande</h5>
                    
                    <div id="items-container">
                        <!-- First item row -->
                        <div class="item-row mb-3 p-3 border rounded">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label required">Produit</label>
                                    <select name="product_id[]" class="form-select product-select" required>
                                        <option value="">Sélectionner un produit...</option>
                                        <?php foreach($products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>" 
                                                data-price="<?php echo $product['unit_price']; ?>"
                                                data-moq="<?php echo $product['moq']; ?>">
                                            <?php echo htmlspecialchars($product['reference']); ?> - 
                                            <?php echo htmlspecialchars($product['name']); ?> 
                                            (<?php echo htmlspecialchars($product['collection_name']); ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label required">Quantité</label>
                                    <input type="number" name="quantity[]" class="form-control quantity-input" 
                                           min="1" value="1" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Couleur</label>
                                    <input type="text" name="color[]" class="form-control" 
                                           placeholder="Indigo">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Taille</label>
                                    <input type="text" name="size[]" class="form-control" 
                                           placeholder="M">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Type de lavage</label>
                                    <input type="text" name="wash_type[]" class="form-control" 
                                           placeholder="Stone Wash">
                                </div>
                                <div class="col-md-12 mt-2">
                                    <label class="form-label">Notes sur l'article</label>
                                    <input type="text" name="item_notes[]" class="form-control" 
                                           placeholder="Notes spécifiques...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="button" class="btn-outline-modern" id="add-item">
                            <i class="fas fa-plus"></i> Ajouter un article
                        </button>
                        <button type="button" class="btn-outline-modern" id="remove-item">
                            <i class="fas fa-minus"></i> Supprimer le dernier
                        </button>
                    </div>

                    <!-- Summary -->
                    <div class="summary-box">
                        <h6 class="mb-3"><i class="fas fa-calculator me-2"></i>Résumé</h6>
                        <div class="summary-item">
                            <span>Nombre d'articles :</span>
                            <span id="total-items">0</span>
                        </div>
                        <div class="summary-item">
                            <span>Total quantité :</span>
                            <span id="total-quantity">0</span>
                        </div>
                        <div class="summary-item">
                            <span>Valeur totale :</span>
                            <span id="total-value">0.00 €</span>
                        </div>
                    </div>
                </div>

                <!-- Order Notes -->
                <div class="form-section">
                    <h5 class="mb-3"><i class="fas fa-sticky-note me-2"></i>Notes</h5>
                    <div class="row">
                        <div class="col-12">
                            <label class="form-label">Notes internes sur la commande</label>
                            <textarea name="order_notes" class="form-control" rows="4" 
                                      placeholder="Notes internes pour les administrateurs..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="btn-group-modern">
                    <button type="submit" name="create_order" value="1" class="btn-modern">
                        <i class="fas fa-check-circle"></i> Créer la commande
                    </button>
                    <a href="order.php" class="btn-outline-modern">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </div>
        </form>

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

        // Gestion des articles
        document.addEventListener('DOMContentLoaded', function() {
            const itemsContainer = document.getElementById('items-container');
            const addItemBtn = document.getElementById('add-item');
            const removeItemBtn = document.getElementById('remove-item');
            const totalItemsSpan = document.getElementById('total-items');
            const totalQuantitySpan = document.getElementById('total-quantity');
            const totalValueSpan = document.getElementById('total-value');

            // Fonction pour calculer le résumé
            function updateSummary() {
                const productSelects = document.querySelectorAll('.product-select');
                const quantityInputs = document.querySelectorAll('.quantity-input');
                
                let totalItems = 0;
                let totalQuantity = 0;
                let totalValue = 0;
                
                productSelects.forEach((select, index) => {
                    const productId = select.value;
                    const quantity = parseInt(quantityInputs[index]?.value) || 0;
                    
                    if (productId && quantity > 0) {
                        const price = parseFloat(select.options[select.selectedIndex]?.dataset.price) || 0;
                        totalItems++;
                        totalQuantity += quantity;
                        totalValue += price * quantity;
                    }
                });
                
                totalItemsSpan.textContent = totalItems;
                totalQuantitySpan.textContent = totalQuantity;
                totalValueSpan.textContent = totalValue.toFixed(2) + ' €';
            }

            // Ajouter un article
            addItemBtn.addEventListener('click', function() {
                const firstRow = itemsContainer.querySelector('.item-row');
                const newRow = firstRow.cloneNode(true);
                
                // Réinitialiser les valeurs
                newRow.querySelector('.product-select').selectedIndex = 0;
                newRow.querySelector('.quantity-input').value = 1;
                newRow.querySelectorAll('input[type="text"]').forEach(input => input.value = '');
                
                itemsContainer.appendChild(newRow);
                
                // Ajouter des écouteurs d'événements
                newRow.querySelector('.product-select').addEventListener('change', updateSummary);
                newRow.querySelector('.quantity-input').addEventListener('input', updateSummary);
                
                updateSummary();
            });

            // Supprimer le dernier article
            removeItemBtn.addEventListener('click', function() {
                const rows = itemsContainer.querySelectorAll('.item-row');
                if (rows.length > 1) {
                    rows[rows.length - 1].remove();
                    updateSummary();
                }
            });

            // Initialiser les écouteurs d'événements
            document.querySelectorAll('.product-select').forEach(select => {
                select.addEventListener('change', updateSummary);
            });
            
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.addEventListener('input', updateSummary);
            });

            // Mettre à jour le résumé initial
            updateSummary();

            // Validation de la quantité minimale
            itemsContainer.addEventListener('change', function(e) {
                if (e.target.classList.contains('product-select')) {
                    const row = e.target.closest('.item-row');
                    const quantityInput = row.querySelector('.quantity-input');
                    const moq = parseInt(e.target.options[e.target.selectedIndex]?.dataset.moq) || 1;
                    
                    if (quantityInput.value < moq) {
                        quantityInput.value = moq;
                        alert(`La quantité minimale pour ce produit est de ${moq} unités.`);
                    }
                    updateSummary();
                }
            });
        });

        // Validation du formulaire
        document.querySelector('form').addEventListener('submit', function(e) {
            const clientSelect = document.querySelector('select[name="client_id"]');
            const productSelects = document.querySelectorAll('.product-select');
            
            if (!clientSelect.value) {
                e.preventDefault();
                alert('Veuillez sélectionner un client.');
                clientSelect.focus();
                return;
            }
            
            let hasProduct = false;
            productSelects.forEach(select => {
                if (select.value) hasProduct = true;
            });
            
            if (!hasProduct) {
                e.preventDefault();
                alert('Veuillez ajouter au moins un article à la commande.');
                return;
            }
        });
    </script>
</body>
</html>