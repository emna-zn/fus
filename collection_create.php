<?php
session_start();
require_once 'connexion.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
$database = new Database();
$conn = $database->getConnection();

$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $season = trim($_POST['season']);
    $description = trim($_POST['description']);
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Le nom de la collection est requis.";
    }
    
    if (empty($season)) {
        $errors[] = "La saison est requise.";
    }
    
    if (empty($description)) {
        $errors[] = "La description est requise.";
    }
    $stmt = $conn->prepare("SELECT id FROM collections WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Une collection avec ce nom existe déjà.";
    }
    $stmt->close();
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO collections (name, season, description, is_public) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $season, $description, $is_public);
        
        if ($stmt->execute()) {
            $message = "Collection créée avec succès !";
            $message_type = 'success';
            $name = $season = $description = '';
            $is_public = 0;
        } else {
            $message = "Erreur lors de la création de la collection : " . $conn->error;
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
    <title>Créer une Collection - FUS Denim</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #111827;
            --secondary: #1F2937;
            --accent-1: #6366F1;
            --accent-2: #A855F7;
            --accent-3: #EC4899;
            --accent-4: #10B981;
            --accent-5: #F59E0B;
            --white: #FFFFFF;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Space Grotesk', sans-serif;
            background: linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%);
            color: var(--gray-900);
            line-height: 1.6;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Syne', sans-serif;
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
            border-right: 1px solid rgba(255, 255, 255, 0.05);
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
            font-family: 'Syne', sans-serif;
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
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
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

        /* Page Header */
        .page-header {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 25px rgba(0,0,0,0.05);
            border: 1px solid var(--gray-100);
            border-top: 4px solid transparent;
            border-image: linear-gradient(90deg, var(--accent-1), var(--accent-2));
            border-image-slice: 1;
        }

        .header-title h1 {
            font-family: 'Syne', sans-serif;
            font-size: 1.8rem;
            color: var(--primary);
            margin: 0;
        }

        .header-title p {
            color: var(--gray-500);
            margin: 0.25rem 0 0 0;
            font-size: 0.9rem;
        }

        /* Form Card */
        .form-card {
            background: var(--white);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-100);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .form-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-color: var(--gray-200);
        }

        /* Form Elements */
        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 1px solid var(--gray-300);
            border-radius: 10px;
            padding: 0.875rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-1);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.1);
        }

        .form-text {
            font-size: 0.85em;
            color: var(--gray-500);
            margin-top: 0.25rem;
        }

        /* Buttons */
        .btn-modern {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: var(--white);
            border: none;
            padding: 0.875rem 2rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3);
            position: relative;
            overflow: hidden;
            font-size: 0.95rem;
        }
        
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.4);
            color: var(--white);
        }
        
        .btn-outline-modern {
            color: var(--accent-1);
            border: 2px solid var(--accent-1);
            background: transparent;
            padding: 0.875rem 2rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            font-size: 0.95rem;
        }
        
        .btn-outline-modern:hover {
            color: var(--white);
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
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
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        /* Info Alert */
        .info-alert {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(168, 85, 247, 0.05));
            border: 1px solid var(--accent-1);
            border-left: 4px solid var(--accent-1);
            border-radius: 12px;
            padding: 1.5rem;
        }

        /* Footer */
        .form-footer {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-200);
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        /* Alert */
        .alert-modern {
            border-radius: 12px;
            border: 1px solid;
            padding: 1.25rem 1.5rem;
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

            .page-header {
                padding: 1.5rem;
            }

            .form-card {
                padding: 1.5rem;
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

            .form-card {
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
            <a href="collections.php" class="nav-item active">
                <i class="fas fa-layer-group"></i>
                <span>Collections</span>
            </a>
            <a href="products.php" class="nav-item">
                <i class="fas fa-box"></i>
                <span>Produits</span>
            </a>
            <a href="orders.php" class="nav-item">
                <i class="fas fa-shopping-bag"></i>
                <span>Commandes</span>
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
            <a href="settings.php" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Paramètres</span>
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
            <a href="../login.php?action=logout" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="header-title">Créer une nouvelle collection</h1>
                    <p class="header-text">Ajoutez une nouvelle collection de produits denim</p>
                </div>
                <a href="collections.php" class="btn btn-outline-modern">
                    <i class="fas fa-arrow-left me-2"></i>Retour aux collections
                </a>
            </div>
        </div>

        <!-- Message d'alerte -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-modern alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-3"></i>
                <div><?php echo $message; ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <div class="form-card">
            <form method="POST" action="">
                <div class="row g-4">
                    <!-- Nom de la collection -->
                    <div class="col-md-12">
                        <label for="name" class="form-label">Nom de la collection *</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" 
                               required placeholder="Ex: Heritage Collection 2024">
                        <div class="form-text">Nom unique qui identifiera la collection</div>
                    </div>
                    
                    <!-- Saison -->
                    <div class="col-md-6">
                        <label for="season" class="form-label">Saison *</label>
                        <select class="form-select" id="season" name="season" required>
                            <option value="">Sélectionnez une saison</option>
                            <option value="SS2024" <?php echo (isset($season) && $season == 'SS2024') ? 'selected' : ''; ?>>Printemps/Été 2024 (SS2024)</option>
                            <option value="AW2024" <?php echo (isset($season) && $season == 'AW2024') ? 'selected' : ''; ?>>Automne/Hiver 2024 (AW2024)</option>
                            <option value="SS2025" <?php echo (isset($season) && $season == 'SS2025') ? 'selected' : ''; ?>>Printemps/Été 2025 (SS2025)</option>
                            <option value="AW2025" <?php echo (isset($season) && $season == 'AW2025') ? 'selected' : ''; ?>>Automne/Hiver 2025 (AW2025)</option>
                            <option value="Capsule" <?php echo (isset($season) && $season == 'Capsule') ? 'selected' : ''; ?>>Collection capsule</option>
                            <option value="Permanent" <?php echo (isset($season) && $season == 'Permanent') ? 'selected' : ''; ?>>Collection permanente</option>
                        </select>
                    </div>
                    
                    <!-- Visibilité -->
                    <div class="col-md-6">
                        <label class="form-label d-block">Visibilité</label>
                        <div class="d-flex align-items-center">
                            <label class="toggle-switch me-3">
                                <input type="checkbox" name="is_public" value="1" 
                                       <?php echo (isset($is_public) && $is_public) ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <div>
                                <div class="fw-semibold">Collection publique</div>
                                <small class="text-muted">Visible par tous les clients</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="col-12">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="5" 
                                  required placeholder="Décrivez la collection, son inspiration, ses caractéristiques..."><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                        <div class="form-text">Description détaillée de la collection (visible par les clients)</div>
                        <div id="charCounter" class="form-text text-end mt-1">0 / 1000 caractères</div>
                    </div>
                    
                    <!-- Boutons -->
                    <div class="col-12">
                        <hr class="my-4">
                        <div class="d-flex justify-content-between">
                            <a href="collections.php" class="btn btn-outline-modern">
                                <i class="fas fa-times-circle me-2"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-modern">
                                <i class="fas fa-check-circle me-2"></i>Créer la collection
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Aide -->
        <div class="info-alert">
            <div class="d-flex">
                <i class="fas fa-info-circle me-3" style="color: var(--accent-1); font-size: 1.25rem;"></i>
                <div>
                    <strong>Conseils pour créer une collection :</strong>
                    <ul class="mb-0 mt-2" style="color: var(--gray-600);">
                        <li>Choisissez un nom clair et évocateur</li>
                        <li>Les collections publiques sont visibles par tous les clients</li>
                        <li>Les collections privées sont réservées à certains clients spécifiques</li>
                        <li>Une fois créée, vous pourrez y ajouter des produits</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="form-footer">
            <div class="row">
                <div class="col-md-6">
                    <p>
                        <i class="fas fa-shield-alt" style="color: var(--accent-1);"></i>
                        <strong>FUS Denim</strong> - Création de collection
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>
                        <span style="color: var(--accent-4); font-weight: 600;">
                            <i class="fas fa-circle"></i> Formulaire actif
                        </span>
                    </p>
                </div>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Compteur de caractères pour la description
    const descriptionTextarea = document.getElementById('description');
    const charCounter = document.getElementById('charCounter');
    
    descriptionTextarea.addEventListener('input', function() {
        const charCount = this.value.length;
        const maxChars = 1000;
        
        charCounter.textContent = `${charCount} / ${maxChars} caractères`;
        
        if (charCount > maxChars) {
            charCounter.classList.add('text-danger');
        } else {
            charCounter.classList.remove('text-danger');
        }
    });

    // Initialiser le compteur
    descriptionTextarea.dispatchEvent(new Event('input'));

    // Validation du formulaire
    document.querySelector('form').addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        const description = document.getElementById('description').value.trim();
        
        if (!name) {
            e.preventDefault();
            alert('Le nom de la collection est requis.');
            return;
        }
        
        if (!description) {
            e.preventDefault();
            alert('La description de la collection est requise.');
            return;
        }
        
        // Montrer un indicateur de chargement
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Création en cours...';
        submitBtn.disabled = true;
    });

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