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

if (!isset($_SESSION['company_name'])) {
    $_SESSION['company_name'] = '';
}
if (!isset($_SESSION['contact_person'])) {
    $_SESSION['contact_person'] = '';
}
if (!isset($_SESSION['user_email'])) {
    $_SESSION['user_email'] = '';
}
if (!isset($_SESSION['company_address'])) {
    $_SESSION['company_address'] = '';
}

function getProductUnitPrice($conn, $product_id) {
    $query = "SELECT unit_price FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return floatval($row['unit_price']);
    }
    return 0.00;
}

function getProductMainImage($conn, $product_id) {
    $query = "SELECT image_url FROM product_images WHERE product_id = ? AND is_main = 1 LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['image_url'];
    }
    return '/uploads/products/default.jpg';
}

foreach ($cart as $product_id => &$item) {
    if (!isset($item['unit_price']) || $item['unit_price'] == 0) {
        $item['unit_price'] = getProductUnitPrice($conn, $product_id);
    }
    if (!isset($item['image_url']) || empty($item['image_url'])) {
        $item['image_url'] = getProductMainImage($conn, $product_id);
    }
    $item['selected_colors'] = $item['selected_colors'] ?? [];
    $item['selected_wash_types'] = $item['selected_wash_types'] ?? [];
    $item['notes'] = $item['notes'] ?? '';
    $item['quantities'] = $item['quantities'] ?? [];
}
unset($item); 

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
        $available_colors = !empty($product['available_colors']) ? explode(',', $product['available_colors']) : ['Standard'];
        $available_sizes = !empty($product['available_sizes']) ? explode(',', $product['available_sizes']) : ['Standard'];
        $wash_types = !empty($product['wash_types']) ? explode(',', $product['wash_types']) : ['Standard'];
        
        $cart_item = [
            'product_id' => $product['id'],
            'reference' => $product['reference'],
            'name' => $product['name'],
            'moq' => $product['moq'] ?? 1,
            'unit_price' => $product['unit_price'] ?? 0.00,
            'image_url' => getProductMainImage($conn, $product['id']),
            'collection' => $product['collection_name'] ?? '',
            'colors' => $available_colors,
            'sizes' => $available_sizes,
            'wash_types' => $wash_types,
            'quantities' => array_fill(0, count($available_sizes), 0),
            'selected_colors' => array_fill(0, count($available_sizes), ''),
            'selected_wash_types' => array_fill(0, count($available_sizes), ''),
            'notes' => ''
        ];
        $cart[$product['id']] = $cart_item;
        $_SESSION['cart'] = $cart;
    }
}

$success_message = '';
$errors = [];

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
                    $available_colors = !empty($product['available_colors']) ? explode(',', $product['available_colors']) : ['Standard'];
                    $available_sizes = !empty($product['available_sizes']) ? explode(',', $product['available_sizes']) : ['Standard'];
                    $wash_types = !empty($product['wash_types']) ? explode(',', $product['wash_types']) : ['Standard'];
                    
                    $cart_item = [
                        'product_id' => $product['id'],
                        'reference' => $product['reference'],
                        'name' => $product['name'],
                        'moq' => $product['moq'] ?? 1,
                        'unit_price' => $product['unit_price'] ?? 0.00,
                        'image_url' => getProductMainImage($conn, $product['id']),
                        'collection' => $product['collection_name'] ?? '',
                        'colors' => $available_colors,
                        'sizes' => $available_sizes,
                        'wash_types' => $wash_types,
                        'quantities' => array_fill(0, count($available_sizes), 0),
                        'selected_colors' => array_fill(0, count($available_sizes), ''),
                        'selected_wash_types' => array_fill(0, count($available_sizes), ''),
                        'notes' => ''
                    ];
                    $cart[$product['id']] = $cart_item;
                    $_SESSION['cart'] = $cart;
                    $success_message = "Produit ajouté à la commande !";
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
                $success_message = "Quantités mises à jour !";
            }
            break;
            
        case 'remove_item':
            $remove_product_id = $_POST['product_id'] ?? null;
            if ($remove_product_id && isset($cart[$remove_product_id])) {
                unset($cart[$remove_product_id]);
                $_SESSION['cart'] = $cart;
                $success_message = "Produit supprimé de la commande !";
            }
            break;
            
        case 'save_draft':
            $order_notes = $_POST['order_notes'] ?? '';
            $shipping_address = $_POST['shipping_address'] ?? '';
            $requested_delivery = $_POST['requested_delivery'] ?? '';
            
            $total_items = 0;
            $total_value = 0.00;
            foreach ($cart as $item) {
                $unit_price = isset($item['unit_price']) ? floatval($item['unit_price']) : 0.00;
                foreach ($item['quantities'] as $qty) {
                    $qty_val = intval($qty);
                    $total_items += $qty_val;
                    $total_value += $qty_val * $unit_price;
                }
            }
            
            $reference = 'DRAFT-' . date('Ymd-His') . '-' . rand(100, 999);
            
            $query = "INSERT INTO orders (client_id, reference, status, total_items, total_value, notes, shipping_address, estimated_delivery) 
                      VALUES (?, ?, 'received', ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isiddss", $client_id, $reference, $total_items, $total_value, $order_notes, $shipping_address, $requested_delivery);
            
            if ($stmt->execute()) {
                $order_id = $stmt->insert_id;
                foreach ($cart as $product_id => $item) {
                    $unit_price = isset($item['unit_price']) ? floatval($item['unit_price']) : 0.00;
                    foreach ($item['quantities'] as $index => $quantity) {
                        if ($quantity > 0) {
                            $color = isset($item['selected_colors'][$index]) ? $item['selected_colors'][$index] : '';
                            $size = isset($item['sizes'][$index]) ? $item['sizes'][$index] : '';
                            $wash_type = isset($item['selected_wash_types'][$index]) ? $item['selected_wash_types'][$index] : '';
                            $subtotal = $quantity * $unit_price;
                            
                            $item_query = "INSERT INTO order_items (order_id, product_id, color, size, wash_type, quantity, unit_price, subtotal, notes) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $item_stmt = $conn->prepare($item_query);
                            $item_stmt->bind_param("iisssidds", $order_id, $product_id, $color, $size, $wash_type, $quantity, $unit_price, $subtotal, $item['notes']);
                            $item_stmt->execute();
                        }
                    }
                }
                
                $_SESSION['cart'] = [];
                $cart = [];
                
                $success_message = "Commande sauvegardée ! Référence : " . $reference;
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
                
                if ($total_qty > 0 && $total_qty < $item['moq']) {
                    $errors[] = "Produit '{$item['name']}' : Quantité minimum est {$item['moq']} unités (vous avez commandé $total_qty)";
                }
            }
            
            if (empty($errors)) {
                $total_items = 0;
                $total_value = 0.00;
                foreach ($cart as $item) {
                    $unit_price = isset($item['unit_price']) ? floatval($item['unit_price']) : 0.00;
                    foreach ($item['quantities'] as $qty) {
                        $qty_val = intval($qty);
                        $total_items += $qty_val;
                        $total_value += $qty_val * $unit_price;
                    }
                }
                
                $reference = 'ORD-' . date('Ymd') . '-' . strtoupper(substr($_SESSION['company_name'], 0, 3)) . '-' . rand(1000, 9999);
                $query = "INSERT INTO orders (client_id, reference, status, total_items, total_value, notes, shipping_address, estimated_delivery) 
                          VALUES (?, ?, 'received', ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("isiddss", $client_id, $reference, $total_items, $total_value, $order_notes, $shipping_address, $requested_delivery);
                
                if ($stmt->execute()) {
                    $order_id = $stmt->insert_id;
                    foreach ($cart as $product_id => $item) {
                        $unit_price = isset($item['unit_price']) ? floatval($item['unit_price']) : 0.00;
                        foreach ($item['quantities'] as $index => $quantity) {
                            if ($quantity > 0) {
                                $color = isset($item['selected_colors'][$index]) ? $item['selected_colors'][$index] : '';
                                $size = isset($item['sizes'][$index]) ? $item['sizes'][$index] : '';
                                $wash_type = isset($item['selected_wash_types'][$index]) ? $item['selected_wash_types'][$index] : '';
                                $subtotal = $quantity * $unit_price;
                                
                                $item_query = "INSERT INTO order_items (order_id, product_id, color, size, wash_type, quantity, unit_price, subtotal, notes) 
                                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                $item_stmt = $conn->prepare($item_query);
                                $item_stmt->bind_param("iisssidds", $order_id, $product_id, $color, $size, $wash_type, $quantity, $unit_price, $subtotal, $item['notes']);
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
    
    $search_query = "SELECT p.id, p.reference, p.name, p.moq, p.unit_price, c.name as collection_name, pi.image_url as main_image 
                     FROM products p 
                     LEFT JOIN collections c ON p.collection_id = c.id 
                     LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
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
    
    $search_query .= " ORDER BY p.name LIMIT 20";
    
    $stmt = $conn->prepare($search_query);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $search_results = $stmt->get_result();
}

$collections_result = $conn->query("SELECT id, name FROM collections WHERE is_public = 1 ORDER BY name");

$total_items = 0;
$total_amount = 0.00;
$product_count = 0;
if (!empty($cart)) {
    foreach ($cart as $item) {
        $product_count++;
        $unit_price = isset($item['unit_price']) ? floatval($item['unit_price']) : 0.00;
        foreach ($item['quantities'] as $qty) {
            $qty_val = intval($qty);
            $total_items += $qty_val;
            $total_amount += $qty_val * $unit_price;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Commande - FUS Denim</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .btn-success {
            background: linear-gradient(135deg, var(--accent-4), var(--accent-1));
            border: none;
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
        }

        .order-section {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            margin-bottom: 2rem;
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2.5rem;
            position: relative;
        }

        .step-indicator:before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: var(--gray-200);
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
            background-color: var(--gray-200);
            color: var(--gray-500);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .step.active .step-number {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: var(--white);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .step.completed .step-number {
            background: linear-gradient(135deg, var(--accent-4), var(--accent-1));
            color: var(--white);
        }

        .step-label {
            font-size: 0.9rem;
            color: var(--gray-500);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .step.active .step-label {
            color: var(--accent-1);
            font-weight: 600;
        }

        .product-search-result {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .product-search-result:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--accent-1);
        }

        .moq-badge {
            background: linear-gradient(135deg, var(--accent-5), #FBBF24);
            color: var(--white);
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .cart-item {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .cart-item:hover {
            box-shadow: var(--shadow-md);
        }

        .product-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
        }

        .order-summary {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            position: sticky;
            top: 2rem;
        }

        .quantity-input {
            width: 80px;
            text-align: center;
            border-radius: 8px;
            border: 1px solid var(--gray-300);
            padding: 0.5rem;
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
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-1);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

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

        .price-badge {
            background: linear-gradient(135deg, var(--accent-4), var(--accent-1));
            color: var(--white);
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .subtotal-cell {
            min-width: 100px;
        }

        @media (max-width: 1200px) {
            .sidebar {
                width: 260px;
            }

            .main-content {
                margin-left: 260px;
                padding: 1.5rem;
            }
        }

        @media (max-width: 992px) {
            .order-summary {
                position: static;
                margin-top: 2rem;
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

            .step-indicator {
                flex-direction: column;
                gap: 1rem;
            }

            .step-indicator:before {
                display: none;
            }
            
            .product-image {
                width: 80px;
                height: 80px;
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
        }
    </style>
</head>
<body>
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
            <a href="catalog_prv.php" class="nav-item">
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
                    <strong><?php echo htmlspecialchars(substr($_SESSION['company_name'] ?? '', 0, 20)); ?></strong>
                </div>
            </div>
            <a href="login.php?action=logout" class="logout-btn">
                <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="header-title">
                <h1>Nouvelle Commande</h1>
                <p>Créez une nouvelle commande FUS Denim</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
                <a href="orders.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Retour aux commandes
                </a>
            </div>
        </div>

        <div class="step-indicator">
            <div class="step <?php echo empty($cart) ? 'active' : 'completed'; ?>">
                <div class="step-number">1</div>
                <div class="step-label">Sélection Produits</div>
            </div>
            <div class="step <?php echo !empty($cart) ? 'active' : ''; ?>">
                <div class="step-number">2</div>
                <div class="step-label">Configuration Quantités</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-label">Validation & Envoi</div>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h6>Veuillez corriger les erreurs suivantes :</h6>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="order-section" id="product-search-section">
                    <h4 class="mb-4"><i class="fas fa-search me-2"></i>Recherche Produits</h4>
                    
                    <form method="GET" action="" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="search_term" 
                                       placeholder="Rechercher par nom, référence..." 
                                       value="<?php echo htmlspecialchars($_GET['search_term'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="collection_filter">
                                    <option value="">Toutes les collections</option>
                                    <?php 
                                    $collections_result->data_seek(0); // Réinitialiser le pointeur
                                    while ($collection = $collections_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $collection['id']; ?>" 
                                            <?php echo (($_GET['collection_filter'] ?? '') == $collection['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($collection['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="search_catalog" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Rechercher
                                </button>
                                <a href="catalog_prv.php" class="btn btn-outline-primary">
                                    <i class="fas fa-list me-2"></i>Voir tout le catalogue
                                </a>
                            </div>
                        </div>
                    </form>
                    
                    <?php if (isset($_GET['search_catalog'])): ?>
                        <div class="mt-4">
                            <h5 class="mb-3">Résultats de recherche</h5>
                            <?php if ($search_results->num_rows > 0): ?>
                                <div class="row">
                                    <?php while ($product = $search_results->fetch_assoc()): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="product-search-result">
                                                <div class="d-flex align-items-start">
                                                    <?php if (!empty($product['main_image'])): ?>
                                                        <div class="me-3" style="width: 80px; height: 80px;">
                                                            <img src="<?php echo htmlspecialchars($product['main_image']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                                 class="img-fluid rounded" 
                                                                 style="width: 100%; height: 100%; object-fit: cover;">
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                                <p class="text-muted small mb-1">Ref: <?php echo htmlspecialchars($product['reference']); ?></p>
                                                                <p class="small mb-1">Collection: <?php echo htmlspecialchars($product['collection_name']); ?></p>
                                                                <p class="small mb-0 text-success">
                                                                    <strong><?php echo number_format($product['unit_price'] ?? 0, 2); ?> €</strong>
                                                                </p>
                                                            </div>
                                                            <span class="moq-badge">MOQ: <?php echo $product['moq']; ?></span>
                                                        </div>
                                                        <div class="mt-3">
                                                            <form method="POST" action="">
                                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                                <input type="hidden" name="action" value="add_to_cart">
                                                                <button type="submit" class="btn btn-sm btn-success w-100">
                                                                    <i class="fas fa-cart-plus me-2"></i>Ajouter à la commande
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                    <p>Aucun produit trouvé correspondant à vos critères.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($cart)): ?>
                    <div class="order-section" id="quantity-configuration">
                        <h4 class="mb-4"><i class="fas fa-edit me-2"></i>Configuration des articles</h4>
                        
                        <?php foreach ($cart as $product_id => $item): 
                            $unit_price = isset($item['unit_price']) ? floatval($item['unit_price']) : 0.00;
                            $product_subtotal = 0;
                            foreach ($item['quantities'] as $index => $quantity) {
                                $qty_val = intval($quantity);
                                $product_subtotal += $qty_val * $unit_price;
                            }
                        ?>
                            <div class="cart-item" id="product-<?php echo $product_id; ?>">
                                <div class="d-flex mb-3">
                                    <!-- Image du produit -->
                                    <div class="me-3">
                                        <img src="<?php echo htmlspecialchars($item['image_url'] ?? '/uploads/products/default.jpg'); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             class="product-image">
                                    </div>
                                    
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                                <p class="text-muted small mb-1">
                                                    Ref: <?php echo htmlspecialchars($item['reference']); ?> | 
                                                    Collection: <?php echo htmlspecialchars($item['collection'] ?? ''); ?>
                                                </p>
                                                <div class="d-flex gap-3">
                                                    <span class="moq-badge">MOQ: <?php echo $item['moq']; ?> unités</span>
                                                    <span class="price-badge">
                                                        Prix unitaire: <?php echo number_format($unit_price, 2); ?> €
                                                    </span>
                                                </div>
                                            </div>
                                            <form method="POST" action="" class="mb-0">
                                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                                <input type="hidden" name="action" value="remove_item">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-times"></i> Supprimer
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <form method="POST" action="" class="product-quantity-form mt-3">
                                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                            <input type="hidden" name="action" value="update_quantities">
                                            
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Taille</th>
                                                            <th>Couleur</th>
                                                            <th>Type de lavage</th>
                                                            <th>Quantité</th>
                                                            <th>Prix unitaire</th>
                                                            <th>Sous-total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php 
                                                        $sizes_count = count($item['sizes'] ?? []);
                                                        if ($sizes_count === 0) {
                                                            $sizes_count = 1; // Au moins une ligne
                                                        }
                                                        for ($i = 0; $i < $sizes_count; $i++):
                                                            $quantity = intval($item['quantities'][$i] ?? 0);
                                                            $item_subtotal = $quantity * $unit_price;
                                                        ?>
                                                            <tr>
                                                                <td>
                                                                    <select class="form-select form-select-sm" name="sizes[]">
                                                                        <option value="">Sélectionner taille</option>
                                                                        <?php if (isset($item['sizes']) && is_array($item['sizes'])): ?>
                                                                            <?php foreach ($item['sizes'] as $size): ?>
                                                                                <option value="<?php echo htmlspecialchars(trim($size)); ?>" 
                                                                                    <?php echo (isset($item['sizes'][$i]) && trim($item['sizes'][$i]) == trim($size)) ? 'selected' : ''; ?>>
                                                                                    <?php echo htmlspecialchars(trim($size)); ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        <?php endif; ?>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <select class="form-select form-select-sm" name="colors[]">
                                                                        <option value="">Sélectionner couleur</option>
                                                                        <?php if (isset($item['colors']) && is_array($item['colors'])): ?>
                                                                            <?php foreach ($item['colors'] as $color): ?>
                                                                                <option value="<?php echo htmlspecialchars(trim($color)); ?>"
                                                                                    <?php echo (isset($item['selected_colors'][$i]) && trim($item['selected_colors'][$i]) == trim($color)) ? 'selected' : ''; ?>>
                                                                                    <?php echo htmlspecialchars(trim($color)); ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        <?php endif; ?>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <select class="form-select form-select-sm" name="wash_types[]">
                                                                        <option value="">Sélectionner lavage</option>
                                                                        <?php if (isset($item['wash_types']) && is_array($item['wash_types'])): ?>
                                                                            <?php foreach ($item['wash_types'] as $wash): ?>
                                                                                <option value="<?php echo htmlspecialchars(trim($wash)); ?>"
                                                                                    <?php echo (isset($item['selected_wash_types'][$i]) && trim($item['selected_wash_types'][$i]) == trim($wash)) ? 'selected' : ''; ?>>
                                                                                    <?php echo htmlspecialchars(trim($wash)); ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        <?php endif; ?>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="number" class="form-control form-control-sm quantity-input" 
                                                                           name="quantities[]" 
                                                                           min="0" 
                                                                           value="<?php echo htmlspecialchars($quantity); ?>"
                                                                           data-unit-price="<?php echo $unit_price; ?>"
                                                                           onchange="updateItemSubtotal(this)">
                                                                </td>
                                                                <td class="text-center align-middle">
                                                                    <?php echo number_format($unit_price, 2); ?> €
                                                                </td>
                                                                <td class="text-center align-middle subtotal-cell">
                                                                    <span class="item-subtotal"><?php echo number_format($item_subtotal, 2); ?> €</span>
                                                                </td>
                                                            </tr>
                                                        <?php endfor; ?>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <td colspan="5" class="text-end"><strong>Total produit :</strong></td>
                                                            <td class="text-center align-middle">
                                                                <strong class="product-total"><?php echo number_format($product_subtotal, 2); ?> €</strong>
                                                            </td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label small">Notes techniques pour ce produit :</label>
                                                <textarea class="form-control form-control-sm" name="product_notes" rows="2" 
                                                          placeholder="Exigences spécifiques pour ce produit..."><?php echo htmlspecialchars($item['notes'] ?? ''); ?></textarea>
                                            </div>
                                            
                                            <div class="text-end">
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-save me-2"></i>Mettre à jour
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="text-center mt-4">
                            <a href="catalog_prv.php" class="btn btn-outline-primary">
                                <i class="fas fa-plus me-2"></i>Ajouter plus de produits
                            </a>
                        </div>
                    </div>
                    
                    <div class="order-section" id="order-details">
                        <h4 class="mb-4"><i class="fas fa-clipboard-list me-2"></i>Détails de la commande</h4>
                        
                        <form method="POST" action="" id="order-form">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Adresse de livraison</label>
                                    <textarea class="form-control" name="shipping_address" rows="3" 
                                              placeholder="Adresse complète de livraison..." required><?php echo htmlspecialchars($_SESSION['company_address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Date de livraison souhaitée</label>
                                    <input type="date" class="form-control" name="requested_delivery" 
                                           min="<?php echo date('Y-m-d', strtotime('+1 week')); ?>"
                                           value="<?php echo date('Y-m-d', strtotime('+4 weeks')); ?>">
                                    <small class="text-muted">Délai de production minimum : 3-4 semaines</small>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Priorité de commande</label>
                                    <select class="form-select" name="priority">
                                        <option value="normal">Normale</option>
                                        <option value="high">Haute priorité</option>
                                        <option value="urgent">Urgente</option>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label">Notes techniques & production</label>
                                    <textarea class="form-control" name="order_notes" rows="4" 
                                              placeholder="Instructions spécifiques, exigences ou notes pour cette commande..."></textarea>
                                    <small class="text-muted">Incluez toute instruction spéciale pour la production, l'emballage ou l'expédition</small>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="terms" required>
                                        <label class="form-check-label" for="terms">
                                            J'accepte les <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">conditions générales</a> 
                                            et confirme que tous les détails de la commande sont corrects.
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 pt-3 border-top">
                                <div class="d-flex justify-content-between">
                                    <button type="submit" name="action" value="save_draft" class="btn btn-outline-secondary">
                                        <i class="fas fa-save me-2"></i>Sauvegarder comme brouillon
                                    </button>
                                    
                                    <div>
                                        <button type="button" class="btn btn-outline-danger me-2" onclick="clearCart()">
                                            <i class="fas fa-trash me-2"></i>Vider la commande
                                        </button>
                                        <button type="submit" name="action" value="submit_order" class="btn btn-success btn-lg">
                                            <i class="fas fa-paper-plane me-2"></i>Soumettre la commande
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-lg-4">
                <div class="order-summary">
                    <h4 class="mb-4"><i class="fas fa-receipt me-2"></i>Récapitulatif</h4>
                    
                    <?php if (empty($cart)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <p>Votre commande est vide</p>
                            <p class="small text-muted">Recherchez et ajoutez des produits pour commencer</p>
                        </div>
                    <?php else: ?>
                        <div class="mb-4">
                            <h6>Articles dans la commande :</h6>
                            <ul class="list-unstyled mb-0">
                                <?php 
                                $total_items_summary = 0;
                                $total_amount_summary = 0.00;
                                foreach ($cart as $item):
                                    $item_total = 0;
                                    $item_amount = 0;
                                    $unit_price = isset($item['unit_price']) ? floatval($item['unit_price']) : 0.00;
                                    foreach ($item['quantities'] as $qty) {
                                        $qty_val = intval($qty);
                                        $item_total += $qty_val;
                                        $item_amount += $qty_val * $unit_price;
                                    }
                                    $total_items_summary += $item_total;
                                    $total_amount_summary += $item_amount;
                                ?>
                                    <li class="mb-2 d-flex justify-content-between align-items-center">
                                        <div class="small">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                            <br><small class="text-muted"><?php echo number_format($unit_price, 2); ?> €/unité</small>
                                        </div>
                                        <div class="text-end">
                                            <span class="small fw-bold d-block"><?php echo $item_total; ?> unités</span>
                                            <span class="small text-success"><?php echo number_format($item_amount, 2); ?> €</span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total articles :</span>
                                <span class="fw-bold total-items"><?php echo $total_items_summary; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total produits :</span>
                                <span class="fw-bold"><?php echo count($cart); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Montant HT :</span>
                                <span class="fw-bold total-ht"><?php echo number_format($total_amount_summary, 2); ?> €</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>TVA (20%) :</span>
                                <span class="fw-bold total-tva"><?php echo number_format($total_amount_summary * 0.20, 2); ?> €</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">Total TTC :</span>
                                <span class="fw-bold text-success h5 total-ttc"><?php echo number_format($total_amount_summary * 1.20, 2); ?> €</span>
                            </div>
                        </div>
                        
                        <div class="alert alert-info small">
                            <h6><i class="fas fa-info-circle me-2"></i>Instructions :</h6>
                            <ul class="mb-0">
                                <li>Quantité minimum applicable par produit</li>
                                <li>Délai de production standard : 3-4 semaines</li>
                                <li>Frais d'expédition calculés lors de la confirmation</li>
                                <li>Sauvegardez comme brouillon pour continuer plus tard</li>
                            </ul>
                        </div>
                        
                        <div class="mt-4">
                            <h6>Actions rapides :</h6>
                            <div class="d-grid gap-2">
                                <a href="orders.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-file-alt me-2"></i>Voir mes commandes
                                </a>
                                <a href="catalog_prv.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-tshirt me-2"></i>Explorer catalogue
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="footer">
            <div>
                <i class="fas fa-gem" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Nouvelle Commande
            </div>
            <div>
                <span class="system-status">
                    <i class="fas fa-circle"></i> <?php echo count($cart); ?> produit(s) en cours
                </span>
            </div>
        </div>
    </div>

    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Conditions Générales</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Conditions de commande</h6>
                    <p>En soumettant cette commande, vous acceptez les conditions suivantes :</p>
                    <ul>
                        <li>Les quantités minimales de commande (MOQ) doivent être respectées par produit</li>
                        <li>Le délai de production standard est de 3-4 semaines à partir de la confirmation</li>
                        <li>Les frais d'expédition seront confirmés avant le traitement de la commande</li>
                        <li>Les annulations ne sont acceptées que dans les 24 heures suivant la soumission</li>
                        <li>Les spécifications personnalisées peuvent affecter les prix et les délais</li>
                    </ul>
                    <h6 class="mt-4">Assurance Qualité</h6>
                    <p>Tous les produits subissent un contrôle qualité. Les réclamations doivent être soumises dans les 14 jours suivant la réception.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function clearCart() {
            if (confirm('Êtes-vous sûr de vouloir vider toute la commande ? Cette action est irréversible.')) {
                window.location.href = 'clear_cart.php';
            }
        }
        
        function updateItemSubtotal(input) {
            const quantity = parseInt(input.value) || 0;
            const unitPrice = parseFloat(input.dataset.unitPrice) || 0;
            const row = input.closest('tr');
            const subtotalCell = row.querySelector('.item-subtotal');
            
            // Mettre à jour le sous-total de la ligne
            const itemSubtotal = quantity * unitPrice;
            subtotalCell.textContent = itemSubtotal.toFixed(2) + ' €';
            
            // Calculer le total du produit
            const productRow = input.closest('.cart-item');
            let productTotal = 0;
            productRow.querySelectorAll('.quantity-input').forEach(qtyInput => {
                const qty = parseInt(qtyInput.value) || 0;
                const price = parseFloat(qtyInput.dataset.unitPrice) || 0;
                productTotal += qty * price;
            });
            
            // Mettre à jour le total du produit
            productRow.querySelector('.product-total').textContent = productTotal.toFixed(2) + ' €';
            
            // Mettre à jour le récapitulatif général
            updateOrderSummary();
        }
        
        function updateOrderSummary() {
            let totalItems = 0;
            let totalAmount = 0;
            
            document.querySelectorAll('.cart-item').forEach(item => {
                const quantityInputs = item.querySelectorAll('.quantity-input');
                const unitPrice = parseFloat(quantityInputs[0]?.dataset.unitPrice) || 0;
                
                quantityInputs.forEach(input => {
                    const qty = parseInt(input.value) || 0;
                    totalItems += qty;
                    totalAmount += qty * unitPrice;
                });
            });
            
            // Mettre à jour l'affichage
            const totalItemsElement = document.querySelector('.total-items');
            const totalHTElement = document.querySelector('.total-ht');
            const totalTVAElement = document.querySelector('.total-tva');
            const totalTTCElement = document.querySelector('.total-ttc');
            
            if (totalItemsElement) totalItemsElement.textContent = totalItems;
            if (totalHTElement) totalHTElement.textContent = totalAmount.toFixed(2) + ' €';
            if (totalTVAElement) totalTVAElement.textContent = (totalAmount * 0.20).toFixed(2) + ' €';
            if (totalTTCElement) totalTTCElement.textContent = (totalAmount * 1.20).toFixed(2) + ' €';
        }
        
        function validateOrder() {
            let valid = true;
            let errorMessages = [];
            
            document.querySelectorAll('.cart-item').forEach(item => {
                const productName = item.querySelector('h5').textContent;
                const moqText = item.querySelector('.moq-badge').textContent;
                const moq = parseInt(moqText.replace('MOQ: ', '').replace(' unités', ''));
                let totalQty = 0;
                
                item.querySelectorAll('.quantity-input').forEach(input => {
                    totalQty += parseInt(input.value) || 0;
                });
                
                if (totalQty > 0 && totalQty < moq) {
                    valid = false;
                    errorMessages.push(`${productName} : Quantité minimum est ${moq} unités (vous avez commandé ${totalQty})`);
                }
            });
            
            if (!valid) {
                alert('Veuillez corriger les problèmes suivants :\n\n' + errorMessages.join('\n'));
                return false;
            }
            
            const termsCheckbox = document.getElementById('terms');
            if (!termsCheckbox.checked) {
                alert('Veuillez accepter les conditions générales.');
                termsCheckbox.focus();
                return false;
            }
            
            return true;
        }
        
        document.getElementById('order-form').addEventListener('submit', function(e) {
            if (e.submitter.value === 'submit_order') {
                if (!validateOrder()) {
                    e.preventDefault();
                }
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                document.querySelector('button[value="save_draft"]').click();
            }
            
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                document.querySelector('button[value="submit_order"]').click();
            }
        });
    </script>
</body>
</html>