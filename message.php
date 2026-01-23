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
$user_email = $_SESSION['user_email'];
if ($role === 'admin') {
    $query = "SELECT cm.*, u.company_name, u.contact_person 
              FROM contact_messages cm 
              LEFT JOIN users u ON cm.email = u.email 
              ORDER BY cm.is_read ASC, cm.submitted_at DESC";
    $result = $conn->query($query);
} else {
    $query = "SELECT cm.*, u.company_name, u.contact_person 
              FROM contact_messages cm 
              LEFT JOIN users u ON cm.email = u.email 
              WHERE cm.email = ? 
              ORDER BY cm.submitted_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
}

if ($role === 'admin') {
    $unread_count_query = "SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0";
    $unread_result = $conn->query($unread_count_query);
} else {
    $unread_count_query = "SELECT COUNT(*) as count FROM contact_messages WHERE email = ? AND is_read = 0";
    $unread_stmt = $conn->prepare($unread_count_query);
    $unread_stmt->bind_param("s", $user_email);
    $unread_stmt->execute();
    $unread_result = $unread_stmt->get_result();
}
$unread_count = $unread_result->fetch_assoc()['count'];
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $message_id = intval($_GET['read']);
    if ($role === 'admin') {
        $mark_query = "UPDATE contact_messages SET is_read = 1 WHERE id = ?";
        $mark_stmt = $conn->prepare($mark_query);
        $mark_stmt->bind_param("i", $message_id);
    } else {
        $mark_query = "UPDATE contact_messages SET is_read = 1 WHERE id = ? AND email = ?";
        $mark_stmt = $conn->prepare($mark_query);
        $mark_stmt->bind_param("is", $message_id, $user_email);
    }
    
    if ($mark_stmt->execute()) {
        header('Location: messages.php');
        exit();
    }
    $mark_stmt->close();
}
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $message_id = intval($_GET['delete']);
    if ($role === 'admin') {
        $delete_query = "DELETE FROM contact_messages WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $message_id);
    } else {
        $delete_query = "DELETE FROM contact_messages WHERE id = ? AND email = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("is", $message_id, $user_email);
    }
    
    if ($delete_stmt->execute()) {
        header('Location: messages.php');
        exit();
    }
    $delete_stmt->close();
}
if ($role === 'admin') {
    $stats_query = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
            SUM(CASE WHEN DATE(submitted_at) = CURDATE() THEN 1 ELSE 0 END) as today,
            SUM(CASE WHEN DATE(submitted_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) as yesterday
        FROM contact_messages
    ";
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result->fetch_assoc();
}
function getTimeAgo($date) {
    $now = new DateTime();
    $messageDate = new DateTime($date);
    $interval = $now->diff($messageDate);
    
    if ($interval->y > 0) return "Il y a " . $interval->y . " an" . ($interval->y > 1 ? "s" : "");
    if ($interval->m > 0) return "Il y a " . $interval->m . " mois";
    if ($interval->d > 0) return "Il y a " . $interval->d . " jour" . ($interval->d > 1 ? "s" : "");
    if ($interval->h > 0) return "Il y a " . $interval->h . " heure" . ($interval->h > 1 ? "s" : "");
    if ($interval->i > 0) return "Il y a " . $interval->i . " min";
    return "À l'instant";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - FUS Denim</title>
    
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
            min-width: 20px;
            text-align: center;
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
        }

        .stat-box:nth-child(2)::before {
            background: linear-gradient(90deg, var(--accent-5), var(--accent-3));
        }

        .stat-box:nth-child(3)::before {
            background: linear-gradient(90deg, var(--accent-2), var(--accent-3));
        }

        .stat-box:nth-child(4)::before {
            background: linear-gradient(90deg, var(--accent-4), var(--accent-1));
        }

        .stat-box:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-label {
            color: var(--gray-500);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .stat-box:nth-child(1) .stat-icon {
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-1);
        }

        .stat-box:nth-child(2) .stat-icon {
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent-5);
        }

        .stat-box:nth-child(3) .stat-icon {
            background: rgba(139, 92, 246, 0.1);
            color: var(--accent-2);
        }

        .stat-box:nth-child(4) .stat-icon {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .stat-trend {
            font-size: 0.85rem;
            color: var(--accent-4);
            font-weight: 600;
        }

        /* Messages Container */
        .messages-container {
            background: var(--white);
            border-radius: 16px;
            padding: 0;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            overflow: hidden;
        }

        .messages-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .messages-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .messages-title i {
            color: var(--accent-1);
        }

        .messages-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            color: var(--gray-600);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-action:hover {
            background: var(--gray-100);
            color: var(--primary);
            text-decoration: none;
        }

        .btn-action.active {
            background: var(--accent-1);
            color: white;
            border-color: var(--accent-1);
        }

        /* Messages List */
        .messages-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .message-item {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-100);
            transition: all 0.3s ease;
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .message-item:last-child {
            border-bottom: none;
        }

        .message-item:hover {
            background: var(--gray-50);
        }

        .message-item.unread {
            background: rgba(59, 130, 246, 0.05);
            border-left: 4px solid var(--accent-1);
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .message-content {
            flex: 1;
            min-width: 0;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .message-sender {
            font-weight: 700;
            color: var(--primary);
            font-size: 0.95rem;
        }

        .message-company {
            color: var(--gray-500);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        .message-time {
            color: var(--gray-500);
            font-size: 0.85rem;
            white-space: nowrap;
            margin-left: 1rem;
        }

        .message-subject {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .message-preview {
            color: var(--gray-600);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .message-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn-message-action {
            padding: 0.25rem 0.75rem;
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            color: var(--gray-600);
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-message-action:hover {
            background: var(--gray-200);
            color: var(--primary);
            text-decoration: none;
        }

        .btn-message-action.read:hover {
            background: var(--accent-1);
            color: white;
            border-color: var(--accent-1);
        }

        .btn-message-action.delete:hover {
            background: #EF4444;
            color: white;
            border-color: #EF4444;
        }

        /* Empty State */
        .empty-state {
            padding: 3rem 2rem;
            text-align: center;
            color: var(--gray-400);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h4 {
            color: var(--gray-600);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--gray-500);
            margin-bottom: 1.5rem;
        }

        /* Modal de message */
        .message-modal-content {
            border-radius: 16px;
            border: none;
            overflow: hidden;
        }

        .modal-header {
            background: var(--primary);
            color: white;
            border-bottom: none;
            padding: 1.5rem;
        }

        .modal-body {
            padding: 1.5rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .message-full-content {
            color: var(--gray-700);
            line-height: 1.6;
            white-space: pre-wrap;
        }

        /* New Message Form */
        .new-message-form {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            margin-top: 2rem;
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

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .message-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .message-time {
                margin-left: 0;
            }

            .messages-actions {
                flex-wrap: wrap;
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .message-item {
                flex-direction: column;
                gap: 0.75rem;
            }

            .message-avatar {
                align-self: flex-start;
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
                <a href="message.php" class="nav-item active">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                    <?php if ($unread_count > 0): ?>
                    <span class="nav-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
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
                <a href="profile.php" class="nav-item">
                    <i class="fas fa-user-cog"></i>
                    <span>Mon profil</span>
                </a>
                <a href="message.php" class="nav-item active">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                    <?php if ($unread_count > 0): ?>
                    <span class="nav-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
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
                <h1>Messages</h1>
                <p><?php echo $role === 'admin' ? 'Gestion des messages clients' : 'Vos messages avec FUS Denim'; ?></p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
                <?php if ($role === 'admin'): ?>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                <?php else: ?>
                    <a href="dashboard_client.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats Grid (Admin seulement) -->
        <?php if ($role === 'admin'): ?>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Total Messages</div>
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="stat-trend">Tous les messages</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Non lus</div>
                    <div class="stat-icon">
                        <i class="fas fa-envelope-open"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['unread'] ?? 0; ?></div>
                <div class="stat-trend">À traiter</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Aujourd'hui</div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['today'] ?? 0; ?></div>
                <div class="stat-trend">Nouveaux messages</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Hier</div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-minus"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['yesterday'] ?? 0; ?></div>
                <div class="stat-trend">Messages reçus</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Messages Container -->
        <div class="messages-container">
            <div class="messages-header">
                <div class="messages-title">
                    <i class="fas fa-inbox"></i>
                    Boîte de réception
                </div>
                <div class="messages-actions">
                    <?php if ($role === 'client'): ?>
                    <a href="contact.php" class="btn-action">
                        <i class="fas fa-plus"></i> Nouveau message
                    </a>
                    <?php else: ?>
                    <button type="button" class="btn-action" onclick="showNewMessageForm()">
                        <i class="fas fa-plus"></i> Nouveau message
                    </button>
                    <?php endif; ?>
                    <?php if ($role === 'admin'): ?>
                    <a href="export_messages.php" class="btn-action">
                        <i class="fas fa-download"></i> Exporter
                    </a>
                    <?php endif; ?>
                    <button type="button" class="btn-action" onclick="refreshMessages()">
                        <i class="fas fa-sync-alt"></i> Actualiser
                    </button>
                </div>
            </div>

            <div class="messages-list" id="messagesList">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($message = $result->fetch_assoc()): 
                        $is_unread = !$message['is_read'];
                        $time_ago = getTimeAgo($message['submitted_at']);
                        $sender_name = $message['name'] ? htmlspecialchars($message['name']) : 'Anonyme';
                        $company_name = $message['company_name'] ? htmlspecialchars($message['company_name']) : '';
                    ?>
                        <div class="message-item <?php echo $is_unread ? 'unread' : ''; ?>" 
                             data-message-id="<?php echo $message['id']; ?>">
                            <div class="message-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="message-content">
                                <div class="message-header">
                                    <div>
                                        <div class="message-sender"><?php echo $sender_name; ?></div>
                                        <?php if ($company_name): ?>
                                        <div class="message-company"><?php echo $company_name; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="message-time"><?php echo $time_ago; ?></div>
                                </div>
                                
                                <div class="message-subject">
                                    <?php echo htmlspecialchars($message['subject']); ?>
                                </div>
                                
                                <div class="message-preview">
                                    <?php echo htmlspecialchars(substr($message['message'], 0, 150)); ?>...
                                </div>
                                
                                <div class="message-actions">
                                    <button type="button" class="btn-message-action view" 
                                            onclick="viewMessage(<?php echo $message['id']; ?>)">
                                        <i class="fas fa-eye me-1"></i> Voir
                                    </button>
                                    
                                    <?php if ($is_unread): ?>
                                    <a href="?read=<?php echo $message['id']; ?>" 
                                       class="btn-message-action read">
                                        <i class="fas fa-check me-1"></i> Marquer comme lu
                                    </a>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn-message-action delete" 
                                            onclick="deleteMessage(<?php echo $message['id']; ?>)">
                                        <i class="fas fa-trash me-1"></i> Supprimer
                                    </button>
                                    
                                    <?php if ($role === 'admin' && $message['email']): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" 
                                       class="btn-message-action">
                                        <i class="fas fa-reply me-1"></i> Répondre
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-envelope-open"></i>
                        <h4>Aucun message</h4>
                        <p><?php echo $role === 'admin' ? 'Aucun message pour le moment.' : 'Vous n\'avez pas encore de messages.'; ?></p>
                        <?php if ($role === 'client'): ?>
                        <a href="contact.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Écrire un message
                        </a>
                        <?php else: ?>
                        <button type="button" class="btn btn-primary" onclick="showNewMessageForm()">
                            <i class="fas fa-plus me-2"></i>Écrire un message
                        </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- New Message Form (Admin seulement - Hidden by default) -->
        <?php if ($role === 'admin'): ?>
        <div class="new-message-form" id="newMessageForm" style="display: none;">
            <h3 class="section-title mb-4">
                <i class="fas fa-pen"></i> Nouveau message
            </h3>
            
            <form id="messageForm" action="send_message.php" method="POST">
                <div class="mb-3">
                    <label for="recipient" class="form-label">Destinataire</label>
                    <select class="form-select" id="recipient" name="recipient" required>
                        <option value="">Sélectionner un client...</option>
                        <?php
                        $clients_query = "SELECT id, company_name, contact_person, email FROM users WHERE role = 'client' AND is_active = 1 ORDER BY company_name";
                        $clients_result = $conn->query($clients_query);
                        while ($client = $clients_result->fetch_assoc()):
                        ?>
                        <option value="<?php echo $client['id']; ?>">
                            <?php echo htmlspecialchars($client['company_name'] . ' - ' . $client['contact_person']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="subject" class="form-label">Sujet *</label>
                    <input type="text" class="form-control" id="subject" name="subject" required 
                           placeholder="Sujet de votre message">
                </div>
                
                <div class="mb-3">
                    <label for="message" class="form-label">Message *</label>
                    <textarea class="form-control" id="message" name="message" rows="6" required 
                              placeholder="Écrivez votre message ici..."></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" onclick="hideNewMessageForm()">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Envoyer
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <div>
                <i class="fas fa-<?php echo $role === 'admin' ? 'bolt' : 'gem'; ?>" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Messagerie
            </div>
            <div>
                <span class="system-status">
                    <i class="fas fa-circle"></i> <?php echo $result->num_rows; ?> message<?php echo $result->num_rows > 1 ? 's' : ''; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content message-modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSubject"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong id="modalSender"></strong>
                                <div class="text-muted small" id="modalCompany"></div>
                            </div>
                            <div class="text-muted small" id="modalTime"></div>
                        </div>
                        <div class="text-muted small mb-3" id="modalEmail"></div>
                    </div>
                    <div class="message-full-content" id="modalMessage"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Fermer
                    </button>
                    <?php if ($role === 'admin'): ?>
                    <button type="button" class="btn btn-primary" id="replyButton">
                        <i class="fas fa-reply me-2"></i>Répondre
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Afficher/masquer le formulaire de nouveau message (admin seulement)
        function showNewMessageForm() {
            document.getElementById('newMessageForm').style.display = 'block';
            document.getElementById('newMessageForm').scrollIntoView({ behavior: 'smooth' });
        }

        function hideNewMessageForm() {
            document.getElementById('newMessageForm').style.display = 'none';
            document.getElementById('messageForm').reset();
        }

        // Voir un message en détail
        function viewMessage(messageId) {
            // Charger les détails du message
            fetch('get_message.php?id=' + messageId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remplir la modal
                        document.getElementById('modalSubject').textContent = data.message.subject;
                        document.getElementById('modalSender').textContent = data.message.name || 'Anonyme';
                        document.getElementById('modalCompany').textContent = data.message.company_name || '';
                        document.getElementById('modalEmail').textContent = data.message.email ? `📧 ${data.message.email}` : '';
                        document.getElementById('modalTime').textContent = getTimeAgo(new Date(data.message.submitted_at));
                        document.getElementById('modalMessage').textContent = data.message.message;

                        // Configurer le bouton de réponse
                        if (data.message.email) {
                            document.getElementById('replyButton').onclick = function() {
                                window.location.href = 'mailto:' + data.message.email + 
                                    '?subject=RE: ' + encodeURIComponent(data.message.subject);
                            };
                        }

                        // Afficher la modal
                        const modal = new bootstrap.Modal(document.getElementById('messageModal'));
                        modal.show();
                        
                        // Marquer comme lu après visualisation
                        markAsRead(messageId);
                    }
                });
        }

        // Marquer un message comme lu
        function markAsRead(messageId) {
            fetch('mark_as_read.php?id=' + messageId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mettre à jour l'interface
                        const messageItem = document.querySelector(`[data-message-id="${messageId}"]`);
                        if (messageItem) {
                            messageItem.classList.remove('unread');
                            // Mettre à jour le badge
                            updateUnreadBadge();
                        }
                    }
                });
        }

        // Mettre à jour le badge des messages non lus
        function updateUnreadBadge() {
            fetch('get_unread_count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.querySelector('.nav-badge');
                        if (badge) {
                            if (data.unread_count > 0) {
                                badge.textContent = data.unread_count;
                            } else {
                                badge.remove();
                            }
                        } else if (data.unread_count > 0) {
                            // Créer le badge s'il n'existe pas
                            const navItem = document.querySelector('.nav-item.active');
                            if (navItem) {
                                const newBadge = document.createElement('span');
                                newBadge.className = 'nav-badge';
                                newBadge.textContent = data.unread_count;
                                navItem.appendChild(newBadge);
                            }
                        }
                    }
                });
        }

        // Supprimer un message
        function deleteMessage(messageId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce message ?')) {
                window.location.href = '?delete=' + messageId;
            }
        }

        // Actualiser les messages
        function refreshMessages() {
            window.location.reload();
        }

        // Fonction pour calculer le temps écoulé
        function getTimeAgo(date) {
            const now = new Date();
            const messageDate = new Date(date);
            const diffMs = now - messageDate;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'À l\'instant';
            if (diffMins < 60) return `Il y a ${diffMins} min`;
            if (diffHours < 24) return `Il y a ${diffHours} h`;
            if (diffDays < 7) return `Il y a ${diffDays} j`;
            
            return messageDate.toLocaleDateString('fr-FR');
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

        // Validation du formulaire de message
        document.addEventListener('DOMContentLoaded', function() {
            const messageForm = document.getElementById('messageForm');
            if (messageForm) {
                messageForm.addEventListener('submit', function(e) {
                    const subject = document.getElementById('subject')?.value.trim();
                    const message = document.getElementById('message')?.value.trim();
                    
                    if (!subject || !message) {
                        e.preventDefault();
                        alert('Veuillez remplir tous les champs obligatoires.');
                        return;
                    }
                    
                    if (message.length < 10) {
                        e.preventDefault();
                        alert('Le message doit contenir au moins 10 caractères.');
                        return;
                    }
                    
                    if (!confirm('Êtes-vous sûr de vouloir envoyer ce message ?')) {
                        e.preventDefault();
                    }
                });
            }
        });

        // Raccourcis clavier
        document.addEventListener('keydown', function(e) {
            // N pour nouveau message (admin seulement)
            if (e.key === 'n' && e.ctrlKey && <?php echo $role === 'admin' ? 'true' : 'false'; ?>) {
                e.preventDefault();
                showNewMessageForm();
            }
            // R pour actualiser
            if (e.key === 'r' && e.ctrlKey) {
                e.preventDefault();
                refreshMessages();
            }
            // Échap pour fermer le formulaire
            if (e.key === 'Escape') {
                const form = document.getElementById('newMessageForm');
                if (form && form.style.display === 'block') {
                    hideNewMessageForm();
                }
            }
        });
    </script>
</body>
</html>