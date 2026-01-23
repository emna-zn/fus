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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $company_name = trim($_POST['company_name']);
    $country = trim($_POST['country']);
    $contact_person = trim($_POST['contact_person']);
    $phone = trim($_POST['phone']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $send_credentials = isset($_POST['send_credentials']) ? 1 : 0;
    
    $errors = [];
        if (empty($email)) {
        $errors[] = "L'email est requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Un compte avec cet email existe déjà.";
        }
        $stmt->close();
    }

    if (empty($password)) {
        $errors[] = "Le mot de passe est requis.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }
    if (empty($company_name)) {
        $errors[] = "Le nom de la société est requis.";
    }
    if (empty($contact_person)) {
        $errors[] = "Le nom du contact est requis.";
    }
    if (empty($country)) {
        $errors[] = "Le pays est requis.";
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO users (email, password, company_name, country, contact_person, phone, role, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, 'client', ?)
        ");
        
        $stmt->bind_param("ssssssi", 
            $email, 
            $hashed_password, 
            $company_name, 
            $country, 
            $contact_person, 
            $phone, 
            $is_active
        );
        
        if ($stmt->execute()) {
            $client_id = $stmt->insert_id;
            
            if ($send_credentials) {
                $credentials_sent = "Les identifiants ont été envoyés par email.";
            } else {
                $credentials_sent = "Les identifiants n'ont pas été envoyés.";
            }
            
            $message = "Client créé avec succès ! ID: " . $client_id . " " . $credentials_sent;
            $message_type = 'success';
            
            $_POST = array();
        } else {
            $message = "Erreur lors de la création du client : " . $conn->error;
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
    <title>Créer un Client - FUS Denim</title>
    
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

        .nav-badge {
            margin-left: auto;
            background: linear-gradient(135deg, var(--accent-3), var(--accent-1));
            color: var(--white);
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
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

        .card-action {
            padding: 0.5rem 1rem;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            color: var(--accent-1);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .card-action:hover {
            background: var(--accent-1);
            color: var(--white);
            border-color: var(--accent-1);
        }

        /* Form Styling */
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

        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1.5rem;
            padding-left: 1rem;
            border-left: 4px solid var(--accent-1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--accent-1);
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label.required::after {
            content: '*';
            color: #EF4444;
            margin-left: 0.25rem;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid var(--gray-300);
            padding: 0.875rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-1);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .form-text {
            font-size: 0.85rem;
            color: var(--gray-500);
            margin-top: 0.5rem;
        }

        /* Switch Toggle */
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 32px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
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

        .slider:before {
            position: absolute;
            content: "";
            height: 24px;
            width: 24px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--accent-4);
        }

        input:checked + .slider:before {
            transform: translateX(28px);
        }

        /* Password Strength */
        .password-strength {
            height: 6px;
            background-color: var(--gray-200);
            border-radius: 3px;
            margin-top: 8px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 3px;
        }

        .strength-weak { background-color: #EF4444; }
        .strength-medium { background-color: var(--accent-5); }
        .strength-strong { background-color: var(--accent-4); }

        /* Toggle Item */
        .toggle-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: 10px;
            border: 1px solid var(--gray-200);
        }

        .toggle-label {
            flex: 1;
        }

        .toggle-label strong {
            display: block;
            margin-bottom: 0.25rem;
        }

        .toggle-label small {
            color: var(--gray-500);
            font-size: 0.85rem;
        }

        /* Alert Modern */
        .alert-modern {
            border-radius: 12px;
            border: 1px solid;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
        }

        .alert-modern.alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.2);
            color: var(--accent-4);
        }

        .alert-modern.alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.2);
            color: #EF4444;
        }

        .alert-modern.alert-info {
            background: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.2);
            color: var(--accent-1);
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-100);
            margin-top: 2rem;
        }

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

        /* Generate Password Button */
        .btn-generate {
            background: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .btn-generate:hover {
            background: var(--accent-1);
            color: var(--white);
            border-color: var(--accent-1);
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

        .card-modern {
            animation: slideInUp 0.5s ease-out forwards;
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

            .form-actions {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .form-actions > * {
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
            <i class="fas fa-bolt"></i>
            <h2>FUS Admin</h2>
        </div>

        <div class="nav-section">
            <div class="nav-label">Menu Principal</div>
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-chart-line"></i>
                <span>Tableau de bord</span>
            </a>
            <a href="clients.php" class="nav-item active">
                <i class="fas fa-users"></i>
                <span>Clients</span>
            </a>
            <a href="orders.php" class="nav-item">
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
                <h1>Créer un nouveau client</h1>
                <p>Ajoutez un nouveau client au portail B2B</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
            </div>
        </div>

        <!-- Message d'alerte -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-modern alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-3"></i>
                <div><?php echo $message; ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <div class="card-modern">
            <form method="POST" action="" id="clientForm">
                <!-- Section : Informations de connexion -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-key"></i> Informations de connexion
                    </h3>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label required">
                                <i class="fas fa-envelope"></i> Email
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   required placeholder="client@entreprise.com">
                            <div class="form-text">L'adresse email servira d'identifiant de connexion</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="password" class="form-label required">
                                <i class="fas fa-lock"></i> Mot de passe
                            </label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   required placeholder="Minimum 6 caractères">
                            <div class="password-strength">
                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                            </div>
                            <div class="form-text" id="passwordStrengthText"></div>
                            <button type="button" class="btn-generate" onclick="generatePassword()">
                                <i class="fas fa-random"></i> Générer un mot de passe
                            </button>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label required">
                                <i class="fas fa-lock"></i> Confirmer le mot de passe
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   required placeholder="Répétez le mot de passe">
                            <div class="form-text" id="passwordMatchText"></div>
                        </div>
                    </div>
                </div>

                <!-- Section : Informations société -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-building"></i> Informations société
                    </h3>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="company_name" class="form-label required">
                                <i class="fas fa-briefcase"></i> Nom de la société
                            </label>
                            <input type="text" class="form-control" id="company_name" name="company_name" 
                                   value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>" 
                                   required placeholder="Ex: Paris Fashion House">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="country" class="form-label required">
                                <i class="fas fa-globe"></i> Pays
                            </label>
                            <select class="form-select" id="country" name="country" required>
                                <option value="">Sélectionnez un pays</option>
                                <option value="France" <?php echo (isset($_POST['country']) && $_POST['country'] == 'France') ? 'selected' : ''; ?>>France</option>
                                <option value="Allemagne" <?php echo (isset($_POST['country']) && $_POST['country'] == 'Allemagne') ? 'selected' : ''; ?>>Allemagne</option>
                                <option value="Royaume-Uni" <?php echo (isset($_POST['country']) && $_POST['country'] == 'Royaume-Uni') ? 'selected' : ''; ?>>Royaume-Uni</option>
                                <option value="Espagne" <?php echo (isset($_POST['country']) && $_POST['country'] == 'Espagne') ? 'selected' : ''; ?>>Espagne</option>
                                <option value="Italie" <?php echo (isset($_POST['country']) && $_POST['country'] == 'Italie') ? 'selected' : ''; ?>>Italie</option>
                                <option value="Belgique" <?php echo (isset($_POST['country']) && $_POST['country'] == 'Belgique') ? 'selected' : ''; ?>>Belgique</option>
                                <option value="Suisse" <?php echo (isset($_POST['country']) && $_POST['country'] == 'Suisse') ? 'selected' : ''; ?>>Suisse</option>
                                <option value="Pays-Bas" <?php echo (isset($_POST['country']) && $_POST['country'] == 'Pays-Bas') ? 'selected' : ''; ?>>Pays-Bas</option>
                                <option value="Suède" <?php echo (isset($_POST['country']) && $_POST['country'] == 'Suède') ? 'selected' : ''; ?>>Suède</option>
                                <option value="Danemark" <?php echo (isset($_POST['country']) && $_POST['country'] == 'Danemark') ? 'selected' : ''; ?>>Danemark</option>
                                <option value="Norvège" <?php echo (isset($_POST['country']) && $_POST['country'] == 'Norvège') ? 'selected' : ''; ?>>Norvège</option>
                                <option value="États-Unis" <?php echo (isset($_POST['country']) && $_POST['country'] == 'États-Unis') ? 'selected' : ''; ?>>États-Unis</option>
                                <option value="Canada" <?php echo (isset($_POST['country']) && $_POST['country'] == 'Canada') ? 'selected' : ''; ?>>Canada</option>
                                <option value="Japon" <?php echo (isset($_POST['country']) && $_POST['country'] == 'Japon') ? 'selected' : ''; ?>>Japon</option>
                                <option value="Corée du Sud" <?php echo (isset($_POST['country']) && $_POST['country'] == 'Corée du Sud') ? 'selected' : ''; ?>>Corée du Sud</option>
                                <option value="Australie" <?php echo (isset($_POST['country']) && $_POST['country'] == 'Australie') ? 'selected' : ''; ?>>Australie</option>
                                <option value="Autre" <?php echo (isset($_POST['country']) && $_POST['country'] == 'Autre') ? 'selected' : ''; ?>>Autre</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="contact_person" class="form-label required">
                                <i class="fas fa-user"></i> Personne contact
                            </label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person" 
                                   value="<?php echo isset($_POST['contact_person']) ? htmlspecialchars($_POST['contact_person']) : ''; ?>" 
                                   required placeholder="Ex: Jean Dupont">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="phone" class="form-label">
                                <i class="fas fa-phone"></i> Téléphone
                            </label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                   placeholder="Ex: +33 1 23 45 67 89">
                            <div class="form-text">Format international recommandé</div>
                        </div>
                    </div>
                </div>

                <!-- Section : Paramètres du compte -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-cog"></i> Paramètres du compte
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="toggle-item">
                                <div class="toggle-label">
                                    <strong>Compte actif</strong>
                                    <small>Le client pourra se connecter immédiatement</small>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="is_active" value="1" 
                                           <?php echo (isset($_POST['is_active']) && $_POST['is_active']) ? 'checked' : 'checked'; ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            
                            <div class="toggle-item">
                                <div class="toggle-label">
                                    <strong>Envoyer les identifiants</strong>
                                    <small>Envoyer un email avec les identifiants</small>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="send_credentials" value="1" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="notes" class="form-label">
                                <i class="fas fa-sticky-note"></i> Notes internes (optionnel)
                            </label>
                            <textarea class="form-control" id="notes" name="notes" rows="4" 
                                      placeholder="Notes internes sur ce client..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                            <div class="form-text">Ces notes ne sont visibles que par les administrateurs</div>
                        </div>
                    </div>
                </div>

                <!-- Actions du formulaire -->
                <div class="form-actions">
                    <a href="clients.php" class="btn-outline-modern">
                        <i class="fas fa-arrow-left me-2"></i>Retour aux clients
                    </a>
                    <button type="submit" class="btn-modern">
                        <i class="fas fa-user-plus me-2"></i>Créer le client
                    </button>
                </div>
            </form>
        </div>

        <!-- Informations -->
        <div class="alert alert-info alert-modern mt-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle me-3" style="font-size: 1.2rem;"></i>
                <div>
                    <strong>Informations importantes :</strong>
                    <ul class="mb-0 mt-2">
                        <li>Un email de confirmation sera envoyé si l'option est cochée</li>
                        <li>Les clients actifs peuvent immédiatement accéder au portail</li>
                        <li>Les clients inactifs ne peuvent pas se connecter</li>
                        <li>Le mot de passe doit contenir au moins 6 caractères</li>
                        <li>Le client pourra modifier son mot de passe après la première connexion</li>
                    </ul>
                </div>
            </div>
        </div>

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

        // Active nav item based on current page
        const currentPage = window.location.pathname.split('/').pop() || 'dashboard.php';
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.getAttribute('href') === currentPage) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Vérification de la force du mot de passe
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            const strengthText = document.getElementById('passwordStrengthText');
            
            let strength = 0;
            let text = '';
            let barColor = '';
            
            // Longueur
            if (password.length >= 6) strength += 1;
            if (password.length >= 8) strength += 1;
            
            // Complexité
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Déterminer le niveau
            if (password.length === 0) {
                text = '';
                barColor = '';
                strengthBar.style.width = '0%';
            } else if (strength <= 2) {
                text = 'Faible';
                barColor = 'strength-weak';
                strengthBar.style.width = '33%';
            } else if (strength <= 4) {
                text = 'Moyen';
                barColor = 'strength-medium';
                strengthBar.style.width = '66%';
            } else {
                text = 'Fort';
                barColor = 'strength-strong';
                strengthBar.style.width = '100%';
            }
            
            // Mettre à jour l'affichage
            strengthBar.className = 'password-strength-bar ' + barColor;
            strengthText.textContent = text;
            strengthText.className = 'form-text ' + 
                (barColor === 'strength-weak' ? 'text-danger' : 
                 barColor === 'strength-medium' ? 'text-warning' : 'text-success');
        });
        
        // Vérification de la correspondance des mots de passe
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('passwordMatchText');
            
            if (confirmPassword.length === 0) {
                matchText.textContent = '';
                matchText.className = 'form-text';
            } else if (password === confirmPassword) {
                matchText.textContent = '✓ Les mots de passe correspondent';
                matchText.className = 'form-text text-success';
            } else {
                matchText.textContent = '✗ Les mots de passe ne correspondent pas';
                matchText.className = 'form-text text-danger';
            }
        }
        
        document.getElementById('password').addEventListener('input', checkPasswordMatch);
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
        
        // Validation du formulaire
        document.getElementById('clientForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 6 caractères.');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return;
            }
            
            // Vérifier l'email
            const email = document.getElementById('email').value;
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                e.preventDefault();
                alert('Veuillez entrer une adresse email valide.');
                return;
            }
        });
        
        // Générer un mot de passe aléatoire
        function generatePassword() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
            let password = '';
            
            // Assurer au moins une majuscule, un chiffre et un caractère spécial
            password += chars[Math.floor(Math.random() * 26)]; // Majuscule
            password += chars[26 + Math.floor(Math.random() * 26)]; // Minuscule
            password += chars[52 + Math.floor(Math.random() * 10)]; // Chiffre
            password += chars[62 + Math.floor(Math.random() * 8)]; // Caractère spécial
            
            // Ajouter 4 caractères aléatoires supplémentaires
            for (let i = 0; i < 4; i++) {
                password += chars[Math.floor(Math.random() * chars.length)];
            }
            
            // Mélanger le mot de passe
            password = password.split('').sort(() => Math.random() - 0.5).join('');
            
            document.getElementById('password').value = password;
            document.getElementById('confirm_password').value = password;
            
            // Déclencher les événements pour mettre à jour les indicateurs
            document.getElementById('password').dispatchEvent(new Event('input'));
            checkPasswordMatch();
        }
    </script>
</body>
</html>