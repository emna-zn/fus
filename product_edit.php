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
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$product_id = intval($_GET['id']);
$stmt = $conn->prepare("
    SELECT p.*, c.name as collection_name 
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
$collections_result = $conn->query("SELECT id, name, season FROM collections ORDER BY name");
$collections = [];
if ($collections_result) {
    while($row = $collections_result->fetch_assoc()) {
        $collections[] = $row;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $collection_id = intval($_POST['collection_id']);
    $reference = trim($_POST['reference']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $fabric_composition = trim($_POST['fabric_composition']);
    $weight_oz = trim($_POST['weight_oz']);
    $available_colors = trim($_POST['available_colors']);
    $available_sizes = trim($_POST['available_sizes']);
    $wash_types = trim($_POST['wash_types']);
    $certifications = trim($_POST['certifications']);
    $moq = intval($_POST['moq']);
    $production_time_days = intval($_POST['production_time_days']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    $errors = [];
    
    if (empty($reference)) {
        $errors[] = "La référence est requise.";
    }
    
    if (empty($name)) {
        $errors[] = "Le nom du produit est requis.";
    }
    
    if (empty($collection_id)) {
        $errors[] = "La collection est requise.";
    }
    
    if ($moq <= 0) {
        $errors[] = "Le MOQ doit être supérieur à 0.";
    }
    
    if ($production_time_days <= 0) {
        $errors[] = "Le temps de production doit être supérieur à 0.";
    }
    $stmt = $conn->prepare("SELECT id FROM products WHERE reference = ? AND id != ?");
    $stmt->bind_param("si", $reference, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Un autre produit avec cette référence existe déjà.";
    }
    $stmt->close();
    
    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE products 
            SET collection_id = ?, reference = ?, name = ?, description = ?, 
                fabric_composition = ?, weight_oz = ?, available_colors = ?, 
                available_sizes = ?, wash_types = ?, certifications = ?, 
                moq = ?, production_time_days = ?, is_active = ? 
            WHERE id = ?
        ");
        
        $stmt->bind_param("isssssssssiiii", 
            $collection_id, $reference, $name, $description, $fabric_composition,
            $weight_oz, $available_colors, $available_sizes, $wash_types,
            $certifications, $moq, $production_time_days, $is_active, $product_id
        );
        
        if ($stmt->execute()) {
            if (!empty($_FILES['new_images']['name'][0])) {
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                            }
                            // Check if product has any main image
                $check_main_stmt = $conn->prepare("SELECT COUNT(*) as count FROM product_images WHERE product_id = ? AND is_main = 1");
                $check_main_stmt->bind_param("i", $product_id);
                $check_main_stmt->execute();
                $check_result = $check_main_stmt->get_result();
                $main_count = $check_result->fetch_assoc()['count'];
                $check_main_stmt->close();
    
    foreach ($_FILES['new_images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['new_images']['error'][$key] === UPLOAD_ERR_OK) {
            $original_name = basename($_FILES['new_images']['name'][$key]);
            $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
            $file_name = uniqid() . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            // Debug: afficher les informations d'upload
            // Validate file is an image
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = mime_content_type($tmp_name);
            
            if (in_array($file_type, $allowed_types)) {
                if (move_uploaded_file($tmp_name, $file_path)) {
                    // Determine if this should be main image
                    $is_main = ($main_count == 0 && $key == 0) ? 1 : 0;
                    
                    $img_stmt = $conn->prepare("
                        INSERT INTO product_images (product_id, image_url, is_main) 
                        VALUES (?, ?, ?)
                    ");
                    $image_url = '/uploads/products/' . $file_name;
                    $img_stmt->bind_param("isi", $product_id, $image_url, $is_main);
                    
                    if ($img_stmt->execute()) {
                        $main_count++;
                    } else {
                        error_log("Image insert error: " . $img_stmt->error);
                    }
                    $img_stmt->close();
                }
            }
        }
    }
}
            
            if (isset($_POST['delete_images'])) {
                foreach ($_POST['delete_images'] as $image_id) {
                    $image_id = intval($image_id);
                    $img_stmt = $conn->prepare("SELECT image_url FROM product_images WHERE id = ?");
                    $img_stmt->bind_param("i", $image_id);
                    $img_stmt->execute();
                    $img_result = $img_stmt->get_result();
                    $image = $img_result->fetch_assoc();
                    $img_stmt->close();
                    if ($image && file_exists('..' . $image['image_url'])) {
                        unlink('..' . $image['image_url']);
                    }
                    $del_stmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
                    $del_stmt->bind_param("i", $image_id);
                    $del_stmt->execute();
                    $del_stmt->close();
                }
            }
            if (isset($_POST['main_image']) && !empty($_POST['main_image'])) {
                $main_image_id = intval($_POST['main_image']);
                $stmt_reset = $conn->prepare("UPDATE product_images SET is_main = 0 WHERE product_id = ?");
                $stmt_reset->bind_param("i", $product_id);
                $stmt_reset->execute();
                $stmt_reset->close();
                $stmt_main = $conn->prepare("UPDATE product_images SET is_main = 1 WHERE id = ? AND product_id = ?");
                $stmt_main->bind_param("ii", $main_image_id, $product_id);
                $stmt_main->execute();
                $stmt_main->close();
            }
            
            $message = "Produit mis à jour avec succès !";
            $message_type = 'success';
            
            // Fermer le statement UPDATE avant d'en créer un nouveau
            $stmt->close();
            
            // Récupérer à nouveau les données du produit avec un NOUVEAU statement
            $stmt2 = $conn->prepare("
                SELECT p.*, c.name as collection_name 
                FROM products p 
                LEFT JOIN collections c ON p.collection_id = c.id 
                WHERE p.id = ?
            ");
            $stmt2->bind_param("i", $product_id);
            $stmt2->execute();
            $result = $stmt2->get_result();
            $product = $result->fetch_assoc();
            $stmt2->close();
            
            // Récupérer à nouveau les images
            $images_stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, upload_date DESC");
            $images_stmt->bind_param("i", $product_id);
            $images_stmt->execute();
            $images_result = $images_stmt->get_result();
            $product_images = [];
            while ($image = $images_result->fetch_assoc()) {
                $product_images[] = $image;
            }
            $images_stmt->close();
            
        } else {
            $message = "Erreur lors de la mise à jour du produit : " . $conn->error;
            $message_type = 'danger';
            $stmt->close();
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Produit - FUS Denim</title>
    
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

        /* Card Modern */
        .card-modern {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
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

        .form-text {
            font-size: 0.85rem;
            color: var(--gray-500);
            margin-top: 0.25rem;
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

        .btn-outline-secondary {
            border: 1px solid var(--gray-300);
            color: var(--gray-600);
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-outline-secondary:hover {
            background: var(--gray-100);
            color: var(--primary);
            text-decoration: none;
        }

        /* Product Info Card */
        .product-info-card {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--gray-200);
        }

        .info-row {
            display: flex;
            margin-bottom: 0.75rem;
        }

        .info-label {
            font-weight: 600;
            color: var(--gray-600);
            min-width: 180px;
            font-size: 0.9rem;
        }

        .info-value {
            color: var(--gray-700);
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.75rem;
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

        /* Image Management */
        .image-dropzone {
            border: 2px dashed var(--gray-300);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            background: var(--gray-50);
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .image-dropzone:hover {
            border-color: var(--accent-1);
            background: var(--gray-100);
        }

        .image-dropzone i {
            font-size: 3rem;
            color: var(--gray-400);
            margin-bottom: 1rem;
        }

        .image-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .preview-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            height: 140px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .preview-item.main-image {
            border-color: var(--accent-1);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-actions {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            display: flex;
            gap: 0.5rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .preview-item:hover .preview-actions {
            opacity: 1;
        }

        .preview-action-btn {
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .preview-action-btn:hover {
            background: var(--accent-1);
            transform: scale(1.1);
        }

        .preview-action-btn.delete-btn:hover {
            background: #EF4444;
        }

        /* Tag Input */
        .tag-input-container {
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            padding: 0.5rem;
            min-height: 46px;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }

        .tag {
            background: var(--accent-1);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tag-remove {
            cursor: pointer;
            font-size: 1rem;
            line-height: 1;
        }

        .tag-input {
            border: none;
            outline: none;
            flex-grow: 1;
            min-width: 100px;
            background: transparent;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--gray-300);
            transition: .4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: var(--accent-4);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        /* Alert */
        .alert-modern {
            border-radius: 12px;
            border: none;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
            color: var(--accent-4);
            border-left: 4px solid var(--accent-4);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
            color: #EF4444;
            border-left: 4px solid #EF4444;
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

            .info-row {
                flex-direction: column;
                margin-bottom: 1rem;
            }

            .info-label {
                min-width: auto;
                margin-bottom: 0.25rem;
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

            .card-modern {
                padding: 1.25rem;
            }

            .image-preview-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }

            .preview-item {
                height: 100px;
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
                <h1>Modifier le produit</h1>
                <p><?php echo htmlspecialchars($product['reference']); ?> - <?php echo htmlspecialchars($product['name']); ?></p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
            </div>
        </div>

        <!-- Message d'alerte -->
        <?php if ($message): ?>
        <div class="alert-modern alert-<?php echo $message_type == 'success' ? 'success' : 'danger'; ?>">
            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <!-- Informations produit -->
        <div class="product-info-card">
            <div class="row">
                <div class="col-md-4">
                    <div class="info-row">
                        <div class="info-label">Référence :</div>
                        <div class="info-value"><?php echo htmlspecialchars($product['reference']); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-row">
                        <div class="info-label">Collection :</div>
                        <div class="info-value"><?php echo htmlspecialchars($product['collection_name']); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-row">
                        <div class="info-label">Statut :</div>
                        <div class="info-value">
                            <?php if ($product['is_active']): ?>
                                <span class="status-badge badge-active">Actif</span>
                            <?php else: ?>
                                <span class="status-badge badge-inactive">Inactif</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="info-row">
                        <div class="info-label">MOQ :</div>
                        <div class="info-value"><?php echo $product['moq']; ?> unités</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-row">
                        <div class="info-label">Temps production :</div>
                        <div class="info-value"><?php echo $product['production_time_days']; ?> jours</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-row">
                        <div class="info-label">Date création :</div>
                        <div class="info-value"><?php echo date('d/m/Y', strtotime($product['created_at'])); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulaire -->
        <form method="POST" action="" enctype="multipart/form-data" id="productForm">
            <input type="hidden" name="update_product" value="1">
            
            <div class="row">
                <!-- Colonne gauche - Informations produit -->
                <div class="col-lg-8">
                    <div class="card-modern">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-info-circle"></i> Informations produit
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <!-- Référence et Nom -->
                            <div class="col-md-6">
                                <label for="reference" class="form-label">Référence *</label>
                                <input type="text" class="form-control" id="reference" name="reference" 
                                       value="<?php echo htmlspecialchars($product['reference']); ?>" 
                                       required placeholder="Ex: FUS-HC-001" pattern="[A-Z0-9\-]+">
                                <div class="form-text">Code unique du produit</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nom du produit *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($product['name']); ?>" 
                                       required placeholder="Ex: Classic Straight Jeans">
                            </div>
                            
                            <!-- Collection -->
                            <div class="col-md-12">
                                <label for="collection_id" class="form-label">Collection *</label>
                                <select class="form-select" id="collection_id" name="collection_id" required>
                                    <option value="">Sélectionnez une collection</option>
                                    <?php foreach($collections as $collection): ?>
                                    <option value="<?php echo $collection['id']; ?>" 
                                            <?php echo $product['collection_id'] == $collection['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($collection['name']); ?> (<?php echo $collection['season']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Description -->
                            <div class="col-12">
                                <label for="description" class="form-label">Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="4" 
                                          required><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>
                            
                            <!-- Composition et Poids -->
                            <div class="col-md-6">
                                <label for="fabric_composition" class="form-label">Composition textile</label>
                                <input type="text" class="form-control" id="fabric_composition" name="fabric_composition" 
                                       value="<?php echo htmlspecialchars($product['fabric_composition']); ?>" 
                                       placeholder="Ex: 98% Cotton, 2% Elastane">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="weight_oz" class="form-label">Poids (oz)</label>
                                <input type="text" class="form-control" id="weight_oz" name="weight_oz" 
                                       value="<?php echo htmlspecialchars($product['weight_oz']); ?>" 
                                       placeholder="Ex: 12.5">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Caractéristiques -->
                    <div class="card-modern">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-sliders-h"></i> Caractéristiques
                            </div>
                        </div>
                        
                        <div class="row g-3">
                            <!-- Tailles disponibles -->
                            <div class="col-md-6">
                                <label class="form-label">Tailles disponibles</label>
                                <div class="tag-input-container" id="sizesContainer">
                                    <div id="sizesTags">
                                        <?php if (!empty($product['available_sizes'])): 
                                            $sizes = explode(',', $product['available_sizes']);
                                            foreach($sizes as $size): 
                                                if (trim($size)): ?>
                                            <div class="tag">
                                                <?php echo trim($size); ?>
                                                <span class="tag-remove" onclick="removeTag(this)">×</span>
                                                <input type="hidden" name="available_sizes[]" value="<?php echo trim($size); ?>">
                                            </div>
                                            <?php endif; endforeach; endif; ?>
                                    </div>
                                    <input type="text" id="sizeInput" class="tag-input" placeholder="Ajouter une taille (Ex: 28)">
                                </div>
                                <input type="hidden" id="available_sizes" name="available_sizes" 
                                       value="<?php echo htmlspecialchars($product['available_sizes']); ?>">
                                <div class="form-text">Saisissez une taille puis appuyez sur Entrée</div>
                            </div>
                            
                            <!-- Couleurs disponibles -->
                            <div class="col-md-6">
                                <label class="form-label">Couleurs disponibles</label>
                                <div class="tag-input-container" id="colorsContainer">
                                    <div id="colorsTags">
                                        <?php if (!empty($product['available_colors'])): 
                                            $colors = explode(',', $product['available_colors']);
                                            foreach($colors as $color): 
                                                if (trim($color)): ?>
                                            <div class="tag">
                                                <?php echo trim($color); ?>
                                                <span class="tag-remove" onclick="removeTag(this)">×</span>
                                                <input type="hidden" name="available_colors[]" value="<?php echo trim($color); ?>">
                                            </div>
                                            <?php endif; endforeach; endif; ?>
                                    </div>
                                    <input type="text" id="colorInput" class="tag-input" placeholder="Ajouter une couleur (Ex: Indigo)">
                                </div>
                                <input type="hidden" id="available_colors" name="available_colors" 
                                       value="<?php echo htmlspecialchars($product['available_colors']); ?>">
                            </div>
                            
                            <!-- Types de lavage -->
                            <div class="col-md-6">
                                <label class="form-label">Types de lavage</label>
                                <div class="tag-input-container" id="washesContainer">
                                    <div id="washesTags">
                                        <?php if (!empty($product['wash_types'])): 
                                            $washes = explode(',', $product['wash_types']);
                                            foreach($washes as $wash): 
                                                if (trim($wash)): ?>
                                            <div class="tag">
                                                <?php echo trim($wash); ?>
                                                <span class="tag-remove" onclick="removeTag(this)">×</span>
                                                <input type="hidden" name="wash_types[]" value="<?php echo trim($wash); ?>">
                                            </div>
                                            <?php endif; endforeach; endif; ?>
                                    </div>
                                    <input type="text" id="washInput" class="tag-input" placeholder="Ajouter un lavage (Ex: Rinse)">
                                </div>
                                <input type="hidden" id="wash_types" name="wash_types" 
                                       value="<?php echo htmlspecialchars($product['wash_types']); ?>">
                            </div>
                            
                            <!-- Certifications -->
                            <div class="col-md-6">
                                <label class="form-label">Certifications</label>
                                <div class="tag-input-container" id="certsContainer">
                                    <div id="certsTags">
                                        <?php if (!empty($product['certifications'])): 
                                            $certs = explode(',', $product['certifications']);
                                            foreach($certs as $cert): 
                                                if (trim($cert)): ?>
                                            <div class="tag">
                                                <?php echo trim($cert); ?>
                                                <span class="tag-remove" onclick="removeTag(this)">×</span>
                                                <input type="hidden" name="certifications[]" value="<?php echo trim($cert); ?>">
                                            </div>
                                            <?php endif; endforeach; endif; ?>
                                    </div>
                                    <input type="text" id="certInput" class="tag-input" placeholder="Ajouter une certification (Ex: OEKO-TEX)">
                                </div>
                                <input type="hidden" id="certifications" name="certifications" 
                                       value="<?php echo htmlspecialchars($product['certifications']); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Colonne droite - Métadonnées et images -->
                <div class="col-lg-4">
                    <!-- Statut et Production -->
                    <div class="card-modern mb-4">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-cogs"></i> Production
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="moq" class="form-label">MOQ (Minimum Order Quantity) *</label>
                            <input type="number" class="form-control" id="moq" name="moq" min="1" 
                                   value="<?php echo $product['moq']; ?>" required>
                            <div class="form-text">Quantité minimale de commande</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="production_time_days" class="form-label">Temps de production (jours) *</label>
                            <input type="number" class="form-control" id="production_time_days" name="production_time_days" min="1" 
                                   value="<?php echo $product['production_time_days']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label d-block">Statut du produit</label>
                            <div class="d-flex align-items-center">
                                <label class="toggle-switch me-3">
                                    <input type="checkbox" name="is_active" value="1" 
                                           <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <div>
                                    <div class="fw-semibold">Produit actif</div>
                                    <small class="text-muted">Visible dans le catalogue</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Images -->
                    <div class="card-modern mb-4">
                        <div class="card-header">
                            <div class="card-title">
                                <i class="fas fa-images"></i> Gestion des images
                            </div>
                        </div>
                        
                        <?php if (!empty($product_images)): ?>
                        <div class="image-preview-grid" id="existingImages">
                            <?php foreach($product_images as $image): ?>
                            <div class="preview-item <?php echo $image['is_main'] ? 'main-image' : ''; ?>" 
                                 data-image-id="<?php echo $image['id']; ?>">
                                <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                                     alt="Image produit <?php echo $product['reference']; ?>">
                                <div class="preview-actions">
                                    <button type="button" class="preview-action-btn set-main-btn" 
                                            title="Définir comme image principale"
                                            onclick="setAsMain(<?php echo $image['id']; ?>)">
                                        <i class="fas <?php echo $image['is_main'] ? 'fa-star' : 'fa-star'; ?>"></i>
                                    </button>
                                    <button type="button" class="preview-action-btn delete-btn" 
                                            title="Supprimer cette image"
                                            onclick="markForDeletion(<?php echo $image['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <input type="hidden" name="main_image" id="mainImageInput" 
                                       value="<?php echo $image['is_main'] ? $image['id'] : ''; ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Zone pour nouvelles images -->
                        <div class="image-dropzone mt-3" id="imageDropzone" onclick="document.getElementById('new_images').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p class="mt-2 mb-1 fw-semibold">Ajouter de nouvelles images</p>
                            <small class="text-muted">Formats: JPG, PNG (max 2MB par image)</small>
                            <input type="file" id="new_images" name="new_images[]" multiple accept="image/*" style="display: none;" 
                                   onchange="previewNewImages(this)">
                        </div>
                        
                        <div class="image-preview-grid mt-3" id="newImagesPreview"></div>
                        
                        <div class="mt-3 p-3 rounded" style="background: rgba(245, 158, 11, 0.1); color: var(--accent-5);">
                            <i class="fas fa-info-circle me-2"></i>
                            <small>La première image sera l'image principale par défaut</small>
                        </div>
                    </div>
                    
                    <!-- Boutons -->
                    <div class="card-modern">
                        <div class="d-flex gap-2">
                            <a href="product_view.php?id=<?php echo $product_id; ?>" class="btn btn-outline-secondary flex-fill">
                                <i class="fas fa-eye me-2"></i>Voir
                            </a>
                            <a href="products.php" class="btn btn-outline-secondary flex-fill">
                                <i class="fas fa-arrow-left me-2"></i>Retour
                            </a>
                        </div>
                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Enregistrer les modifications
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <!-- Footer -->
        <div class="footer">
            <div>
                <i class="fas fa-shield-alt" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Modification produit • <?php echo htmlspecialchars($product['reference']); ?>
            </div>
            <div>
                <span class="system-status">
                    <i class="fas fa-circle"></i> Édition en cours
                </span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Gestion des tags
    function setupTagInput(inputId, containerId, hiddenId) {
        const input = document.getElementById(inputId);
        const container = document.getElementById(containerId);
        const hidden = document.getElementById(hiddenId);
        
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const value = this.value.trim();
                if (value) {
                    addTag(value, container, hidden);
                    this.value = '';
                }
            }
        });
        
        // Permet aussi la saisie par tab
        input.addEventListener('blur', function() {
            const value = this.value.trim();
            if (value) {
                addTag(value, container, hidden);
                this.value = '';
            }
        });
    }
    
    function addTag(value, container, hidden) {
        // Vérifier si le tag existe déjà
        const existingTags = container.querySelectorAll('input[type="hidden"]');
        for (const tag of existingTags) {
            if (tag.value === value) return;
        }
        
        // Créer le tag
        const tagDiv = document.createElement('div');
        tagDiv.className = 'tag';
        tagDiv.innerHTML = `
            ${value}
            <span class="tag-remove" onclick="removeTag(this)">×</span>
            <input type="hidden" name="${hidden.name}[]" value="${value}">
        `;
        container.appendChild(tagDiv);
        
        // Mettre à jour le champ caché
        updateHiddenField(hidden);
    }
    
    function removeTag(element) {
        const tag = element.parentElement;
        tag.remove();
        
        // Mettre à jour le champ caché correspondant
        const hiddenId = tag.parentElement.id.replace('Tags', '');
        const hidden = document.getElementById(hiddenId);
        updateHiddenField(hidden);
    }
    
    function updateHiddenField(hidden) {
        const values = [];
        const tags = hidden.parentElement.querySelectorAll('input[type="hidden"]');
        tags.forEach(tag => values.push(tag.value));
        hidden.value = values.join(',');
    }
    
    // Initialiser les inputs de tags
    setupTagInput('sizeInput', 'sizesTags', 'available_sizes');
    setupTagInput('colorInput', 'colorsTags', 'available_colors');
    setupTagInput('washInput', 'washesTags', 'wash_types');
    setupTagInput('certInput', 'certsTags', 'certifications');
    
    // Gestion du drag & drop pour les images
    const dropzone = document.getElementById('imageDropzone');
    const fileInput = document.getElementById('new_images');
    
    dropzone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = 'var(--accent-1)';
        this.style.backgroundColor = 'var(--gray-100)';
    });
    
    dropzone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.borderColor = 'var(--gray-300)';
        this.style.backgroundColor = 'var(--gray-50)';
    });
    
    dropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = 'var(--gray-300)';
        this.style.backgroundColor = 'var(--gray-50)';
        
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            previewNewImages(fileInput);
        }
    });
    
    // Prévisualisation des nouvelles images
    function previewNewImages(input) {
        const preview = document.getElementById('newImagesPreview');
        preview.innerHTML = '';
        
        for (const file of input.files) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="${file.name}">
                    `;
                    preview.appendChild(div);
                };
                reader.readAsDataURL(file);
            }
        }
    }
    
    // Gestion des images existantes
    let deleteImages = [];
    let mainImageId = <?php echo !empty($product_images) ? $product_images[0]['id'] : 'null'; ?>;
    
    function setAsMain(imageId) {
        // Mettre à jour l'interface
        document.querySelectorAll('.preview-item').forEach(item => {
            item.classList.remove('main-image');
        });
        
        const imageItem = document.querySelector(`.preview-item[data-image-id="${imageId}"]`);
        if (imageItem) {
            imageItem.classList.add('main-image');
        }
        
        // Mettre à jour la valeur cachée
        document.getElementById('mainImageInput').value = imageId;
        mainImageId = imageId;
    }
    
    function markForDeletion(imageId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
            return;
        }
        
        // Ajouter à la liste des images à supprimer
        if (!deleteImages.includes(imageId)) {
            deleteImages.push(imageId);
            
            // Ajouter un champ caché pour la suppression
            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_images[]';
            deleteInput.value = imageId;
            document.getElementById('productForm').appendChild(deleteInput);
            
            // Masquer l'image dans l'interface
            const imageItem = document.querySelector(`.preview-item[data-image-id="${imageId}"]`);
            if (imageItem) {
                imageItem.style.opacity = '0.3';
                imageItem.style.pointerEvents = 'none';
            }
            
            // Si c'était l'image principale, définir une nouvelle image principale
            if (imageId === mainImageId) {
                const remainingImages = document.querySelectorAll('.preview-item:not([style*="opacity: 0.3"])');
                if (remainingImages.length > 0) {
                    const newMainId = remainingImages[0].dataset.imageId;
                    setAsMain(newMainId);
                }
            }
        }
    }
    
    // Validation du formulaire
    document.getElementById('productForm').addEventListener('submit', function(e) {
        const reference = document.getElementById('reference').value;
        if (!/^[A-Z0-9\-]+$/.test(reference)) {
            e.preventDefault();
            alert('La référence ne doit contenir que des lettres majuscules, chiffres et tirets.');
            return;
        }
        
        const moq = parseInt(document.getElementById('moq').value);
        if (moq < 1) {
            e.preventDefault();
            alert('Le MOQ doit être supérieur à 0.');
            return;
        }
    });

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
    </script>
</body>
</html>