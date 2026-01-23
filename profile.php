<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$query = $conn->prepare("SELECT * FROM users WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$user_result = $query->get_result();
$user = $user_result->fetch_assoc();
if ($role === 'client') {
    $stats_query = $conn->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status IN ('received', 'validating', 'confirmed', 'production') THEN 1 ELSE 0 END) as active_orders,
            SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
            SUM(total_value) as total_spent
        FROM orders 
        WHERE client_id = ?
    ");
    $stats_query->bind_param("i", $user_id);
    $stats_query->execute();
    $stats = $stats_query->get_result()->fetch_assoc();
}
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $errors = [];
    
    if (empty($company_name)) {
        $errors[] = "Le nom de l'entreprise est requis.";
    }
    
    if (empty($contact_person)) {
        $errors[] = "Le nom du contact est requis.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email est invalide.";
    }
    $check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check_email->bind_param("si", $email, $user_id);
    $check_email->execute();
    $email_result = $check_email->get_result();
    if ($email_result->num_rows > 0) {
        $errors[] = "Cette adresse email est déjà utilisée.";
    }
    
    if (empty($errors)) {
        $update_query = $conn->prepare("
            UPDATE users 
            SET company_name = ?, contact_person = ?, email = ?, phone = ?, 
                address = ?, city = ?, postal_code = ?, country = ?, website = ? 
            WHERE id = ?
        ");
        
        $update_query->bind_param(
            "sssssssssi",
            $company_name, $contact_person, $email, $phone,
            $address, $city, $postal_code, $country, $website, $user_id
        );
        
        if ($update_query->execute()) {
            $message = "Profil mis à jour avec succès !";
            $message_type = 'success';
            $_SESSION['company_name'] = $company_name;
            $_SESSION['contact_person'] = $contact_person;
            $_SESSION['user_email'] = $email;
            $query = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $query->bind_param("i", $user_id);
            $query->execute();
            $user_result = $query->get_result();
            $user = $user_result->fetch_assoc();
        } else {
            $message = "Erreur lors de la mise à jour : " . $conn->error;
            $message_type = 'danger';
        }
        $update_query->close();
    } else {
        $message = implode("<br>", $errors);
        $message_type = 'danger';
    }
}
$password_message = '';
$password_message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $errors = [];
    
    if (empty($current_password)) {
        $errors[] = "Le mot de passe actuel est requis.";
    }
    
    if (empty($new_password)) {
        $errors[] = "Le nouveau mot de passe est requis.";
    } elseif (strlen($new_password) < 8) {
        $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }
    
    if (empty($errors)) {
        $check_query = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $check_query->bind_param("i", $user_id);
        $check_query->execute();
        $check_result = $check_query->get_result();
        $user_data = $check_result->fetch_assoc();
        
        if (password_verify($current_password, $user_data['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_password = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_password->bind_param("si", $hashed_password, $user_id);
            
            if ($update_password->execute()) {
                $password_message = "Mot de passe changé avec succès !";
                $password_message_type = 'success';
            } else {
                $password_message = "Erreur lors du changement de mot de passe.";
                $password_message_type = 'danger';
            }
            $update_password->close();
        } else {
            $password_message = "Le mot de passe actuel est incorrect.";
            $password_message_type = 'danger';
        }
        $check_query->close();
    } else {
        $password_message = implode("<br>", $errors);
        $password_message_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - FUS Denim</title>
    
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

        /* Profile Container */
        .profile-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }

        /* Profile Sidebar */
        .profile-sidebar {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            height: fit-content;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2rem;
            border: 4px solid var(--white);
            box-shadow: var(--shadow-md);
        }

        .profile-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .profile-company {
            color: var(--gray-500);
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        .profile-role {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: var(--gray-100);
            color: var(--gray-700);
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .profile-stats {
            margin: 2rem 0;
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

        /* Profile Content */
        .profile-content {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
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

        /* Account Status */
        .account-status {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .status-item:last-child {
            margin-bottom: 0;
        }

        .status-label {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .status-value {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-active {
            color: var(--accent-4);
        }

        .status-inactive {
            color: #EF4444;
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

            .profile-container {
                grid-template-columns: 250px 1fr;
                gap: 1.5rem;
            }
        }

        @media (max-width: 992px) {
            .profile-container {
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

            .profile-sidebar,
            .profile-content {
                padding: 1.25rem;
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
        <?php if ($role === 'admin'): ?>
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
                <a href="message.php" class="nav-item">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
            </div>
        <?php else: ?>
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
                <a href="catalog.php" class="nav-item">
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
                <a href="profile.php" class="nav-item active">
                    <i class="fas fa-user-cog"></i>
                    <span>Mon profil</span>
                </a>
                <a href="message.php" class="nav-item">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
            </div>
        <?php endif; ?>

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
                <h1>Mon profil</h1>
                <p>Gérez vos informations personnelles et vos paramètres de compte</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
                <?php if ($role === 'client'): ?>
                    <a href="dashboard_client.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                <?php else: ?>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Profile Container -->
        <div class="profile-container">
            <!-- Profile Sidebar -->
            <div class="profile-sidebar">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($user['contact_person']); ?></div>
                    <div class="profile-company"><?php echo htmlspecialchars($user['company_name']); ?></div>
                    <span class="profile-role"><?php echo $role === 'admin' ? 'Administrateur' : 'Client'; ?></span>
                </div>

                <!-- Statistiques du compte -->
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-label">Date d'inscription</span>
                        <span class="stat-value"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <?php if ($role === 'client' && isset($stats)): ?>
                        <div class="stat-item">
                            <span class="stat-label">Commandes totales</span>
                            <span class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Commandes actives</span>
                            <span class="stat-value"><?php echo $stats['active_orders'] ?? 0; ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Montant total</span>
                            <span class="stat-value"><?php echo number_format($stats['total_spent'] ?? 0, 2, ',', ' '); ?> €</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Statut du compte -->
                <div class="account-status">
                    <div class="status-item">
                        <span class="status-label">Statut du compte</span>
                        <span class="status-value <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $user['is_active'] ? 'Actif' : 'Inactif'; ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Dernière connexion</span>
                        <span class="status-value"><?php echo date('d/m/Y H:i', strtotime($user['last_login'] ?? $user['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="profile-content">
                <!-- Messages d'alerte -->
                <?php if ($message): ?>
                <div class="alert-modern alert-<?php echo $message_type == 'success' ? 'success' : 'danger'; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <?php if ($password_message): ?>
                <div class="alert-modern alert-<?php echo $password_message_type == 'success' ? 'success' : 'danger'; ?>">
                    <i class="fas fa-<?php echo $password_message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo $password_message; ?>
                </div>
                <?php endif; ?>

                <!-- Informations du profil -->
                <h3 class="section-title">
                    <i class="fas fa-user-edit"></i> Informations du profil
                </h3>

                <form method="POST" action="">
                    <div class="row g-3">
                        <!-- Informations entreprise -->
                        <div class="col-md-6">
                            <label for="company_name" class="form-label">Nom de l'entreprise *</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" 
                                   value="<?php echo htmlspecialchars($user['company_name']); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="contact_person" class="form-label">Personne de contact *</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person" 
                                   value="<?php echo htmlspecialchars($user['contact_person']); ?>" required>
                        </div>

                        <!-- Contact -->
                        <div class="col-md-6">
                            <label for="email" class="form-label">Adresse email *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>

                        <!-- Adresse -->
                        <div class="col-md-12">
                            <label for="address" class="form-label">Adresse</label>
                            <input type="text" class="form-control" id="address" name="address" 
                                   value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="city" class="form-label">Ville</label>
                            <input type="text" class="form-control" id="city" name="city" 
                                   value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="postal_code" class="form-label">Code postal</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                   value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="country" class="form-label">Pays</label>
                            <input type="text" class="form-control" id="country" name="country" 
                                   value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>">
                        </div>

                        <!-- Site web -->
                        <div class="col-md-12">
                            <label for="website" class="form-label">Site web</label>
                            <input type="url" class="form-control" id="website" name="website" 
                                   value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>" 
                                   placeholder="https://example.com">
                            <div class="form-text">URL complète avec http:// ou https://</div>
                        </div>

                        <!-- Bouton de soumission -->
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Enregistrer les modifications
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Changement de mot de passe -->
                <hr class="my-4">

                <h3 class="section-title">
                    <i class="fas fa-key"></i> Sécurité du compte
                </h3>

                <form method="POST" action="" id="passwordForm">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="current_password" class="form-label">Mot de passe actuel *</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>

                        <div class="col-md-4">
                            <label for="new_password" class="form-label">Nouveau mot de passe *</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required 
                                   minlength="8">
                            <div class="form-text">Minimum 8 caractères</div>
                        </div>

                        <div class="col-md-4">
                            <label for="confirm_password" class="form-label">Confirmer le mot de passe *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key me-2"></i>Changer le mot de passe
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Informations additionnelles -->
                <hr class="my-4">

                <h3 class="section-title">
                    <i class="fas fa-info-circle"></i> Informations additionnelles
                </h3>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">ID Utilisateur</label>
                            <div class="form-control" style="background-color: var(--gray-50);">
                                <?php echo htmlspecialchars($user['id']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Rôle</label>
                            <div class="form-control" style="background-color: var(--gray-50);">
                                <?php echo $role === 'admin' ? 'Administrateur' : 'Client'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions du compte -->
                <div class="mt-4 pt-4 border-top">
                    <h5 class="mb-3">Actions du compte</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-danger" onclick="requestAccountDeletion()">
                            <i class="fas fa-trash me-2"></i>Demander la suppression du compte
                        </button>
                        <button type="button" class="btn btn-outline-warning" onclick="exportUserData()">
                            <i class="fas fa-download me-2"></i>Exporter mes données
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>
                <i class="fas fa-<?php echo $role === 'admin' ? 'bolt' : 'gem'; ?>" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Gestion du profil
            </div>
            <div>
                <span class="system-status">
                    <i class="fas fa-circle"></i> Compte <?php echo $user['is_active'] ? 'actif' : 'inactif'; ?>
                </span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation du formulaire de changement de mot de passe
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 8 caractères.');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return;
            }
            
            if (!confirm('Êtes-vous sûr de vouloir changer votre mot de passe ?')) {
                e.preventDefault();
            }
        });

        // Validation du formulaire de profil
        const profileForm = document.querySelector('form:not(#passwordForm)');
        profileForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Veuillez entrer une adresse email valide.');
                return;
            }
            
            const website = document.getElementById('website').value;
            if (website && !website.startsWith('http://') && !website.startsWith('https://')) {
                e.preventDefault();
                alert('L\'URL du site web doit commencer par http:// ou https://');
                return;
            }
        });

        // Fonction pour demander la suppression du compte
        function requestAccountDeletion() {
            if (confirm('Êtes-vous sûr de vouloir demander la suppression de votre compte ? Cette action nécessitera une confirmation.')) {
                // Envoyer une requête pour la suppression
                fetch('request_account_deletion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ user_id: <?php echo $user_id; ?> })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Demande de suppression envoyée. Vous recevrez une confirmation par email.');
                    } else {
                        alert('Erreur : ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Une erreur est survenue.');
                });
            }
        }

        // Fonction pour exporter les données utilisateur
        function exportUserData() {
            if (confirm('Voulez-vous exporter toutes vos données personnelles ?')) {
                window.location.href = 'export_user_data.php';
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
        const currentPage = window.location.pathname.split('/').pop() || 'profile.php';
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.getAttribute('href') === currentPage) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Afficher/masquer le mot de passe
        const togglePasswordButtons = document.querySelectorAll('.toggle-password');
        togglePasswordButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        });

        // Générer un mot de passe fort
        function generateStrongPassword() {
            const length = 12;
            const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
            let password = "";
            for (let i = 0; i < length; i++) {
                password += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            return password;
        }

        // Fonction pour générer et insérer un mot de passe fort
        function insertGeneratedPassword() {
            const generatedPassword = generateStrongPassword();
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            newPasswordInput.value = generatedPassword;
            confirmPasswordInput.value = generatedPassword;
            
            alert('Mot de passe fort généré ! Il a été copié dans les deux champs.');
        }

        // Ajouter un bouton pour générer un mot de passe fort
        const passwordForm = document.getElementById('passwordForm');
        const generateButton = document.createElement('button');
        generateButton.type = 'button';
        generateButton.className = 'btn btn-outline-info btn-sm mt-2';
        generateButton.innerHTML = '<i class="fas fa-magic me-1"></i>Générer un mot de passe fort';
        generateButton.onclick = insertGeneratedPassword;
        
        const newPasswordField = document.getElementById('new_password').parentElement;
        newPasswordField.appendChild(generateButton);
    </script>
</body>
</html>