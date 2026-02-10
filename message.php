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

$stmt = $conn->prepare("SELECT contact_person, company_name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$client_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (empty($subject) || empty($message)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } elseif (strlen($message) > 5000) {
        $error = "Le message est trop long (maximum 5000 caractères).";
    } else {
        $stmt = $conn->prepare("INSERT INTO contact_messages (client_id, name, email, subject, message, submitted_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issss", $client_id, $client_info['contact_person'], $client_info['email'], $subject, $message);
        
        if ($stmt->execute()) {
            $success = "Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.";
            $_POST = [];
        } else {
            $error = "Une erreur est survenue lors de l'envoi du message. Veuillez réessayer.";
        }
        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT * FROM contact_messages WHERE client_id = ? ORDER BY submitted_at DESC");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$messages_result = $stmt->get_result();
$messages = [];
while ($row = $messages_result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM contact_messages WHERE client_id = ? AND is_read = 0");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$unread_result = $stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['unread_count'];
$stmt->close();

$stmt = $conn->prepare("UPDATE contact_messages SET is_read = 1 WHERE client_id = ? AND is_read = 0");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders 
                       WHERE client_id = ? AND status IN ('received', 'validating', 'confirmed', 'production')");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$active_orders_result = $stmt->get_result();
$active_orders = $active_orders_result->fetch_assoc()['count'];
$stmt->close();
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

        .badge-notification {
            position: absolute;
            right: 1rem;
            background: linear-gradient(135deg, var(--accent-3), var(--accent-5));
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
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

        /* Message Layout */
        .message-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 1rem;
        }

        @media (max-width: 992px) {
            .message-container {
                grid-template-columns: 1fr;
            }
        }

        /* Messages List */
        .messages-list {
            background: var(--white);
            border-radius: 16px;
            padding: 0;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            height: 600px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .messages-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-100);
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: white;
            border-radius: 16px 16px 0 0;
        }

        .messages-header h3 {
            color: white;
            margin: 0;
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

        .message-item.unread {
            background: rgba(59, 130, 246, 0.05);
        }

        .message-item.active {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.05));
            border-left: 4px solid var(--accent-1);
        }

        .message-subject {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .message-preview {
            color: var(--gray-600);
            font-size: 0.9rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .message-meta {
            display: flex;
            justify-content: space-between;
            margin-top: 0.75rem;
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .message-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-read {
            color: var(--accent-4);
        }

        .status-unread {
            color: var(--accent-5);
        }

        .status-replied {
            color: var(--accent-1);
        }

        /* Message Detail */
        .message-detail {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            height: 600px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .message-detail-header {
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-100);
            margin-bottom: 1.5rem;
        }

        .message-detail-subject {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .message-detail-meta {
            display: flex;
            justify-content: space-between;
            color: var(--gray-500);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .message-content {
            flex-grow: 1;
            line-height: 1.6;
            color: var(--gray-700);
        }

        .message-reply {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
            border: 1px solid var(--gray-200);
        }

        .reply-label {
            display: block;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .reply-content {
            line-height: 1.6;
            color: var(--gray-700);
        }

        .reply-meta {
            text-align: right;
            color: var(--gray-500);
            font-size: 0.85rem;
            margin-top: 1rem;
            font-style: italic;
        }

        .no-message-selected {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--gray-400);
            text-align: center;
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

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--gray-700);
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-1);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.875rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.2);
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid transparent;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.2);
            color: var(--accent-4);
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.2);
            color: #EF4444;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--gray-400);
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state p {
            color: var(--gray-500);
            margin-bottom: 1.5rem;
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

            .message-container {
                gap: 1rem;
            }

            .messages-list, .message-detail {
                height: 500px;
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

            .message-container {
                grid-template-columns: 1fr;
            }

            .new-message-form {
                padding: 1.25rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
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
                <?php if ($active_orders > 0): ?>
                    <span class="badge-notification"><?php echo $active_orders; ?></span>
                <?php endif; ?>
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
                    <span class="badge-notification"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
        </div>

        <div class="sidebar-user">
            <div class="user-card">
                <div class="user-avatar">
                    <i class="fas fa-building"></i>
                </div>
                <div class="user-info">
                    <small>Société</small>
                    <strong><?php echo htmlspecialchars(substr($_SESSION['company_name'], 0, 20)); ?></strong>
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
                <h1>Messagerie</h1>
                <p>Communiquez avec notre équipe FUS Denim</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Messages Container -->
        <div class="message-container">
            <!-- Messages List -->
            <div class="messages-list">
                <div class="messages-header">
                    <h3><i class="fas fa-inbox me-2"></i> Boîte de réception</h3>
                </div>
                
                <?php if (empty($messages)): ?>
                    <div class="empty-state">
                        <i class="fas fa-envelope-open-text"></i>
                        <p>Aucun message pour le moment</p>
                    </div>
                <?php else: ?>
                    <?php 
                    $selected_message = $_GET['message_id'] ?? (isset($messages[0]) ? $messages[0]['id'] : null);
                    foreach ($messages as $message): 
                        $is_unread = $message['is_read'] == 0;
                        $is_active = $message['id'] == $selected_message;
                    ?>
                        <a href="?message_id=<?php echo $message['id']; ?>" class="message-item <?php echo $is_unread ? 'unread' : ''; ?> <?php echo $is_active ? 'active' : ''; ?>">
                            <div class="message-subject">
                                <span><?php echo htmlspecialchars($message['subject']); ?></span>
                                <?php if ($is_unread): ?>
                                    <span class="status-unread"><i class="fas fa-circle fa-xs"></i></span>
                                <?php endif; ?>
                            </div>
                            <div class="message-preview">
                                <?php echo htmlspecialchars(substr($message['message'], 0, 100)); ?>...
                            </div>
                            <div class="message-meta">
                                <span><?php echo date('d/m/Y H:i', strtotime($message['submitted_at'])); ?></span>
                                <div class="message-status">
                                    <?php if ($message['admin_replied']): ?>
                                        <span class="status-replied">
                                            <i class="fas fa-reply me-1"></i>Répondu
                                        </span>
                                    <?php else: ?>
                                        <span class="status-unread">
                                            <i class="fas fa-clock me-1"></i>En attente
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Message Detail -->
            <div class="message-detail">
                <?php 
                $selected_message = null;
                if (isset($_GET['message_id'])) {
                    $message_id = $_GET['message_id'];
                    foreach ($messages as $msg) {
                        if ($msg['id'] == $message_id) {
                            $selected_message = $msg;
                            break;
                        }
                    }
                }
                
                if ($selected_message): ?>
                    <div class="message-detail-header">
                        <div class="message-detail-subject">
                            <?php echo htmlspecialchars($selected_message['subject']); ?>
                        </div>
                        <div class="message-detail-meta">
                            <span><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($selected_message['name']); ?></span>
                            <span><i class="fas fa-clock me-1"></i><?php echo date('d/m/Y H:i', strtotime($selected_message['submitted_at'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="message-content">
                        <p><?php echo nl2br(htmlspecialchars($selected_message['message'])); ?></p>
                    </div>
                    
                    <?php if ($selected_message['admin_replied'] && $selected_message['reply_message']): ?>
                        <div class="message-reply">
                            <div class="reply-label">
                                <i class="fas fa-reply me-2"></i>Réponse de FUS Denim
                            </div>
                            <div class="reply-content">
                                <?php echo nl2br(htmlspecialchars($selected_message['reply_message'])); ?>
                            </div>
                            <?php if ($selected_message['replied_at']): ?>
                                <div class="reply-meta">
                                    <i class="fas fa-clock me-1"></i>
                                    Répondu le <?php echo date('d/m/Y H:i', strtotime($selected_message['replied_at'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-message-selected">
                        <div>
                            <i class="fas fa-envelope fa-3x mb-3"></i>
                            <p>Sélectionnez un message pour le lire</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- New Message Form -->
        <div class="new-message-form">
            <h3><i class="fas fa-paper-plane me-2"></i> Nouveau message</h3>
            <p class="text-muted mb-4">Envoyez un message à notre équipe. Nous vous répondrons dans les plus brefs délais.</p>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="subject" class="form-label">Sujet *</label>
                    <input type="text" id="subject" name="subject" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" 
                           required placeholder="Ex: Demande d'information sur une commande">
                </div>
                
                <div class="form-group">
                    <label for="message" class="form-label">Message *</label>
                    <textarea id="message" name="message" class="form-control form-textarea" 
                              required placeholder="Décrivez votre demande en détail..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    <small class="text-muted">Maximum 5000 caractères</small>
                </div>
                
                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-redo me-2"></i>Effacer
                    </button>
                    <button type="submit" name="send_message" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Envoyer le message
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh toutes les 30 secondes pour les nouveaux messages
        setTimeout(function() {
            location.reload();
        }, 30000);

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
        const currentPage = window.location.pathname.split('/').pop() || 'message.php';
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.getAttribute('href') === currentPage) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Scroll to bottom of message detail
        const messageDetail = document.querySelector('.message-detail');
        if (messageDetail) {
            messageDetail.scrollTop = messageDetail.scrollHeight;
        }

        // Character counter for message textarea
        const messageTextarea = document.getElementById('message');
        if (messageTextarea) {
            const charCounter = document.createElement('small');
            charCounter.className = 'text-muted d-block mt-1';
            charCounter.id = 'char-counter';
            updateCharCounter();
            messageTextarea.parentNode.insertBefore(charCounter, messageTextarea.nextSibling);
            
            messageTextarea.addEventListener('input', updateCharCounter);
            
            function updateCharCounter() {
                const currentLength = messageTextarea.value.length;
                const maxLength = 5000;
                charCounter.textContent = `${currentLength} / ${maxLength} caractères`;
                
                if (currentLength > maxLength) {
                    charCounter.className = 'text-danger d-block mt-1';
                } else if (currentLength > maxLength * 0.9) {
                    charCounter.className = 'text-warning d-block mt-1';
                } else {
                    charCounter.className = 'text-muted d-block mt-1';
                }
            }
        }
    </script>
</body>
</html>