<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$messages_per_page = 15;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $messages_per_page;
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

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
    $messages[] = $row;
}
$stmt->close();

$clients_result = $conn->query("
    SELECT id, company_name, contact_person, email, country, phone 
    FROM users 
    WHERE role = 'client' AND is_active = 1 
    ORDER BY company_name
");
$clients = [];
if ($clients_result) {
    while($row = $clients_result->fetch_assoc()) {
        $clients[] = $row;
    }
}

$message_feedback = '';
$message_type = '';

if (isset($_GET['toggle_read']) && is_numeric($_GET['id'])) {
    $message_id = (int)$_GET['id'];
    
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

if (isset($_GET['delete']) && is_numeric($_GET['id'])) {
    $message_id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    if ($stmt->execute()) {
        $message_feedback = "Message supprimé.";
        $message_type = 'success';
        header("Location: messages.php?deleted=1");
        exit();
    }
    $stmt->close();
}

if (isset($_GET['mark_all_read'])) {
    $conn->query("UPDATE contact_messages SET is_read = 1");
    $message_feedback = "Tous les messages marqués comme lus.";
    $message_type = 'success';
    header("Location: messages.php?all_read=1");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reply'])) {
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
            $message_feedback = "Réponse envoyée avec succès!";
            $message_type = 'success';
            header("Location: messages.php?view=" . $message_id . "&replied=1");
            exit();
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_to_client'])) {
    $client_id = (int)$_POST['client_id'];
    $subject = trim($_POST['subject']);
    $message_content = trim($_POST['message_content']);
    
    $stmt = $conn->prepare("SELECT company_name, contact_person, email FROM users WHERE id = ? AND role = 'client'");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $client = $result->fetch_assoc();
    $stmt->close();
    
    if ($client && !empty($subject) && !empty($message_content)) {
        $insert_stmt = $conn->prepare("
            INSERT INTO contact_messages (client_id, name, email, subject, message, is_read, admin_replied, submitted_at)
            VALUES (?, ?, ?, ?, ?, 1, 1, NOW())
        ");
        
        $insert_stmt->bind_param(
            "issss",
            $client_id,
            $client['contact_person'],
            $client['email'],
            $subject,
            $message_content
        );
        
        if ($insert_stmt->execute()) {
            $message_feedback = "Message envoyé au client avec succès!";
            $message_type = 'success';
            header("Location: messages.php?sent=1");
            exit();
        } else {
            $message_feedback = "Erreur lors de l'envoi du message.";
            $message_type = 'error';
        }
        $insert_stmt->close();
    } else {
        $message_feedback = "Veuillez remplir tous les champs obligatoires.";
        $message_type = 'error';
    }
}

$stats_result = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(is_read = 0) as unread,
        SUM(is_read = 1) as read_count,
        SUM(admin_replied = 1) as replied
    FROM contact_messages
");
$stats = $stats_result->fetch_assoc();

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

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.2);
            color: white;
            text-decoration: none;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--accent-4), #059669);
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
            color: white;
            text-decoration: none;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: var(--white);
            border-radius: 16px;
            padding: 1.75rem;
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
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
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
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-trend {
            font-size: 0.85rem;
            color: var(--accent-4);
            font-weight: 600;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
            height: calc(100vh - 300px);
        }

        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
                height: auto;
            }
        }

        /* Card Modern */
        .card-modern {
            background: var(--white);
            border-radius: 16px;
            padding: 0;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .card-modern:hover {
            box-shadow: var(--shadow-lg);
            border-color: var(--gray-200);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0;
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-100);
            background: var(--gray-50);
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

        /* Message List */
        .message-list-content {
            flex: 1;
            overflow-y: auto;
            padding: 0;
        }

        .message-filters {
            padding: 1rem 1.5rem;
            background: var(--white);
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
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background: var(--gray-100);
        }

        .filter-btn.active {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
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
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--accent-1);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
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
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.05));
            border-left: 4px solid var(--accent-1);
        }

        .message-item.unread {
            background: rgba(59, 130, 246, 0.03);
        }

        .message-sender {
            font-weight: 600;
            color: var(--primary);
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        /* Status Badge */
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .badge-unread {
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent-5);
        }

        .badge-read {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .badge-replied {
            background: rgba(139, 92, 246, 0.1);
            color: var(--accent-2);
        }

        /* Message View */
        .message-view-content {
            flex: 1;
            padding: 0;
            overflow-y: auto;
        }

        .message-view-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .message-detail {
            padding: 2rem;
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

        /* Send Message Form */
        .send-message-form {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.05), rgba(59, 130, 246, 0.05));
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.25rem;
            display: block;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--accent-1);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-textarea {
            min-height: 150px;
            resize: vertical;
        }

        .required::after {
            content: " *";
            color: #EF4444;
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
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
            text-decoration: none;
            font-size: 0.85rem;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            text-decoration: none;
        }

        .btn-reply:hover {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: white;
            border-color: var(--accent-1);
        }

        .btn-read:hover {
            background: linear-gradient(135deg, var(--accent-4), var(--accent-1));
            color: white;
            border-color: var(--accent-4);
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #EF4444, #DC2626);
            color: white;
            border-color: #EF4444;
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

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 1.5rem;
            border-top: 1px solid var(--gray-100);
        }

        .page-btn {
            padding: 0.5rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 6px;
            background: var(--white);
            color: var(--gray-600);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .page-btn:hover:not(:disabled) {
            background: var(--gray-100);
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .page-info {
            padding: 0 1rem;
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

        .stat-box {
            animation: slideInUp 0.5s ease-out forwards;
        }

        .stat-box:nth-child(1) { animation-delay: 0.1s; }
        .stat-box:nth-child(2) { animation-delay: 0.2s; }
        .stat-box:nth-child(3) { animation-delay: 0.3s; }
        .stat-box:nth-child(4) { animation-delay: 0.4s; }

        /* Responsive */
        @media (max-width: 1200px) {
            .sidebar {
                width: 260px;
            }

            .main-content {
                margin-left: 260px;
                padding: 1.5rem;
            }

            .content-grid {
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

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .content-grid {
                height: auto;
                gap: 1rem;
            }

            .card-modern {
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

            .stat-value {
                font-size: 1.75rem;
            }

            .message-view-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .action-buttons {
                width: 100%;
                justify-content: space-between;
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
            <a href="messages.php" class="nav-item active">
                <i class="fas fa-envelope"></i>
                <span>Messages</span>
                <?php if ($stats['unread'] > 0): ?>
                <span class="nav-badge"><?php echo $stats['unread']; ?></span>
                <?php endif; ?>
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
                <h1>Messages des clients</h1>
                <p>Gérez les messages reçus des clients</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
                <?php if ($stats['unread'] > 0): ?>
                <a href="messages.php?mark_all_read=1" class="btn btn-primary">
                    <i class="fas fa-check-double me-2"></i>Tout marquer comme lu
                </a>
                <?php endif; ?>
                <button type="button" class="btn-success" data-bs-toggle="modal" data-bs-target="#sendMessageModal">
                    <i class="fas fa-paper-plane me-2"></i>Envoyer un message
                </button>
            </div>
        </div>

        <!-- Alert Message -->
        <?php if ($message_feedback): ?>
        <div class="alert-modern alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
            <div class="d-flex align-items-center">
                <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                <span><?php echo $message_feedback; ?></span>
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.style.display='none'"></button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Total messages</div>
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-trend"><?php echo $today_stats['today_count']; ?> aujourd'hui</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Non lus</div>
                    <div class="stat-icon">
                        <i class="fas fa-envelope-open"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['unread']; ?></div>
                <div class="stat-trend">En attente</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Lus</div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['read_count']; ?></div>
                <div class="stat-trend">Consultés</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Réponses</div>
                    <div class="stat-icon">
                        <i class="fas fa-reply"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['replied']; ?></div>
                <div class="stat-trend">Traitées</div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Left Panel - Message List -->
            <div class="card-modern">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-inbox"></i> Messages reçus
                    </div>
                    <a href="?type=all" class="card-action">Actualiser</a>
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
                
                <div class="message-list-content">
                    <?php if (empty($messages)): ?>
                        <div class="empty-state">
                            <i class="fas fa-envelope-open-text"></i>
                            <p>Aucun message trouvé</p>
                            <?php if ($search_query || $filter_type !== 'all'): ?>
                            <button class="btn btn-primary mt-3" onclick="window.location.href='messages.php'">
                                <i class="fas fa-eye me-2"></i>Voir tous les messages
                            </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php 
                        $selected_message_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
                        foreach ($messages as $message): 
                            $is_unread = $message['is_read'] == 0;
                            $is_active = $message['id'] == $selected_message_id;
                        ?>
                            <div class="message-item <?php echo $is_unread ? 'unread' : ''; ?> <?php echo $is_active ? 'active' : ''; ?>" 
                                 onclick="viewMessage(<?php echo $message['id']; ?>)">
                                <div class="message-sender">
                                    <span><?php echo htmlspecialchars($message['name']); ?></span>
                                    <span class="message-date">
                                        <?php echo date('d/m/Y', strtotime($message['submitted_at'])); ?>
                                    </span>
                                </div>
                                <div class="message-subject">
                                    <?php echo htmlspecialchars($message['subject'] ?: '(Sans objet)'); ?>
                                </div>
                                <div class="message-preview">
                                    <?php echo htmlspecialchars(substr($message['message'], 0, 100)); ?>...
                                </div>
                                <div style="margin-top: 0.75rem;">
                                    <?php if (!$message['is_read']): ?>
                                    <span class="status-badge badge-unread">
                                        <i class="fas fa-circle fa-xs me-1"></i>Non lu
                                    </span>
                                    <?php elseif ($message['admin_replied']): ?>
                                    <span class="status-badge badge-replied">
                                        <i class="fas fa-reply me-1"></i>Répondu
                                    </span>
                                    <?php else: ?>
                                    <span class="status-badge badge-read">
                                        <i class="fas fa-check me-1"></i>Lu
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <button class="page-btn" 
                            onclick="changePage(<?php echo max(1, $current_page - 1); ?>)" 
                            <?php echo $current_page <= 1 ? 'disabled' : ''; ?>>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    
                    <span class="page-info">
                        Page <?php echo $current_page; ?> sur <?php echo $total_pages; ?>
                    </span>
                    
                    <button class="page-btn" 
                            onclick="changePage(<?php echo min($total_pages, $current_page + 1); ?>)" 
                            <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Panel - Message View -->
            <div class="card-modern">
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
                            <h3 style="margin: 0;"><?php echo htmlspecialchars($view_message['subject']); ?></h3>
                            <div class="action-buttons">
                                <a href="mailto:<?php echo urlencode($view_message['email']); ?>?subject=Re: <?php echo urlencode($view_message['subject']); ?>" 
                                   class="btn-action btn-reply" title="Répondre par email">
                                    <i class="fas fa-reply"></i> Répondre
                                </a>
                                <a href="?toggle_read=1&id=<?php echo $view_message['id']; ?>&view=<?php echo $view_message['id']; ?>" 
                                   class="btn-action btn-read" title="<?php echo $view_message['is_read'] ? 'Marquer comme non lu' : 'Marquer comme lu'; ?>">
                                    <i class="fas <?php echo $view_message['is_read'] ? 'fa-envelope' : 'fa-envelope-open'; ?>"></i>
                                    <?php echo $view_message['is_read'] ? 'Non lu' : 'Marquer lu'; ?>
                                </a>
                                <a href="?delete=1&id=<?php echo $view_message['id']; ?>" 
                                   class="btn-action btn-delete" 
                                   title="Supprimer"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?')">
                                    <i class="fas fa-trash"></i> Supprimer
                                </a>
                            </div>
                        </div>
                        
                        <div class="message-detail">
                            <div class="message-meta">
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
                                    <span class="status-badge badge-unread">
                                        <i class="fas fa-circle fa-xs me-1"></i> Non lu
                                    </span>
                                    <?php elseif ($view_message['admin_replied']): ?>
                                    <span class="status-badge badge-replied">
                                        <i class="fas fa-reply me-1"></i> Répondu le <?php echo date('d/m/Y', strtotime($view_message['replied_at'])); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="status-badge badge-read">
                                        <i class="fas fa-check me-1"></i> Lu
                                    </span>
                                    <?php endif; ?>
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
                                               disabled style="background: var(--gray-50);">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Objet</label>
                                        <input type="text" name="reply_subject" class="form-control" 
                                               value="Re: <?php echo htmlspecialchars($view_message['subject']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Réponse *</label>
                                        <textarea name="reply_message" class="form-control" rows="6" 
                                                  placeholder="Écrivez votre réponse ici..." required
                                                  style="font-family: 'Inter', sans-serif;"></textarea>
                                        <small class="form-text text-muted">
                                            Cette réponse sera enregistrée dans le système et vous pourrez aussi l'envoyer par email.
                                        </small>
                                    </div>
                                    
                                    <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                                        <button type="button" class="btn btn-secondary" onclick="closeReplyForm()">Annuler</button>
                                        <button type="submit" name="submit_reply" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-2"></i>Enregistrer la réponse
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>Message non trouvé</p>
                            <button class="btn btn-primary mt-3" onclick="window.location.href='messages.php'">
                                <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-envelope-open-text"></i>
                        <p>Sélectionnez un message pour le consulter</p>
                        <p class="small mt-2">Vous avez <?php echo $stats['unread']; ?> message(s) non lu(s)</p>
                        <?php if ($stats['unread'] > 0): ?>
                        <a href="messages.php?mark_all_read=1" class="btn btn-primary mt-3">
                            <i class="fas fa-check-double me-2"></i>Tout marquer comme lu
                        </a>
                        <?php endif; ?>
                        
                        <!-- Bouton pour ouvrir le formulaire d'envoi de message -->
                        <button type="button" class="btn-success mt-3" data-bs-toggle="modal" data-bs-target="#sendMessageModal">
                            <i class="fas fa-paper-plane me-2"></i>Envoyer un message à un client
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal pour envoyer un message à un client -->
    <div class="modal fade" id="sendMessageModal" tabindex="-1" aria-labelledby="sendMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sendMessageModalLabel">
                        <i class="fas fa-paper-plane me-2"></i>Envoyer un message à un client
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="messages.php">
                    <div class="modal-body">
                        <div class="send-message-form">
                            <div class="form-group">
                                <label class="form-label required">Client</label>
                                <select name="client_id" class="form-select" required>
                                    <option value="">Sélectionner un client...</option>
                                    <?php foreach($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>">
                                        <?php echo htmlspecialchars($client['company_name']); ?> - 
                                        <?php echo htmlspecialchars($client['contact_person']); ?>
                                        (<?php echo htmlspecialchars($client['email']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label required">Objet du message</label>
                                <input type="text" name="subject" class="form-control" 
                                       placeholder="Sujet du message..." required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label required">Message</label>
                                <textarea name="message_content" class="form-control form-textarea" 
                                          placeholder="Écrivez votre message ici..." required></textarea>
                                <small class="form-text text-muted">
                                    Ce message sera enregistré dans le système et marqué comme "répondu".
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="send_to_client" class="btn btn-success">
                            <i class="fas fa-paper-plane me-2"></i>Envoyer le message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
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
            const form = document.querySelector('.reply-section form');
            if (form) {
                form.reset();
            }
        }

        // Animation des nombres des statistiques
        const animateValue = (element, start, end, duration) => {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                element.textContent = Math.floor(progress * (end - start) + start);
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        };

        // Observer pour déclencher les animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const value = entry.target.querySelector('.stat-value');
                    if (value && !value.dataset.animated) {
                        const finalValue = parseInt(value.textContent);
                        animateValue(value, 0, finalValue, 800);
                        value.dataset.animated = 'true';
                    }
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.stat-box').forEach(box => observer.observe(box));

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

        // Auto-refresh toutes les 60 secondes
        setTimeout(function() {
            if (!document.querySelector('.reply-section form') || !document.querySelector('.reply-section form textarea:focus')) {
                location.reload();
            }
        }, 60000);

        // Notifications automatiques
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

        <?php if (isset($_GET['replied']) && $_GET['replied'] == 1): ?>
            setTimeout(() => {
                const alert = document.createElement('div');
                alert.className = 'alert-modern alert-success';
                alert.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-2"></i>
                        <span>Réponse enregistrée avec succès</span>
                        <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.style.display='none'"></button>
                    </div>
                `;
                document.querySelector('.main-content').insertBefore(alert, document.querySelector('.header').nextSibling);
            }, 100);
        <?php endif; ?>

        <?php if (isset($_GET['sent']) && $_GET['sent'] == 1): ?>
            setTimeout(() => {
                const alert = document.createElement('div');
                alert.className = 'alert-modern alert-success';
                alert.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-2"></i>
                        <span>Message envoyé au client avec succès</span>
                        <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.style.display='none'"></button>
                    </div>
                `;
                document.querySelector('.main-content').insertBefore(alert, document.querySelector('.header').nextSibling);
            }, 100);
        <?php endif; ?>

        // Active nav item based on current page
        const currentPage = window.location.pathname.split('/').pop() || 'messages.php';
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