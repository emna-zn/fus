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
$selected_product_id = $_GET['product'] ?? null;
$cart = $_SESSION['cart'] ?? [];
if ($selected_product_id && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $query = "SELECT p.*, c.name as collection_name 
              FROM products p 
              LEFT JOIN collections c ON p.collection_id = c.id 
              WHERE p.id = ? AND p.is_active = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $selected_product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if ($product) {
        $cart_item = [
            'product_id' => $product['id'],
            'reference' => $product['reference'],
            'name' => $product['name'],
            'moq' => $product['moq'],
            'collection' => $product['collection_name'],
            'colors' => explode(',', $product['available_colors']),
            'sizes' => explode(',', $product['available_sizes']),
            'wash_types' => explode(',', $product['wash_types']),
            'quantities' => []
        ];
        $cart[$product['id']] = $cart_item;
        $_SESSION['cart'] = $cart;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_to_cart':
            $search_product_id = $_POST['product_id'] ?? null;
            if ($search_product_id) {
                $query = "SELECT p.*, c.name as collection_name 
                          FROM products p 
                          LEFT JOIN collections c ON p.collection_id = c.id 
                          WHERE p.id = ? AND p.is_active = 1";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $search_product_id);
                $stmt->execute();
                $product = $stmt->get_result()->fetch_assoc();
                
                if ($product) {
                    $cart_item = [
                        'product_id' => $product['id'],
                        'reference' => $product['reference'],
                        'name' => $product['name'],
                        'moq' => $product['moq'],
                        'collection' => $product['collection_name'],
                        'colors' => explode(',', $product['available_colors']),
                        'sizes' => explode(',', $product['available_sizes']),
                        'wash_types' => explode(',', $product['wash_types']),
                        'quantities' => []
                    ];
                    $cart[$product['id']] = $cart_item;
                    $_SESSION['cart'] = $cart;
                    $success_message = "Product added to order!";
                }
            }
            break;
            
        case 'update_quantities':
            $update_product_id = $_POST['product_id'] ?? null;
            if ($update_product_id && isset($cart[$update_product_id])) {
                $quantities = $_POST['quantities'] ?? [];
                $colors = $_POST['colors'] ?? [];
                $wash_types = $_POST['wash_types'] ?? [];
                $notes = $_POST['product_notes'] ?? '';
                
                $cart[$update_product_id]['quantities'] = $quantities;
                $cart[$update_product_id]['selected_colors'] = $colors;
                $cart[$update_product_id]['selected_wash_types'] = $wash_types;
                $cart[$update_product_id]['notes'] = $notes;
                
                $_SESSION['cart'] = $cart;
                $success_message = "Quantities updated!";
            }
            break;
            
        case 'remove_item':
            $remove_product_id = $_POST['product_id'] ?? null;
            if ($remove_product_id && isset($cart[$remove_product_id])) {
                unset($cart[$remove_product_id]);
                $_SESSION['cart'] = $cart;
                $success_message = "Product removed from order!";
            }
            break;
            
        case 'save_draft':
            $order_notes = $_POST['order_notes'] ?? '';
            $shipping_address = $_POST['shipping_address'] ?? '';
            $requested_delivery = $_POST['requested_delivery'] ?? '';
            $total_items = 0;
            foreach ($cart as $item) {
                foreach ($item['quantities'] as $qty) {
                    $total_items += intval($qty);
                }
            }
            $reference = 'DRAFT-' . date('Ymd-His') . '-' . rand(100, 999);
            $query = "INSERT INTO orders (client_id, reference, status, total_items, notes, shipping_address, estimated_delivery, is_draft) 
                      VALUES (?, ?, 'draft', ?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isisss", $client_id, $reference, $total_items, $order_notes, $shipping_address, $requested_delivery);
            
            if ($stmt->execute()) {
                $order_id = $stmt->insert_id;
                foreach ($cart as $product_id => $item) {
                    foreach ($item['quantities'] as $index => $quantity) {
                        if ($quantity > 0) {
                            $color = $item['selected_colors'][$index] ?? '';
                            $size = $item['sizes'][$index] ?? '';
                            $wash_type = $item['selected_wash_types'][$index] ?? '';
                            
                            $item_query = "INSERT INTO order_items (order_id, product_id, color, size, wash_type, quantity, notes) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?)";
                            $item_stmt = $conn->prepare($item_query);
                            $item_stmt->bind_param("iisssis", $order_id, $product_id, $color, $size, $wash_type, $quantity, $item['notes']);
                            $item_stmt->execute();
                        }
                    }
                }
                
                $_SESSION['cart'] = [];
                $cart = [];
                
                $success_message = "Order saved as draft successfully! Draft reference: " . $reference;
            }
            break;
            
        case 'submit_order':
            $order_notes = $_POST['order_notes'] ?? '';
            $shipping_address = $_POST['shipping_address'] ?? '';
            $requested_delivery = $_POST['requested_delivery'] ?? '';
            $errors = [];
            foreach ($cart as $product_id => $item) {
                $total_qty = 0;
                foreach ($item['quantities'] as $qty) {
                    $total_qty += intval($qty);
                }
                
                if ($total_qty < $item['moq']) {
                    $errors[] = "Product '{$item['name']}': Minimum order quantity is {$item['moq']} units (you ordered $total_qty)";
                }
            }
            
            if (empty($errors)) {
                $total_items = 0;
                foreach ($cart as $item) {
                    foreach ($item['quantities'] as $qty) {
                        $total_items += intval($qty);
                    }
                }
                $reference = 'ORD-' . date('Ymd') . '-' . strtoupper(substr($_SESSION['company_name'], 0, 3)) . '-' . rand(1000, 9999);
                $query = "INSERT INTO orders (client_id, reference, status, total_items, notes, shipping_address, estimated_delivery) 
                          VALUES (?, ?, 'received', ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("isisss", $client_id, $reference, $total_items, $order_notes, $shipping_address, $requested_delivery);
                
                if ($stmt->execute()) {
                    $order_id = $stmt->insert_id;
                    foreach ($cart as $product_id => $item) {
                        foreach ($item['quantities'] as $index => $quantity) {
                            if ($quantity > 0) {
                                $color = $item['selected_colors'][$index] ?? '';
                                $size = $item['sizes'][$index] ?? '';
                                $wash_type = $item['selected_wash_types'][$index] ?? '';
                                
                                $item_query = "INSERT INTO order_items (order_id, product_id, color, size, wash_type, quantity, notes) 
                                               VALUES (?, ?, ?, ?, ?, ?, ?)";
                                $item_stmt = $conn->prepare($item_query);
                                $item_stmt->bind_param("iisssis", $order_id, $product_id, $color, $size, $wash_type, $quantity, $item['notes']);
                                $item_stmt->execute();
                            }
                        }
                    }
                    $_SESSION['cart'] = [];
                    $cart = [];
                    header("Location: order_confirmation.php?id=" . $order_id);
                    exit();
                }
            }
            break;
    }
}


$search_results = [];
if (isset($_GET['search_catalog'])) {
    $search_term = $_GET['search_term'] ?? '';
    $collection_filter = $_GET['collection_filter'] ?? '';
    
    $search_query = "SELECT p.id, p.reference, p.name, p.moq, c.name as collection_name 
                     FROM products p 
                     LEFT JOIN collections c ON p.collection_id = c.id 
                     WHERE p.is_active = 1";
    
    $params = [];
    $types = "";
    
    if ($search_term) {
        $search_query .= " AND (p.name LIKE ? OR p.reference LIKE ? OR p.description LIKE ?)";
        $search_param = "%" . $search_term . "%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sss";
    }
    
    if ($collection_filter) {
        $search_query .= " AND p.collection_id = ?";
        $params[] = $collection_filter;
        $types .= "i";
    }
    
    $search_query .= " LIMIT 20";
    
    $stmt = $conn->prepare($search_query);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $search_results = $stmt->get_result();
}
$collections_result = $conn->query("SELECT id, name FROM collections WHERE is_public = 1 ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order | FUS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-dark: #0a1931;
            --accent-gold: #d4af37;
            --light-gray: #f8f9fa;
        }
        
        .navbar-client {
            background: var(--primary-dark);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .sidebar {
            background: var(--primary-dark);
            color: white;
            min-height: 100vh;
            padding-top: 1rem;
        }
        
        .sidebar-link {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 0.8rem 1.5rem;
            display: block;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-link:hover, .sidebar-link.active {
            color: white;
            background: rgba(212, 175, 55, 0.1);
            border-left-color: var(--accent-gold);
        }
        
        .main-content {
            padding: 2rem;
            background-color: var(--light-gray);
            min-height: 100vh;
        }
        
        .order-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        
        .quantity-input {
            width: 80px;
            text-align: center;
        }
        
        .cart-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            background: #fff;
        }
        
        .moq-badge {
            background-color: var(--accent-gold);
            color: white;
        }
        
        .order-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            position: sticky;
            top: 20px;
        }
        
        .product-search-result {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        
        .product-search-result:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
        }
        
        footer {
            background-color: var(--primary-dark);
            color: white;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        
        .step-indicator:before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #dee2e6;
            z-index: 1;
        }
        
        .step {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #dee2e6;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
        }
        
        .step.active .step-number {
            background-color: var(--accent-gold);
            color: white;
        }
        
        .step.completed .step-number {
            background-color: var(--primary-dark);
            color: white;
        }
        
        .step-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .step.active .step-label {
            color: var(--accent-gold);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-client">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard_client.php">
                <i class="fas fa-tshirt me-2"></i>FUS Client Portal
            </a>
            <div class="navbar-nav ms-auto">
                <span class="nav-item nav-link">
                    <i class="fas fa-building me-1"></i>
                    <?php echo isset($_SESSION['company_name']) ? htmlspecialchars($_SESSION['company_name']) : ''; ?>
                </span>
                <a class="nav-item nav-link" href="login.php?action=logout">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar d-md-block">
                <div class="mb-4 px-3 pt-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px;">
                                <i class="fas fa-user text-dark"></i>
                            </div>
                        </div>
                        <div>
                            <div class="fw-bold small"><?php echo isset($_SESSION['contact_person']) ? htmlspecialchars($_SESSION['contact_person']) : ''; ?></div>
                            <small class="text-muted"><?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?></small>
                        </div>
                    </div>
                </div>
                
                <nav class="nav flex-column">
                    <a href="dashboard_client.php" class="sidebar-link">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="catalog.php" class="sidebar-link">
                        <i class="fas fa-tshirt"></i> Product Catalog
                    </a>
                    <a href="orders.php" class="sidebar-link">
                        <i class="fas fa-shopping-cart"></i> My Orders
                    </a>
                    <a href="new_order.php" class="sidebar-link active">
                        <i class="fas fa-plus-circle"></i> New Order
                    </a>
                    <a href="collections.php" class="sidebar-link">
                        <i class="fas fa-layer-group"></i> Collections
                    </a>
                    <a href="profile.php" class="sidebar-link">
                        <i class="fas fa-user-cog"></i> Profile
                    </a>
                </nav>
            </div>
            
            <!-- Main Content Area -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="container-fluid">
                    <!-- Indicateur d'étapes -->
                    <div class="step-indicator">
                        <div class="step <?php echo empty($cart) ? 'active' : 'completed'; ?>">
                            <div class="step-number">1</div>
                            <div class="step-label">Select Products</div>
                        </div>
                        <div class="step <?php echo !empty($cart) ? 'active' : ''; ?>">
                            <div class="step-number">2</div>
                            <div class="step-label">Configure Quantities</div>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-label">Review & Submit</div>
                        </div>
                    </div>
                    
                    <h2 class="mb-4"><i class="fas fa-file-invoice me-2"></i>New Order</h2>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h6>Please fix the following errors:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Section gauche : Recherche et configuration -->
                        <div class="col-lg-8">
                            <!-- Étape 1 : Recherche de produits -->
                            <div class="order-section" id="product-search-section">
                                <h4 class="mb-4"><i class="fas fa-search me-2"></i>Search Products</h4>
                                
                                <form method="GET" action="" class="mb-4">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" name="search_term" 
                                                   placeholder="Search by product name, reference..." 
                                                   value="<?php echo htmlspecialchars($_GET['search_term'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <select class="form-select" name="collection_filter">
                                                <option value="">All Collections</option>
                                                <?php while ($collection = $collections_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $collection['id']; ?>" 
                                                        <?php echo (($_GET['collection_filter'] ?? '') == $collection['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($collection['name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" name="search_catalog" class="btn btn-primary">
                                                <i class="fas fa-search me-2"></i>Search Products
                                            </button>
                                            <a href="catalog.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-list me-2"></i>Browse Full Catalog
                                            </a>
                                        </div>
                                    </div>
                                </form>
                                
                                <?php if (isset($_GET['search_catalog'])): ?>
                                    <div class="mt-4">
                                        <h5 class="mb-3">Search Results</h5>
                                        <?php if ($search_results->num_rows > 0): ?>
                                            <div class="row">
                                                <?php while ($product = $search_results->fetch_assoc()): ?>
                                                    <div class="col-md-6 mb-3">
                                                        <div class="product-search-result">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div>
                                                                    <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                                    <p class="text-muted small mb-1">Ref: <?php echo htmlspecialchars($product['reference']); ?></p>
                                                                    <p class="small mb-0">Collection: <?php echo htmlspecialchars($product['collection_name']); ?></p>
                                                                </div>
                                                                <span class="badge moq-badge">MOQ: <?php echo $product['moq']; ?></span>
                                                            </div>
                                                            <div class="mt-3">
                                                                <form method="POST" action="">
                                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                                    <input type="hidden" name="action" value="add_to_cart">
                                                                    <button type="submit" class="btn btn-sm btn-success w-100">
                                                                        <i class="fas fa-cart-plus me-2"></i>Add to Order
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-4">
                                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                                <p>No products found matching your search criteria.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Étape 2 : Configuration des quantités -->
                            <?php if (!empty($cart)): ?>
                                <div class="order-section" id="quantity-configuration">
                                    <h4 class="mb-4"><i class="fas fa-edit me-2"></i>Configure Order Items</h4>
                                    
                                    <?php foreach ($cart as $product_id => $item): ?>
                                        <div class="cart-item" id="product-<?php echo $product_id; ?>">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                                    <p class="text-muted small mb-1">
                                                        Ref: <?php echo htmlspecialchars($item['reference']); ?> | 
                                                        Collection: <?php echo htmlspecialchars($item['collection']); ?>
                                                    </p>
                                                    <span class="badge moq-badge">MOQ: <?php echo $item['moq']; ?> units</span>
                                                </div>
                                                <form method="POST" action="" class="mb-0">
                                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                                    <input type="hidden" name="action" value="remove_item">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-times"></i> Remove
                                                    </button>
                                                </form>
                                            </div>
                                            
                                            <form method="POST" action="" class="product-quantity-form">
                                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                                <input type="hidden" name="action" value="update_quantities">
                                                
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Size</th>
                                                                <th>Color</th>
                                                                <th>Wash Type</th>
                                                                <th>Quantity</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php for ($i = 0; $i < max(count($item['sizes']), 1); $i++): ?>
                                                                <tr>
                                                                    <td>
                                                                        <select class="form-select form-select-sm" name="sizes[]">
                                                                            <option value="">Select Size</option>
                                                                            <?php foreach ($item['sizes'] as $size): ?>
                                                                                <option value="<?php echo htmlspecialchars(trim($size)); ?>" 
                                                                                    <?php echo (isset($item['sizes'][$i]) && trim($item['sizes'][$i]) == trim($size)) ? 'selected' : ''; ?>>
                                                                                    <?php echo htmlspecialchars(trim($size)); ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <select class="form-select form-select-sm" name="colors[]">
                                                                            <option value="">Select Color</option>
                                                                            <?php foreach ($item['colors'] as $color): ?>
                                                                                <option value="<?php echo htmlspecialchars(trim($color)); ?>"
                                                                                    <?php echo (isset($item['selected_colors'][$i]) && trim($item['selected_colors'][$i]) == trim($color)) ? 'selected' : ''; ?>>
                                                                                    <?php echo htmlspecialchars(trim($color)); ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <select class="form-select form-select-sm" name="wash_types[]">
                                                                            <option value="">Select Wash</option>
                                                                            <?php foreach ($item['wash_types'] as $wash): ?>
                                                                                <option value="<?php echo htmlspecialchars(trim($wash)); ?>"
                                                                                    <?php echo (isset($item['selected_wash_types'][$i]) && trim($item['selected_wash_types'][$i]) == trim($wash)) ? 'selected' : ''; ?>>
                                                                                    <?php echo htmlspecialchars(trim($wash)); ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <input type="number" class="form-control form-control-sm quantity-input" 
                                                                               name="quantities[]" 
                                                                               min="0" 
                                                                               value="<?php echo htmlspecialchars($item['quantities'][$i] ?? '0'); ?>">
                                                                    </td>
                                                                </tr>
                                                            <?php endfor; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label small">Technical Notes for this product:</label>
                                                    <textarea class="form-control form-control-sm" name="product_notes" rows="2" 
                                                              placeholder="Any specific requirements for this product..."><?php echo htmlspecialchars($item['notes'] ?? ''); ?></textarea>
                                                </div>
                                                
                                                <div class="text-end">
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-save me-2"></i>Update Quantities
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="text-center mt-4">
                                        <a href="catalog.php" class="btn btn-outline-primary">
                                            <i class="fas fa-plus me-2"></i>Add More Products
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Étape 3 : Informations de commande -->
                                <div class="order-section" id="order-details">
                                    <h4 class="mb-4"><i class="fas fa-clipboard-list me-2"></i>Order Details</h4>
                                    
                                    <form method="POST" action="" id="order-form">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label">Shipping Address</label>
                                                <textarea class="form-control" name="shipping_address" rows="3" 
                                                          placeholder="Enter complete shipping address..." required><?php echo htmlspecialchars($_SESSION['company_address'] ?? ''); ?></textarea>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label class="form-label">Requested Delivery Date</label>
                                                <input type="date" class="form-control" name="requested_delivery" 
                                                       min="<?php echo date('Y-m-d', strtotime('+1 week')); ?>"
                                                       value="<?php echo date('Y-m-d', strtotime('+4 weeks')); ?>">
                                                <small class="text-muted">Minimum production time: 3-4 weeks</small>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label class="form-label">Order Priority</label>
                                                <select class="form-select" name="priority">
                                                    <option value="normal">Normal</option>
                                                    <option value="high">High Priority</option>
                                                    <option value="urgent">Urgent</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-12">
                                                <label class="form-label">Technical & Production Notes</label>
                                                <textarea class="form-control" name="order_notes" rows="4" 
                                                          placeholder="Any specific requirements, instructions, or notes for this order..."></textarea>
                                                <small class="text-muted">Include any special instructions for production, packaging, or shipping</small>
                                            </div>
                                            
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="terms" required>
                                                    <label class="form-check-label" for="terms">
                                                        I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">terms and conditions</a> 
                                                        and confirm that all order details are correct.
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4 pt-3 border-top">
                                            <div class="d-flex justify-content-between">
                                                <button type="submit" name="action" value="save_draft" class="btn btn-outline-secondary">
                                                    <i class="fas fa-save me-2"></i>Save as Draft
                                                </button>
                                                
                                                <div>
                                                    <button type="button" class="btn btn-outline-danger me-2" onclick="clearCart()">
                                                        <i class="fas fa-trash me-2"></i>Clear Order
                                                    </button>
                                                    <button type="submit" name="action" value="submit_order" class="btn btn-success btn-lg">
                                                        <i class="fas fa-paper-plane me-2"></i>Submit Order
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Section droite : Récapitulatif -->
                        <div class="col-lg-4">
                            <div class="order-summary">
                                <h4 class="mb-4"><i class="fas fa-receipt me-2"></i>Order Summary</h4>
                                
                                <?php if (empty($cart)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                        <p>Your order is empty</p>
                                        <p class="small text-muted">Search and add products to begin</p>
                                    </div>
                                <?php else: ?>
                                    <div class="mb-4">
                                        <h6>Items in Order:</h6>
                                        <ul class="list-unstyled mb-0">
                                            <?php 
                                            $total_items = 0;
                                            $total_value = 0;
                                            foreach ($cart as $item):
                                                $item_total = 0;
                                                foreach ($item['quantities'] as $qty) {
                                                    $item_total += intval($qty);
                                                }
                                                $total_items += $item_total;
                                            ?>
                                                <li class="mb-2 d-flex justify-content-between">
                                                    <span class="small"><?php echo htmlspecialchars($item['name']); ?></span>
                                                    <span class="small fw-bold"><?php echo $item_total; ?> units</span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Total Items:</span>
                                            <span class="fw-bold"><?php echo $total_items; ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Total Products:</span>
                                            <span class="fw-bold"><?php echo count($cart); ?></span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold">Order Status:</span>
                                            <span class="badge bg-warning">Draft in Progress</span>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info small">
                                        <h6><i class="fas fa-info-circle me-2"></i>Order Guidelines:</h6>
                                        <ul class="mb-0">
                                            <li>Minimum order quantity applies per product</li>
                                            <li>Standard production time: 3-4 weeks</li>
                                            <li>Shipping costs calculated upon confirmation</li>
                                            <li>Save as draft to continue later</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <h6>Quick Actions:</h6>
                                        <div class="d-grid gap-2">
                                            <a href="orders_drafts.php" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-file-alt me-2"></i>View Drafts
                                            </a>
                                            <a href="orders.php" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-history me-2"></i>Order History
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- Fermeture de la colonne main-content -->
        </div> <!-- Fermeture de la row -->
    </div> <!-- Fermeture du container-fluid -->
    
    <!-- Modal Terms & Conditions -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms & Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Order Terms</h6>
                    <p>By submitting this order, you agree to the following terms:</p>
                    <ul>
                        <li>Minimum order quantities (MOQ) must be respected per product</li>
                        <li>Standard production time is 3-4 weeks from order confirmation</li>
                        <li>Shipping costs will be confirmed before order processing</li>
                        <li>Cancellations are only accepted within 24 hours of order submission</li>
                        <li>Custom specifications may affect pricing and lead time</li>
                    </ul>
                    <h6 class="mt-4">Quality Assurance</h6>
                    <p>All products undergo quality control. Claims must be submitted within 14 days of receipt.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>FUS Denim Portal</h5>
                    <p class="text-muted">B2B Order Management System</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">
                        &copy; <?php echo date('Y'); ?> Fashion Unique Solutions
                    </p>
                    <p class="text-muted small">New Order | v1.0</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    
    <script>
        // Fonction pour vider le panier
        function clearCart() {
            if (confirm('Are you sure you want to clear the entire order? This cannot be undone.')) {
                window.location.href = 'clear_cart.php';
            }
        }
        
        // Fonction pour ajouter une ligne de taille
        function addSizeRow(productId) {
            const tbody = document.querySelector(`#product-${productId} tbody`);
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>
                    <select class="form-select form-select-sm" name="sizes[]">
                        <option value="">Select Size</option>
                        ${Array.from(document.querySelectorAll(`#product-${productId} select[name="sizes[]"]`)[0].options)
                            .map(option => `<option value="${option.value}">${option.text}</option>`)
                            .join('')}
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm" name="colors[]">
                        <option value="">Select Color</option>
                        ${Array.from(document.querySelectorAll(`#product-${productId} select[name="colors[]"]`)[0].options)
                            .map(option => `<option value="${option.value}">${option.text}</option>`)
                            .join('')}
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm" name="wash_types[]">
                        <option value="">Select Wash</option>
                        ${Array.from(document.querySelectorAll(`#product-${productId} select[name="wash_types[]"]`)[0].options)
                            .map(option => `<option value="${option.value}">${option.text}</option>`)
                            .join('')}
                    </select>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm quantity-input" 
                           name="quantities[]" min="0" value="0">
                </td>
            `;
            tbody.appendChild(newRow);
        }
        
        // Fonction pour valider les quantités avant soumission
        function validateOrder() {
            let valid = true;
            let errorMessages = [];
            
            // Vérifier le MOQ pour chaque produit
            document.querySelectorAll('.cart-item').forEach(item => {
                const productName = item.querySelector('h5').textContent;
                const moq = parseInt(item.querySelector('.moq-badge').textContent.replace('MOQ: ', '').replace(' units', ''));
                let totalQty = 0;
                
                item.querySelectorAll('.quantity-input').forEach(input => {
                    totalQty += parseInt(input.value) || 0;
                });
                
                if (totalQty > 0 && totalQty < moq) {
                    valid = false;
                    errorMessages.push(`${productName}: Minimum order quantity is ${moq} units (you ordered ${totalQty})`);
                }
            });
            
            if (!valid) {
                alert('Please fix the following issues:\n\n' + errorMessages.join('\n'));
                return false;
            }
            
            return true;
        }
        
        // Ajouter la validation au formulaire
        document.getElementById('order-form').addEventListener('submit', function(e) {
            if (e.submitter.value === 'submit_order') {
                if (!validateOrder()) {
                    e.preventDefault();
                }
            }
        });
        
        // Calculer le total en temps réel
        function updateOrderSummary() {
            let totalItems = 0;
            let totalProducts = document.querySelectorAll('.cart-item').length;
            
            document.querySelectorAll('.quantity-input').forEach(input => {
                totalItems += parseInt(input.value) || 0;
            });
            
            // Mettre à jour l'affichage (à implémenter si nécessaire)
            console.log('Total items:', totalItems, 'Total products:', totalProducts);
        }
        
        // Écouter les changements de quantité
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('quantity-input')) {
                updateOrderSummary();
            }
        });
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            updateOrderSummary();
            
            // Raccourcis clavier
            document.addEventListener('keydown', function(e) {
                // Ctrl+S pour sauvegarder comme brouillon
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    document.querySelector('button[value="save_draft"]').click();
                }
                
                // Ctrl+Enter pour soumettre
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    document.querySelector('button[value="submit_order"]').click();
                }
            });
        });
    </script>
</body>
</html>