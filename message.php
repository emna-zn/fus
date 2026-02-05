<?php
// messages.php - CÔTÉ CLIENT
session_start();
require_once 'connexion.php';

// Vérification de l'authentification
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$client_id = $_SESSION['user_id'];
$company_name = $_SESSION['company_name'];
$contact_person = $_SESSION['contact_person'];

// DEBUG: Afficher les infos de session
error_log("=== DEBUG MESSAGES.PHP ===");
error_log("Client ID: " . $client_id);
error_log("Company: " . $company_name);
error_log("Contact: " . $contact_person);

// Récupérer l'email de l'utilisateur depuis la base de données
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_email = $user['email'];
$stmt->close();

error_log("User Email: " . $user_email);

// Variables pour la pagination et le filtrage
$messages_per_page = 15;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $messages_per_page;
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Construire la requête pour récupérer les messages envoyés par ce client
$query_conditions = "WHERE client_id = ?";
$query_params = [$client_id];
$query_types = "i";

if ($filter_type === 'unread') {
    // Pour le client, "unread" signifie les messages auxquels l'admin n'a pas encore répondu
    $query_conditions .= " AND admin_replied = 0";
} elseif ($filter_type === 'read') {
    $query_conditions .= " AND admin_replied = 1";
}

if (!empty($search_query)) {
    $query_conditions .= " AND (subject LIKE ? OR message LIKE ?)";
    $query_params[] = "%$search_query%";
    $query_params[] = "%$search_query%";
    $query_types .= "ss";
}

// Compter le nombre total de messages envoyés par ce client
$count_sql = "SELECT COUNT(*) as total FROM contact_messages $query_conditions";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param($query_types, ...$query_params);
$stmt->execute();
$count_result = $stmt->get_result();
$total_messages = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_messages / $messages_per_page);
$stmt->close();

// Récupérer les messages envoyés par ce client
$messages_sql = "SELECT * FROM contact_messages 
                 $query_conditions 
                 ORDER BY submitted_at DESC 
                 LIMIT ? OFFSET ?";

$query_params[] = $messages_per_page;
$query_params[] = $offset;
$query_types .= "ii";

$stmt = $conn->prepare($messages_sql);
$stmt->bind_param($query_types, ...$query_params);
$stmt->execute();
$messages_result = $stmt->get_result();
$messages = [];
while ($row = $messages_result->fetch_assoc()) {
    // Ces messages sont ceux que le client a envoyés à l'admin
    $row['message_type'] = 'sent';
    $row['sender_name'] = 'Vous';
    $row['sender_info'] = $company_name;
    $row['recipient'] = 'Administration FUS Denim';
    
    $messages[] = $row;
}
$stmt->close();

// Traitement de l'envoi d'un nouveau message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    error_log("=== FORM SUBMITTED ===");
    error_log("Subject: " . $subject);
    error_log("Message: " . substr($message, 0, 50) . "...");
    error_log("Client ID: " . $client_id);
    error_log("Contact Person: " . $contact_person);
    error_log("Email: " . $user_email);
    
    if (!empty($subject) && !empty($message)) {
        // DEBUG: Afficher la requête SQL
        error_log("SQL INSERT: INSERT INTO contact_messages (client_id, name, email, subject, message, is_read, submitted_at) VALUES ($client_id, '$contact_person', '$user_email', '$subject', '$message', 0, NOW())");
        
        // Vérifier si la table a le champ client_id
        $check_table = $conn->query("DESCRIBE contact_messages");
        $fields = [];
        while($field = $check_table->fetch_assoc()) {
            $fields[] = $field['Field'];
            error_log("Field: " . $field['Field'] . " - Type: " . $field['Type']);
        }
        
        // Essayer deux méthodes d'insertion
        try {
            // Méthode 1: Avec client_id
            $insert_stmt = $conn->prepare("INSERT INTO contact_messages 
                                          (client_id, name, email, subject, message, is_read, submitted_at) 
                                          VALUES (?, ?, ?, ?, ?, 0, NOW())");
            
            if ($insert_stmt === false) {
                error_log("Prepare failed: " . $conn->error);
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $insert_stmt->bind_param(
                "issss", 
                $client_id, 
                $contact_person, 
                $user_email, 
                $subject, 
                $message
            );
            
            $result = $insert_stmt->execute();
            
            if ($result) {
                error_log("Insert successful! ID: " . $conn->insert_id);
                $success_message = "Message envoyé à l'administration avec succès!";
                // Redirection pour éviter le re-soumission
                header("Location: messages.php?sent=1");
                exit();
            } else {
                error_log("Execute failed: " . $insert_stmt->error);
                $error_message = "Erreur lors de l'envoi du message. Code: " . $insert_stmt->errno . " - " . $insert_stmt->error;
                
                // Essayer méthode 2: Sans client_id (pour compatibilité)
                error_log("Trying alternative method without client_id...");
                $insert_stmt2 = $conn->prepare("INSERT INTO contact_messages 
                                              (name, email, subject, message, is_read, submitted_at) 
                                              VALUES (?, ?, ?, ?, 0, NOW())");
                
                if ($insert_stmt2) {
                    $insert_stmt2->bind_param(
                        "ssss", 
                        $contact_person, 
                        $user_email, 
                        $subject, 
                        $message
                    );
                    
                    if ($insert_stmt2->execute()) {
                        error_log("Alternative insert successful! ID: " . $conn->insert_id);
                        $success_message = "Message envoyé à l'administration avec succès!";
                        header("Location: messages.php?sent=1");
                        exit();
                    } else {
                        error_log("Alternative execute failed: " . $insert_stmt2->error);
                        $error_message .= "<br>Alternative also failed: " . $insert_stmt2->error;
                    }
                    $insert_stmt2->close();
                }
            }
            $insert_stmt->close();
            
        } catch (Exception $e) {
            error_log("Exception: " . $e->getMessage());
            $error_message = "Exception: " . $e->getMessage();
        }
    } else {
        $error_message = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Marquer un message comme lu (quand le client le consulte)
if (isset($_GET['mark_as_read']) && is_numeric($_GET['mark_as_read'])) {
    $message_id = (int)$_GET['mark_as_read'];
    $update_stmt = $conn->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ? AND client_id = ?");
    $update_stmt->bind_param("ii", $message_id, $client_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    header("Location: messages.php#message-$message_id");
    exit();
}

// Supprimer un message
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $message_id = (int)$_GET['delete'];
    $delete_stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ? AND client_id = ?");
    $delete_stmt->bind_param("ii", $message_id, $client_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    header("Location: messages.php?deleted=1");
    exit();
}

// Récupérer le nombre de messages envoyés (pour info)
$count_stmt = $conn->prepare("SELECT COUNT(*) as total_sent FROM contact_messages WHERE client_id = ?");
$count_stmt->bind_param("i", $client_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_sent = $count_result->fetch_assoc()['total_sent'];
$count_stmt->close();

// Afficher les erreurs à l'écran pour débogage
if (isset($error_message)) {
    error_log("Error message: " . $error_message);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie - FUS Denim</title>
    
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

        .sidebar-user {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, rgba(0, 0, 0, 0.1) 100%);
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

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        /* Messages Container */
        .messages-container {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
            height: calc(100vh - 180px);
        }

        @media (max-width: 1200px) {
            .messages-container {
                grid-template-columns: 1fr;
            }
        }

        /* Message List */
        .message-list-panel {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            display: flex;
            flex-direction: column;
        }

        .message-list-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-100);
            background: var(--gray-50);
        }

        .stats-box {
            background: var(--white);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            border: 1px solid var(--gray-200);
        }

        .stats-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-1);
        }

        .stats-label {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .message-filters {
            padding: 1rem 1.5rem;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-100);
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .filter-btn.active {
            background: var(--accent-1);
            color: white;
            border-color: var(--accent-1);
        }

        .search-box {
            margin-top: 1rem;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
        }

        .search-box i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
        }

        .message-list {
            flex: 1;
            overflow-y: auto;
            padding: 0;
        }

        .message-item {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-100);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .message-item:hover {
            background: var(--gray-50);
        }

        .message-item.active {
            background: var(--gray-50);
            border-left: 3px solid var(--accent-1);
        }

        .message-sender {
            font-weight: 600;
            color: var(--primary);
        }

        .message-date {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .message-subject {
            font-weight: 600;
            color: var(--gray-700);
            margin: 0.5rem 0;
        }

        .message-preview {
            color: var(--gray-600);
            font-size: 0.85rem;
            line-height: 1.4;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .status-badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent-5);
        }

        .status-read {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        /* Message View */
        .message-view-panel {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            display: flex;
            flex-direction: column;
        }

        .message-view-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-100);
        }

        .message-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .message-meta {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-100);
        }

        .message-body {
            font-size: 1rem;
            line-height: 1.7;
            color: var(--gray-700);
            white-space: pre-wrap;
            background: var(--gray-50);
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--accent-1);
        }

        /* Modal */
        .compose-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .compose-modal.active {
            display: flex;
        }

        .compose-form {
            background: var(--white);
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 1.5rem;
            border-top: 1px solid var(--gray-100);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .logo h2,
            .sidebar .nav-label,
            .sidebar .nav-item span,
            .sidebar-user .user-info {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
                padding: 1rem;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .messages-container {
                height: auto;
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
            <a href="catalog_prv.php" class="nav-item">
                <i class="fas fa-tshirt"></i>
                <span>Catalogue produits</span>
            </a>
            <a href="orders.php" class="nav-item">
                <i class="fas fa-shopping-bag"></i>
                <span>Mes commandes</span>
            </a>
            <a href="messages.php" class="nav-item active">
                <i class="fas fa-envelope"></i>
                <span>Messages</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-label">Compte</div>
            <a href="profile.php" class="nav-item">
                <i class="fas fa-user-cog"></i>
                <span>Mon profil</span>
            </a>
        </div>

        <div class="sidebar-user">
            <div style="margin-bottom: 1rem;">
                <small style="color: rgba(255, 255, 255, 0.6); display: block;">Société</small>
                <strong style="color: var(--white);"><?php echo htmlspecialchars($company_name); ?></strong>
            </div>
            <a href="login.php?action=logout" style="
                width: 100%;
                padding: 0.75rem;
                border: 1px solid rgba(255, 255, 255, 0.2);
                background: transparent;
                color: var(--white);
                border-radius: 8px;
                text-decoration: none;
                display: block;
                text-align: center;
            ">
                <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <h1>Messagerie</h1>
                <p>Envoyez des messages à l'administration FUS Denim</p>
            </div>
            <div>
                <button class="btn btn-primary" onclick="openComposeModal()">
                    <i class="fas fa-plus me-2"></i>Nouveau message
                </button>
            </div>
        </div>

        <!-- Messages Container -->
        <div class="messages-container">
            <!-- Left Panel - Message List -->
            <div class="message-list-panel">
                <div class="message-list-header">
                    <div class="stats-box">
                        <div class="stats-number"><?php echo $total_sent; ?></div>
                        <div class="stats-label">Messages envoyés</div>
                    </div>
                </div>

                <div class="message-filters">
                    <div class="filter-buttons">
                        <button class="filter-btn <?php echo $filter_type === 'all' ? 'active' : ''; ?>" 
                                onclick="setFilter('all')">Tous</button>
                        <button class="filter-btn <?php echo $filter_type === 'unread' ? 'active' : ''; ?>" 
                                onclick="setFilter('unread')">En attente</button>
                        <button class="filter-btn <?php echo $filter_type === 'read' ? 'active' : ''; ?>" 
                                onclick="setFilter('read')">Lus</button>
                    </div>

                    <div class="search-box">
                        <form method="GET" action="messages.php" onsubmit="return handleSearch()">
                            <input type="hidden" name="type" value="<?php echo $filter_type; ?>">
                            <i class="fas fa-search"></i>
                            <input type="text" 
                                   name="search" 
                                   id="searchInput"
                                   placeholder="Rechercher..."
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                        </form>
                    </div>
                </div>

                <div class="message-list">
                    <?php if (empty($messages)): ?>
                        <div style="padding: 3rem 1rem; text-align: center; color: var(--gray-400);">
                            <i class="fas fa-envelope-open" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <p>Vous n'avez pas encore envoyé de messages</p>
                            <button class="btn btn-primary mt-3" onclick="openComposeModal()">
                                <i class="fas fa-plus me-2"></i>Écrire un message
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message-item <?php echo isset($_GET['view']) && $_GET['view'] == $message['id'] ? 'active' : ''; ?>" 
                                 onclick="viewMessage(<?php echo $message['id']; ?>)">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <div class="message-subject">
                                            <?php echo htmlspecialchars($message['subject'] ?: '(Sans objet)'); ?>
                                        </div>
                                        <div class="message-preview">
                                            <?php echo htmlspecialchars(substr($message['message'], 0, 100)); ?>...
                                        </div>
                                    </div>
                                    <div class="message-date">
                                        <?php echo date('d/m/Y', strtotime($message['submitted_at'])); ?>
                                    </div>
                                </div>
                                <div>
                                    <span class="status-badge status-<?php echo $message['admin_replied'] ? 'read' : 'pending'; ?>">
                                        <?php echo $message['admin_replied'] ? 'Répondu par admin' : 'En attente'; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <button class="btn btn-sm btn-outline-primary" 
                            onclick="changePage(<?php echo max(1, $current_page - 1); ?>)" 
                            <?php echo $current_page <= 1 ? 'disabled' : ''; ?>>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    
                    <span style="padding: 0 1rem;">
                        Page <?php echo $current_page; ?> sur <?php echo $total_pages; ?>
                    </span>
                    
                    <button class="btn btn-sm btn-outline-primary" 
                            onclick="changePage(<?php echo min($total_pages, $current_page + 1); ?>)" 
                            <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Panel - Message View -->
            <div class="message-view-panel">
                <?php if (isset($_GET['view']) && is_numeric($_GET['view'])): 
                    $view_id = (int)$_GET['view'];
                    $view_message = null;
                    
                    foreach ($messages as $msg) {
                        if ($msg['id'] == $view_id) {
                            $view_message = $msg;
                            break;
                        }
                    }
                    
                    if ($view_message): ?>
                        <div class="message-view-header">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <h3 style="margin: 0;"><?php echo htmlspecialchars($view_message['subject']); ?></h3>
                                <div>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteMessage(<?php echo $view_message['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="message-content">
                            <div class="message-meta">
                                <div>
                                    <h4>Message à : Administration FUS Denim</h4>
                                    <p style="color: var(--gray-500); margin: 0.5rem 0;">
                                        Envoyé par : <strong><?php echo htmlspecialchars($contact_person); ?></strong> 
                                        (<?php echo htmlspecialchars($company_name); ?>)
                                    </p>
                                    <small style="color: var(--gray-400);">
                                        <i class="fas fa-clock me-1"></i> 
                                        <?php echo date('d/m/Y à H:i', strtotime($view_message['submitted_at'])); ?>
                                    </small>
                                    <div style="margin-top: 0.5rem;">
                                        <span class="status-badge status-<?php echo $view_message['admin_replied'] ? 'read' : 'pending'; ?>">
                                            <?php echo $view_message['admin_replied'] ? 'Répondu par l\'administration' : 'En attente de réponse'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="message-body">
                                <?php echo nl2br(htmlspecialchars($view_message['message'])); ?>
                            </div>
                            
                            <?php if ($view_message['admin_replied'] && !empty($view_message['reply_message'])): ?>
                            <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(16, 185, 129, 0.1); border-radius: 8px; border-left: 4px solid var(--accent-4);">
                                <h5 style="color: var(--accent-4); margin-bottom: 1rem;">
                                    <i class="fas fa-reply me-2"></i>Réponse de l'administration
                                </h5>
                                <div style="background: white; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                                    <?php echo nl2br(htmlspecialchars($view_message['reply_message'])); ?>
                                </div>
                                <?php if ($view_message['replied_at']): ?>
                                <p style="color: var(--gray-500); margin: 0; font-size: 0.9rem;">
                                    <i class="fas fa-clock me-1"></i>
                                    Répondu le <?php echo date('d/m/Y à H:i', strtotime($view_message['replied_at'])); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <?php elseif (!$view_message['admin_replied']): ?>
                            <div style="margin-top: 2rem; padding: 1rem; background: rgba(245, 158, 11, 0.1); border-radius: 8px; border-left: 4px solid var(--accent-5);">
                                <p style="margin: 0; color: var(--accent-5);">
                                    <i class="fas fa-clock me-2"></i>
                                    En attente d'une réponse de l'administration.
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div style="padding: 3rem; text-align: center;">
                            <i class="fas fa-exclamation-circle" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
                            <p>Message non trouvé</p>
                            <button class="btn btn-primary mt-2" onclick="window.location.href='messages.php'">
                                Retour à la liste
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="padding: 3rem; text-align: center;">
                        <i class="fas fa-envelope-open-text" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
                        <p>Sélectionnez un message pour le consulter</p>
                        <p class="small mt-2">Ou envoyez un nouveau message à l'administration</p>
                        <button class="btn btn-primary mt-3" onclick="openComposeModal()">
                            <i class="fas fa-plus me-2"></i>Nouveau message
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Compose Message Modal -->
        <div class="compose-modal" id="composeModal">
            <div class="compose-form">
                <div class="message-view-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0;">Nouveau message</h3>
                        <button onclick="closeComposeModal()" style="background: none; border: none; font-size: 1.5rem; color: var(--gray-500);">&times;</button>
                    </div>
                </div>
                
                <div style="padding: 2rem;">
                    <form method="POST" action="messages.php">
                        <div class="mb-3">
                            <label class="form-label">De</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($contact_person . ' - ' . $company_name); ?>" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">À</label>
                            <input type="text" class="form-control" value="Administration FUS Denim" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Objet *</label>
                            <input type="text" name="subject" class="form-control" placeholder="Sujet de votre message" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Message *</label>
                            <textarea name="message" class="form-control" rows="8" placeholder="Tapez votre message ici..." required></textarea>
                            <small class="form-text text-muted">
                                Votre message sera envoyé à l'équipe d'administration FUS Denim.
                            </small>
                        </div>
                        
                        <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                            <button type="button" class="btn btn-secondary" onclick="closeComposeModal()">Annuler</button>
                            <button type="submit" name="send_message" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Envoyer à l'administration
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gestion des messages
        function viewMessage(messageId) {
            const params = new URLSearchParams(window.location.search);
            params.set('view', messageId);
            window.location.href = `messages.php?${params.toString()}`;
        }

        function setFilter(filterType) {
            const params = new URLSearchParams(window.location.search);
            params.set('type', filterType);
            params.delete('view');
            params.delete('page');
            window.location.href = `messages.php?${params.toString()}`;
        }

        function handleSearch() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput.value.trim() === '') {
                return false;
            }
            return true;
        }

        function clearSearch() {
            const params = new URLSearchParams(window.location.search);
            params.delete('search');
            window.location.href = `messages.php?${params.toString()}`;
        }

        function changePage(page) {
            const params = new URLSearchParams(window.location.search);
            params.set('page', page);
            window.location.href = `messages.php?${params.toString()}`;
        }

        // Gestion de la modale
        function openComposeModal() {
            document.getElementById('composeModal').classList.add('active');
        }

        function closeComposeModal() {
            document.getElementById('composeModal').classList.remove('active');
        }

        // Supprimer un message
        function deleteMessage(messageId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce message ?')) {
                window.location.href = `messages.php?delete=${messageId}`;
            }
        }

        // Fermer la modale avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeComposeModal();
            }
        });

        // Notifications
        <?php if (isset($_GET['sent']) && $_GET['sent'] == 1): ?>
            alert('Message envoyé à l\'administration avec succès!');
            const params = new URLSearchParams(window.location.search);
            params.delete('sent');
            window.history.replaceState({}, '', `messages.php?${params.toString()}`);
        <?php endif; ?>

        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
            alert('Message supprimé avec succès!');
            const params = new URLSearchParams(window.location.search);
            params.delete('deleted');
            window.history.replaceState({}, '', `messages.php?${params.toString()}`);
        <?php endif; ?>
    </script>
</body>
</html>