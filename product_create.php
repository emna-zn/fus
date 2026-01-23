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
$collections_result = $conn->query("SELECT id, name, season FROM collections ORDER BY name");
$collections = [];
if ($collections_result) {
    while($row = $collections_result->fetch_assoc()) {
        $collections[] = $row;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $stmt = $conn->prepare("SELECT id FROM products WHERE reference = ?");
    $stmt->bind_param("s", $reference);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Un produit avec cette référence existe déjà.";
    }
    $stmt->close();
    
    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO products (collection_id, reference, name, description, fabric_composition, 
                                 weight_oz, available_colors, available_sizes, wash_types, 
                                 certifications, moq, production_time_days, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("isssssssssiii", 
            $collection_id, $reference, $name, $description, $fabric_composition,
            $weight_oz, $available_colors, $available_sizes, $wash_types,
            $certifications, $moq, $production_time_days, $is_active
        );
        
        if ($stmt->execute()) {
            $product_id = $stmt->insert_id;
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = '../uploads/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $uploaded_images = [];
                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = uniqid() . '_' . basename($_FILES['images']['name'][$key]);
                        $file_path = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($tmp_name, $file_path)) {
                            $uploaded_images[] = $file_path;
                            $is_main = ($key === 0) ? 1 : 0; 
                            $img_stmt = $conn->prepare("
                                INSERT INTO product_images (product_id, image_url, is_main) 
                                VALUES (?, ?, ?)
                            ");
                            $image_url = '/uploads/products/' . $file_name;
                            $img_stmt->bind_param("isi", $product_id, $image_url, $is_main);
                            $img_stmt->execute();
                            $img_stmt->close();
                        }
                    }
                }
            }
            
            $message = "Produit créé avec succès ! ID: " . $product_id;
            $message_type = 'success';
            $_POST = array();
        } else {
            $message = "Erreur lors de la création du produit : " . $conn->error;
            $message_type = 'danger';
        }
        $stmt->close();
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
    <title>Créer un Produit - FUS Denim</title>
    
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

        .back-btn {
            padding: 0.75rem 1.5rem;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            color: var(--gray-600);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn:hover {
            background: var(--gray-100);
            color: var(--primary);
            text-decoration: none;
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
            padding: 0.875rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        /* Image Upload */
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

        .image-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .preview-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            height: 120px;
        }

        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-remove {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.8rem;
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
                <h1>Créer un nouveau produit</h1>
                <p>Ajoutez un nouveau produit denim au catalogue</p>
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

        <form method="POST" action="" enctype="multipart/form-data" id="productForm">
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
                                       value="<?php echo isset($_POST['reference']) ? htmlspecialchars($_POST['reference']) : ''; ?>" 
                                       required placeholder="Ex: FUS-HC-001" pattern="[A-Z0-9\-]+">
                                <div class="form-text">Code unique du produit (lettres majuscules, chiffres et tirets)</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nom du produit *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                       required placeholder="Ex: Classic Straight Jeans">
                            </div>
                            
                            <!-- Collection -->
                            <div class="col-md-12">
                                <label for="collection_id" class="form-label">Collection *</label>
                                <select class="form-select" id="collection_id" name="collection_id" required>
                                    <option value="">Sélectionnez une collection</option>
                                    <?php foreach($collections as $collection): ?>
                                    <option value="<?php echo $collection['id']; ?>" 
                                            <?php echo (isset($_POST['collection_id']) && $_POST['collection_id'] == $collection['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($collection['name']); ?> (<?php echo $collection['season']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Description -->
                            <div class="col-12">
                                <label for="description" class="form-label">Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="4" 
                                          required placeholder="Description détaillée du produit..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>
                            
                            <!-- Composition et Poids -->
                            <div class="col-md-6">
                                <label for="fabric_composition" class="form-label">Composition textile</label>
                                <input type="text" class="form-control" id="fabric_composition" name="fabric_composition" 
                                       value="<?php echo isset($_POST['fabric_composition']) ? htmlspecialchars($_POST['fabric_composition']) : ''; ?>" 
                                       placeholder="Ex: 98% Cotton, 2% Elastane">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="weight_oz" class="form-label">Poids (oz)</label>
                                <input type="text" class="form-control" id="weight_oz" name="weight_oz" 
                                       value="<?php echo isset($_POST['weight_oz']) ? htmlspecialchars($_POST['weight_oz']) : ''; ?>" 
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
                                        <?php if (isset($_POST['available_sizes']) && !empty($_POST['available_sizes'])): 
                                            $sizes = explode(',', $_POST['available_sizes']);
                                            foreach($sizes as $size): ?>
                                            <div class="tag">
                                                <?php echo trim($size); ?>
                                                <span class="tag-remove" onclick="removeTag(this)">×</span>
                                                <input type="hidden" name="available_sizes[]" value="<?php echo trim($size); ?>">
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <input type="text" id="sizeInput" class="tag-input" placeholder="Ajouter une taille (Ex: 28)">
                                </div>
                                <input type="hidden" id="available_sizes" name="available_sizes" 
                                       value="<?php echo isset($_POST['available_sizes']) ? htmlspecialchars($_POST['available_sizes']) : ''; ?>">
                                <div class="form-text">Saisissez une taille puis appuyez sur Entrée</div>
                            </div>
                            
                            <!-- Couleurs disponibles -->
                            <div class="col-md-6">
                                <label class="form-label">Couleurs disponibles</label>
                                <div class="tag-input-container" id="colorsContainer">
                                    <div id="colorsTags">
                                        <?php if (isset($_POST['available_colors']) && !empty($_POST['available_colors'])): 
                                            $colors = explode(',', $_POST['available_colors']);
                                            foreach($colors as $color): ?>
                                            <div class="tag">
                                                <?php echo trim($color); ?>
                                                <span class="tag-remove" onclick="removeTag(this)">×</span>
                                                <input type="hidden" name="available_colors[]" value="<?php echo trim($color); ?>">
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <input type="text" id="colorInput" class="tag-input" placeholder="Ajouter une couleur (Ex: Indigo)">
                                </div>
                                <input type="hidden" id="available_colors" name="available_colors" 
                                       value="<?php echo isset($_POST['available_colors']) ? htmlspecialchars($_POST['available_colors']) : ''; ?>">
                            </div>
                            
                            <!-- Types de lavage -->
                            <div class="col-md-6">
                                <label class="form-label">Types de lavage</label>
                                <div class="tag-input-container" id="washesContainer">
                                    <div id="washesTags">
                                        <?php if (isset($_POST['wash_types']) && !empty($_POST['wash_types'])): 
                                            $washes = explode(',', $_POST['wash_types']);
                                            foreach($washes as $wash): ?>
                                            <div class="tag">
                                                <?php echo trim($wash); ?>
                                                <span class="tag-remove" onclick="removeTag(this)">×</span>
                                                <input type="hidden" name="wash_types[]" value="<?php echo trim($wash); ?>">
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <input type="text" id="washInput" class="tag-input" placeholder="Ajouter un lavage (Ex: Rinse)">
                                </div>
                                <input type="hidden" id="wash_types" name="wash_types" 
                                       value="<?php echo isset($_POST['wash_types']) ? htmlspecialchars($_POST['wash_types']) : ''; ?>">
                            </div>
                            
                            <!-- Certifications -->
                            <div class="col-md-6">
                                <label class="form-label">Certifications</label>
                                <div class="tag-input-container" id="certsContainer">
                                    <div id="certsTags">
                                        <?php if (isset($_POST['certifications']) && !empty($_POST['certifications'])): 
                                            $certs = explode(',', $_POST['certifications']);
                                            foreach($certs as $cert): ?>
                                            <div class="tag">
                                                <?php echo trim($cert); ?>
                                                <span class="tag-remove" onclick="removeTag(this)">×</span>
                                                <input type="hidden" name="certifications[]" value="<?php echo trim($cert); ?>">
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <input type="text" id="certInput" class="tag-input" placeholder="Ajouter une certification (Ex: OEKO-TEX)">
                                </div>
                                <input type="hidden" id="certifications" name="certifications" 
                                       value="<?php echo isset($_POST['certifications']) ? htmlspecialchars($_POST['certifications']) : ''; ?>">
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
                                   value="<?php echo isset($_POST['moq']) ? $_POST['moq'] : '100'; ?>" required>
                            <div class="form-text">Quantité minimale de commande</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="production_time_days" class="form-label">Temps de production (jours) *</label>
                            <input type="number" class="form-control" id="production_time_days" name="production_time_days" min="1" 
                                   value="<?php echo isset($_POST['production_time_days']) ? $_POST['production_time_days'] : '45'; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label d-block">Statut du produit</label>
                            <div class="d-flex align-items-center">
                                <label class="toggle-switch me-3">
                                    <input type="checkbox" name="is_active" value="1" 
                                           <?php echo (isset($_POST['is_active']) && $_POST['is_active']) ? 'checked' : 'checked'; ?>>
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
                                <i class="fas fa-images"></i> Images du produit
                            </div>
                        </div>
                        
                        <div class="image-dropzone" id="imageDropzone" onclick="document.getElementById('images').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p class="mt-2 mb-1 fw-semibold">Glissez-déposez ou cliquez pour ajouter des images</p>
                            <small class="text-muted">Formats: JPG, PNG (max 2MB par image)</small>
                            <input type="file" id="images" name="images[]" multiple accept="image/*" style="display: none;" 
                                   onchange="previewImages(this)">
                        </div>
                        
                        <div class="image-preview" id="imagePreview"></div>
                        
                        <div class="mt-3 p-3 rounded" style="background: rgba(245, 158, 11, 0.1); color: var(--accent-5);">
                            <i class="fas fa-info-circle me-2"></i>
                            <small>La première image sera l'image principale du produit</small>
                        </div>
                    </div>
                    
                    <!-- Boutons -->
                    <div class="card-modern">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check-circle me-2"></i>Créer le produit
                            </button>
                            <a href="products.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times-circle me-2"></i>Annuler
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <!-- Footer -->
        <div class="footer">
            <div>
                <i class="fas fa-shield-alt" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Nouveau produit • Création
            </div>
            <div>
                <span class="system-status">
                    <i class="fas fa-circle"></i> Formulaire actif
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
    const fileInput = document.getElementById('images');
    
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
            previewImages(fileInput);
        }
    });
    
    // Prévisualisation des images
    function previewImages(input) {
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';
        
        for (const file of input.files) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="${file.name}">
                        <button type="button" class="preview-remove" onclick="removeImage(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    preview.appendChild(div);
                };
                reader.readAsDataURL(file);
            }
        }
    }
    
    function removeImage(button) {
        const item = button.parentElement;
        item.remove();
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