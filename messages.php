<?php
session_start();
require_once 'connexion.php';

// Vérification de l'authentification
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Variables pour la pagination et le filtrage
$messages_per_page = 15;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $messages_per_page;
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Construire la requête pour récupérer tous les messages
$query_conditions = "WHERE 1=1";
$query_params = [];
$query_types = "";

if ($filter_type === 'unread') {
    $query_conditions .= " AND is_read = 0";
} elseif ($filter_type === 'read') {
    $query_conditions .= " AND is_read = 1";
}

if (!empty($search_query)) {
    $query_conditions .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $query_params[] = "%$search_query%";
    $query_params[] = "%$search_query%";
    $query_params[] = "%$search_query%";
    $query_params[] = "%$search_query%";
    $query_types .= "ssss";
}

// Compter le nombre total de messages
$count_sql = "SELECT COUNT(*) as total FROM contact_messages $query_conditions";
if (!empty($query_params)) {
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param($query_types, ...$query_params);
    $stmt->execute();
    $count_result = $stmt->get_result();
} else {
    $count_result = $conn->query($count_sql);
}
$total_messages = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_messages / $messages_per_page);

// Récupérer les messages avec pagination
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
    // Pour l'admin, les messages sont ceux reçus des clients
    $row['message_type'] = 'received';
    $row['sender_info'] = $row['name'] . ' (' . $row['email'] . ')';
    $row['recipient'] = 'Administration FUS Denim';
    
    $messages[] = $row;
}
$stmt->close();

// Traitement des actions sur les messages
$message_feedback = '';
$message_type = '';

// Marquer un message comme lu/non lu
if (isset($_GET['toggle_read']) && is_numeric($_GET['id'])) {
    $message_id = (int)$_GET['id'];
    
    // Récupérer l'état actuel
    $stmt = $conn->prepare("SELECT is_read FROM contact_messages WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $msg = $result->fetch_assoc();
    
    if ($msg) {
        $new_status = $msg['is_read'] ? 0 : 1;
        $stmt = $conn->prepare("UPDATE contact_messages SET is_read = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $message_id);
        if ($stmt->execute()) {
            $message_feedback = $new_status ? "Message marqué comme lu." : "Message marqué comme non lu.";
            $message_type = 'success';
        }
        $stmt->close();
    }
}

// Supprimer un message
if (isset($_GET['delete']) && is_numeric($_GET['id'])) {
    $message_id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    if ($stmt->execute()) {
        $message_feedback = "Message supprimé.";
        $message_type = 'success';
        // Redirection pour éviter le re-soumission
        header("Location: messages.php?deleted=1");
        exit();
    }
    $stmt->close();
}

// Marquer tous les messages comme lus
if (isset($_GET['mark_all_read'])) {
    $conn->query("UPDATE contact_messages SET is_read = 1");
    $message_feedback = "Tous les messages marqués comme lus.";
    $message_type = 'success';
    // Redirection pour éviter le re-soumission
    header("Location: messages.php?all_read=1");
    exit();
}

// Répondre à un message (à implémenter si nécessaire)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $message_id = (int)$_POST['message_id'];
    $reply_message = trim($_POST['reply_message']);
    $reply_subject = isset($_POST['reply_subject']) ? trim($_POST['reply_subject']) : 'Re: ' . $_POST['original_subject'];
    
    if (!empty($reply_message)) {
        $update_stmt = $conn->prepare("UPDATE contact_messages 
                                      SET admin_replied = 1, 
                                          reply_message = ?, 
                                          replied_at = NOW() 
                                      WHERE id = ?");
        
        $update_stmt->bind_param("si", $reply_message, $message_id);
        
        if ($update_stmt->execute()) {
            // Ici vous pourriez ajouter l'envoi d'email au client
            $message_feedback = "Réponse envoyée avec succès!";
            $message_type = 'success';
        } else {
            $message_feedback = "Erreur lors de l'envoi de la réponse.";
            $message_type = 'error';
        }
        $update_stmt->close();
    } else {
        $message_feedback = "Veuillez écrire une réponse.";
        $message_type = 'error';
    }
}

// Récupérer les statistiques
$stats_result = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(is_read = 0) as unread,
        SUM(is_read = 1) as read_count,
        SUM(admin_replied = 1) as replied
    FROM contact_messages
");
$stats = $stats_result->fetch_assoc();

// Messages aujourd'hui
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) as today_count FROM contact_messages WHERE DATE(submitted_at) = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$today_result = $stmt->get_result();
$today_stats = $today_result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Tableau de bord Admin - FUS Denim</title>
    
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
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

        .message-item.unread {
            background: rgba(59, 130, 246, 0.03);
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

        .status-unread {
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-1);
        }

        .status-read {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .status-replied {
            background: rgba(139, 92, 246, 0.1);
            color: var(--accent-2);
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
            margin-bottom: 2rem;
        }

        .reply-section {
            background: rgba(16, 185, 129, 0.05);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: 1px solid var(--gray-200);
            background: var(--white);
            color: var(--gray-600);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        .btn-reply:hover {
            background: var(--accent-1);
            color: white;
            border-color: var(--accent-1);
        }

        .btn-read:hover {
            background: var(--accent-4);
            color: white;
            border-color: var(--accent-4);
        }

        .btn-delete:hover {
            background: #EF4444;
            color: white;
            border-color: #EF4444;
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

        /* Alert */
        .alert-modern {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid transparent;
            animation: slideInUp 0.5s ease-out;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.3);
            color: var(--accent-4);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #EF4444;
        }

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
                <i class="fas fa-tshirt"></i>
                <span>Produits</span>
            </a>
            <a href="messages.php" class="nav-item active">
                <i class="fas fa-envelope"></i>
                <span>Messages</span>
                <?php if ($stats['unread'] > 0): ?>
                <span class="nav-badge"><?php echo $stats['unread']; ?></span>
                <?php endif; ?>
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
                <small style="color: rgba(255, 255, 255, 0.6); display: block;">Connecté en tant que</small>
                <strong style="color: var(--white);">Administrateur</strong>
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
                <h1>Messages des clients</h1>
                <p>Gérez les messages reçus des clients</p>
            </div>
            <div>
                <?php if ($stats['unread'] > 0): ?>
                <a href="messages.php?mark_all_read=1" class="btn btn-primary">
                    <i class="fas fa-check-double me-2"></i>Tout marquer comme lu
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alert Message -->
        <?php if ($message_feedback): ?>
        <div class="alert-modern alert-<?php echo $message_type; ?>">
            <div class="d-flex align-items-center">
                <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                <span><?php echo $message_feedback; ?></span>
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.style.display='none'"></button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Messages Container -->
        <div class="messages-container">
            <!-- Left Panel - Message List -->
            <div class="message-list-panel">
                <div class="message-list-header">
                    <div class="stats-grid">
                        <div class="stats-box">
                            <div class="stats-number"><?php echo $stats['total']; ?></div>
                            <div class="stats-label">Total</div>
                        </div>
                        <div class="stats-box">
                            <div class="stats-number" style="color: var(--accent-1);"><?php echo $stats['unread']; ?></div>
                            <div class="stats-label">Non lus</div>
                        </div>
                        <div class="stats-box">
                            <div class="stats-number" style="color: var(--accent-4);"><?php echo $stats['read_count']; ?></div>
                            <div class="stats-label">Lus</div>
                        </div>
                        <div class="stats-box">
                            <div class="stats-number" style="color: var(--accent-2);"><?php echo $stats['replied']; ?></div>
                            <div class="stats-label">Réponses</div>
                        </div>
                    </div>
                </div>

                <div class="message-filters">
                    <div class="filter-buttons">
                        <button class="filter-btn <?php echo $filter_type === 'all' ? 'active' : ''; ?>" 
                                onclick="setFilter('all')">Tous</button>
                        <button class="filter-btn <?php echo $filter_type === 'unread' ? 'active' : ''; ?>" 
                                onclick="setFilter('unread')">Non lus</button>
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
                            <p>Aucun message trouvé</p>
                            <?php if ($search_query || $filter_type !== 'all'): ?>
                            <button class="btn btn-outline-primary mt-3" onclick="window.location.href='messages.php'">
                                <i class="fas fa-eye me-2"></i>Voir tous les messages
                            </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message-item <?php echo !$message['is_read'] ? 'unread' : ''; ?> <?php echo isset($_GET['view']) && $_GET['view'] == $message['id'] ? 'active' : ''; ?>" 
                                 onclick="viewMessage(<?php echo $message['id']; ?>)">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <div class="message-sender">
                                            <?php echo htmlspecialchars($message['name']); ?>
                                        </div>
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
                                    <?php if (!$message['is_read']): ?>
                                    <span class="status-badge status-unread">
                                        Nouveau
                                    </span>
                                    <?php elseif ($message['admin_replied']): ?>
                                    <span class="status-badge status-replied">
                                        Répondu
                                    </span>
                                    <?php else: ?>
                                    <span class="status-badge status-read">
                                        Lu
                                    </span>
                                    <?php endif; ?>
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
                    
                    if ($view_message): 
                        // Marquer le message comme lu lorsqu'on le consulte
                        if (!$view_message['is_read']) {
                            $update_stmt = $conn->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
                            $update_stmt->bind_param("i", $view_id);
                            $update_stmt->execute();
                            $update_stmt->close();
                            $view_message['is_read'] = 1;
                        }
                    ?>
                        <div class="message-view-header">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <h3 style="margin: 0;"><?php echo htmlspecialchars($view_message['subject']); ?></h3>
                                <div class="action-buttons">
                                    <a href="mailto:<?php echo urlencode($view_message['email']); ?>?subject=Re: <?php echo urlencode($view_message['subject']); ?>" 
                                       class="btn-action btn-reply" title="Répondre par email">
                                        <i class="fas fa-reply"></i> Répondre
                                    </a>
                                    <a href="?toggle_read=1&id=<?php echo $view_message['id']; ?>&view=<?php echo $view_message['id']; ?>" 
                                       class="btn-action" title="<?php echo $view_message['is_read'] ? 'Marquer comme non lu' : 'Marquer comme lu'; ?>">
                                        <i class="fas <?php echo $view_message['is_read'] ? 'fa-envelope' : 'fa-envelope-open'; ?>"></i>
                                    </a>
                                    <a href="?delete=1&id=<?php echo $view_message['id']; ?>" 
                                       class="btn-action btn-delete" 
                                       title="Supprimer"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="message-content">
                            <div class="message-meta">
                                <div>
                                    <h4>Message de : <?php echo htmlspecialchars($view_message['name']); ?></h4>
                                    <p style="color: var(--gray-500); margin: 0.5rem 0;">
                                        <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($view_message['email']); ?>
                                    </p>
                                    <small style="color: var(--gray-400);">
                                        <i class="fas fa-clock me-1"></i> 
                                        <?php echo date('d/m/Y à H:i', strtotime($view_message['submitted_at'])); ?>
                                    </small>
                                    <div style="margin-top: 0.5rem;">
                                        <?php if (!$view_message['is_read']): ?>
                                        <span class="status-badge status-unread">
                                            Non lu
                                        </span>
                                        <?php elseif ($view_message['admin_replied']): ?>
                                        <span class="status-badge status-replied">
                                            Répondu le <?php echo date('d/m/Y', strtotime($view_message['replied_at'])); ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="status-badge status-read">
                                            Lu
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="message-body">
                                <?php echo nl2br(htmlspecialchars($view_message['message'])); ?>
                            </div>
                            
                            <?php if ($view_message['admin_replied'] && !empty($view_message['reply_message'])): ?>
                            <div class="reply-section">
                                <h5 style="color: var(--accent-4); margin-bottom: 1rem;">
                                    <i class="fas fa-reply me-2"></i>Réponse envoyée
                                </h5>
                                <div style="background: white; padding: 1rem; border-radius: 6px; border-left: 3px solid var(--accent-4);">
                                    <?php echo nl2br(htmlspecialchars($view_message['reply_message'])); ?>
                                </div>
                                <p style="color: var(--gray-500); margin-top: 0.5rem; font-size: 0.9rem;">
                                    <i class="fas fa-clock me-1"></i>
                                    Répondu le <?php echo date('d/m/Y à H:i', strtotime($view_message['replied_at'])); ?>
                                </p>
                            </div>
                            <?php else: ?>
                            <div class="reply-section">
                                <h5 style="color: var(--accent-1); margin-bottom: 1rem;">
                                    <i class="fas fa-reply me-2"></i>Répondre au client
                                </h5>
                                <form method="POST" action="messages.php?view=<?php echo $view_message['id']; ?>">
                                    <input type="hidden" name="message_id" value="<?php echo $view_message['id']; ?>">
                                    <input type="hidden" name="original_subject" value="<?php echo htmlspecialchars($view_message['subject']); ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Destinataire</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo htmlspecialchars($view_message['name'] . ' <' . $view_message['email'] . '>'); ?>" 
                                               disabled>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Objet</label>
                                        <input type="text" name="reply_subject" class="form-control" 
                                               value="Re: <?php echo htmlspecialchars($view_message['subject']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Réponse *</label>
                                        <textarea name="reply_message" class="form-control" rows="6" 
                                                  placeholder="Écrivez votre réponse ici..." required></textarea>
                                        <small class="form-text text-muted">
                                            Cette réponse sera enregistrée dans le système et vous pourrez aussi l'envoyer par email.
                                        </small>
                                    </div>
                                    
                                    <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                                        <button type="button" class="btn btn-secondary" onclick="closeReplyForm()">Annuler</button>
                                        <button type="submit" name="reply_message_submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-2"></i>Enregistrer la réponse
                                        </button>
                                    </div>
                                </form>
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
                        <p class="small mt-2">Vous avez <?php echo $stats['unread']; ?> message(s) non lu(s)</p>
                        <?php if ($stats['unread'] > 0): ?>
                        <a href="messages.php?mark_all_read=1" class="btn btn-primary mt-3">
                            <i class="fas fa-check-double me-2"></i>Tout marquer comme lu
                        </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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

        function changePage(page) {
            const params = new URLSearchParams(window.location.search);
            params.set('page', page);
            window.location.href = `messages.php?${params.toString()}`;
        }

        function closeReplyForm() {
            // Réinitialiser le formulaire
            document.querySelector('form').reset();
        }

        // Notifications
        <?php if (isset($_GET['all_read']) && $_GET['all_read'] == 1): ?>
            setTimeout(() => {
                const alert = document.createElement('div');
                alert.className = 'alert-modern alert-success';
                alert.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-2"></i>
                        <span>Tous les messages ont été marqués comme lus</span>
                        <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.style.display='none'"></button>
                    </div>
                `;
                document.querySelector('.main-content').insertBefore(alert, document.querySelector('.header').nextSibling);
                
                // Mettre à jour les badges
                document.querySelectorAll('.status-unread').forEach(badge => {
                    badge.className = 'status-badge status-read';
                    badge.textContent = 'Lu';
                });
                
                // Mettre à jour la liste des messages
                document.querySelectorAll('.message-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
            }, 100);
        <?php endif; ?>

        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
            setTimeout(() => {
                const alert = document.createElement('div');
                alert.className = 'alert-modern alert-success';
                alert.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-2"></i>
                        <span>Message supprimé avec succès</span>
                        <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.style.display='none'"></button>
                    </div>
                `;
                document.querySelector('.main-content').insertBefore(alert, document.querySelector('.header').nextSibling);
            }, 100);
        <?php endif; ?>

        // Auto-refresh toutes les 30 secondes pour les nouveaux messages
        setTimeout(() => {
            if (!document.querySelector('.reply-section form') || document.querySelector('.reply-section form').style.display === 'none') {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>