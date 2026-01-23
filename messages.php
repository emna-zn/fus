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
if (isset($_GET['toggle_read']) && isset($_GET['id'])) {
    $message_id = intval($_GET['id']);
    
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
            $message = $new_status ? "Message marqué comme lu." : "Message marqué comme non lu.";
            $message_type = 'success';
        }
        $stmt->close();
    }
}
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $message_id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    if ($stmt->execute()) {
        $message = "Message supprimé.";
        $message_type = 'success';
    }
    $stmt->close();
}
if (isset($_GET['mark_all_read'])) {
    $conn->query("UPDATE contact_messages SET is_read = 1");
    $message = "Tous les messages marqués comme lus.";
    $message_type = 'success';
}
$search = isset($_GET['search']) ? $_GET['search'] : '';
$read_filter = isset($_GET['read']) ? $_GET['read'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$query = "SELECT * FROM contact_messages WHERE 1=1";
$params = [];
$types = '';

if ($search) {
    $query .= " AND (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?) ";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ssss';
}

if ($read_filter !== '') {
    $query .= " AND is_read = ? ";
    $params[] = $read_filter;
    $types .= 'i';
}

if ($date_from) {
    $query .= " AND DATE(submitted_at) >= ? ";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $query .= " AND DATE(submitted_at) <= ? ";
    $params[] = $date_to;
    $types .= 's';
}

$query .= " ORDER BY submitted_at DESC";
if ($params) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

$messages = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
}
$stats_result = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(is_read = 0) as unread,
        SUM(is_read = 1) as read_count
    FROM contact_messages
");
$stats = $stats_result->fetch_assoc();
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
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
        }

        .stat-box:nth-child(1)::before {
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
        }

        .stat-box:nth-child(2)::before {
            background: linear-gradient(90deg, var(--accent-1), #3B82F6);
        }

        .stat-box:nth-child(3)::before {
            background: linear-gradient(90deg, var(--accent-4), #059669);
        }

        .stat-box:nth-child(4)::before {
            background: linear-gradient(90deg, var(--accent-5), #D97706);
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
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-1);
        }

        .stat-box:nth-child(3) .stat-icon {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .stat-box:nth-child(4) .stat-icon {
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent-5);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-trend {
            font-size: 0.85rem;
            color: var(--gray-500);
            font-weight: 500;
        }

        /* Card Modern */
        .card-modern {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            transition: all 0.3s ease;
            margin-bottom: 2rem;
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

        /* Modern Buttons */
        .btn-modern {
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            color: var(--white);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
            color: var(--white);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--accent-4), #059669);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-outline-modern {
            border: 1px solid var(--gray-300);
            background: transparent;
            color: var(--gray-600);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-outline-modern:hover {
            border-color: var(--accent-1);
            color: var(--accent-1);
            background: rgba(59, 130, 246, 0.05);
        }

        /* Filter Form */
        .filter-form .form-control {
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .filter-form .form-control:focus {
            border-color: var(--accent-1);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-box {
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            z-index: 1;
        }

        .search-box .form-control {
            padding-left: 2.5rem;
        }

        /* Message Cards */
        .message-card {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            transition: all 0.3s ease;
            border-left: 4px solid var(--gray-300);
        }

        .message-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--accent-1);
        }

        .message-card.unread {
            border-left-color: var(--accent-1);
            background: rgba(59, 130, 246, 0.03);
        }

        .message-card.read {
            border-left-color: var(--accent-4);
        }

        /* Message Header */
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-100);
        }

        .message-sender {
            flex-grow: 1;
        }

        .sender-name {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary);
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sender-info {
            color: var(--gray-600);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .sender-info i {
            width: 16px;
            color: var(--accent-1);
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
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-1);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .badge-read {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        /* Message Subject */
        .message-subject {
            font-weight: 600;
            color: var(--gray-800);
            margin: 0.75rem 0;
            padding: 0.75rem;
            background: var(--gray-50);
            border-radius: 12px;
            border-left: 3px solid var(--accent-1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Message Content */
        .message-content {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 1.25rem;
            margin: 1rem 0;
            white-space: pre-wrap;
            line-height: 1.7;
            border: 1px solid var(--gray-200);
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .message-content.collapsed {
            max-height: 150px;
            overflow: hidden;
        }

        .message-content .expand-btn {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, var(--gray-50));
            padding: 2rem 1rem 1rem;
            text-align: center;
            color: var(--accent-1);
            font-weight: 600;
            display: none;
        }

        .message-content.collapsed .expand-btn {
            display: block;
        }

        /* Message Date */
        .message-date {
            color: var(--gray-500);
            font-size: 0.85rem;
            margin-top: 1rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .message-date i {
            color: var(--accent-1);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--gray-200);
            background: var(--white);
            color: var(--gray-600);
            transition: all 0.3s ease;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .btn-reply:hover {
            background: var(--accent-1);
            color: var(--white);
            border-color: var(--accent-1);
        }

        .btn-read:hover {
            background: var(--accent-5);
            color: var(--white);
            border-color: var(--accent-5);
        }

        .btn-unread:hover {
            background: var(--accent-4);
            color: var(--white);
            border-color: var(--accent-4);
        }

        .btn-delete:hover {
            background: #EF4444;
            color: var(--white);
            border-color: #EF4444;
        }

        /* Alert */
        .alert-modern {
            border-radius: 12px;
            border: 1px solid;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            animation: slideInUp 0.5s ease-out;
        }

        .alert-modern.alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.3);
            color: var(--accent-4);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--gray-400);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state p {
            color: var(--gray-500);
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

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        .stat-box, .card-modern, .message-card, .alert-modern {
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

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
                justify-content: space-between;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .message-header {
                flex-direction: column;
                gap: 1rem;
            }

            .action-buttons {
                margin-left: 0;
                width: 100%;
                justify-content: flex-start;
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

            .card-modern {
                padding: 1.25rem;
            }

            .message-card {
                padding: 1rem;
            }

            .footer {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }

        /* Stats Badge */
        .stats-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            background: var(--gray-100);
            border-radius: 8px;
            font-size: 0.9rem;
            color: var(--gray-700);
        }

        .stats-badge i {
            color: var(--accent-1);
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
                <span class="nav-badge pulse"><?php echo $stats['unread']; ?></span>
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
            <a href="login.php" class="logout-btn">
                <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <h1>Messages Contact</h1>
                <p>Gérez les messages reçus via le formulaire de contact</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
                <?php if ($stats['unread'] > 0): ?>
                <a href="?mark_all_read=1" class="btn-modern btn-success">
                    <i class="fas fa-check-double"></i> Marquer comme lus
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alert Message -->
        <?php if ($message): ?>
        <div class="alert-modern alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas <?php 
                    if ($message_type == 'success') echo 'fa-check-circle';
                    else echo 'fa-exclamation-circle';
                ?> me-2"></i>
                <span><?php echo $message; ?></span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Messages reçus</div>
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-trend">Total</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Non lus</div>
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['unread']; ?></div>
                <div class="stat-trend">À lire</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Lus</div>
                    <div class="stat-icon">
                        <i class="fas fa-envelope-open"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['read_count']; ?></div>
                <div class="stat-trend">Consultés</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Aujourd'hui</div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
                <div class="stat-value">
                    <?php 
                    $today = date('Y-m-d');
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM contact_messages WHERE DATE(submitted_at) = ?");
                    $stmt->bind_param("s", $today);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $today_count = $result->fetch_assoc()['count'];
                    echo $today_count;
                    ?>
                </div>
                <div class="stat-trend">Nouveaux</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card-modern">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-filter"></i> Filtres de recherche
                </div>
                <?php if ($search || $read_filter !== '' || $date_from || $date_to): ?>
                <a href="messages.php" class="btn-outline-modern">
                    <i class="fas fa-times"></i> Réinitialiser
                </a>
                <?php endif; ?>
            </div>
            
            <form method="GET" class="row g-3 filter-form">
                <div class="col-lg-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Rechercher dans les messages..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-lg-3">
                    <select class="form-select" name="read">
                        <option value="">Tous les états</option>
                        <option value="0" <?php echo $read_filter === '0' ? 'selected' : ''; ?>>Non lus seulement</option>
                        <option value="1" <?php echo $read_filter === '1' ? 'selected' : ''; ?>>Lus seulement</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <input type="date" class="form-control" name="date_from" 
                           value="<?php echo htmlspecialchars($date_from); ?>" placeholder="Date de début">
                </div>
                <div class="col-lg-2">
                    <input type="date" class="form-control" name="date_to" 
                           value="<?php echo htmlspecialchars($date_to); ?>" placeholder="Date de fin">
                </div>
                <div class="col-lg-1">
                    <button type="submit" class="btn-modern w-100">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Messages List -->
        <div class="card-modern">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-envelope"></i> Messages reçus
                    <span class="ms-2 text-muted" style="font-size: 0.9rem; font-weight: 400;">
                        (<?php echo count($messages); ?> message<?php echo count($messages) > 1 ? 's' : ''; ?>)
                    </span>
                </div>
            </div>
            
            <?php if (empty($messages)): ?>
            <div class="empty-state">
                <i class="fas fa-envelope-open-text"></i>
                <h4 class="mt-3 mb-2">Aucun message</h4>
                <p class="text-muted">
                    <?php if ($search || $read_filter !== '' || $date_from || $date_to): ?>
                    Aucun message ne correspond à vos critères de recherche.
                    <?php else: ?>
                    Aucun message reçu pour le moment.
                    <?php endif; ?>
                </p>
                <?php if ($search || $read_filter !== '' || $date_from || $date_to): ?>
                <a href="messages.php" class="btn-outline-modern mt-3">
                    <i class="fas fa-eye me-2"></i>Voir tous les messages
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <?php foreach($messages as $index => $msg): ?>
            <div class="message-card <?php echo $msg['is_read'] ? 'read' : 'unread'; ?>" 
                 style="animation-delay: <?php echo ($index * 0.1) + 0.2; ?>s">
                <div class="message-header">
                    <div class="message-sender">
                        <div class="sender-name">
                            <?php echo htmlspecialchars($msg['name']); ?>
                            <span class="status-badge badge-<?php echo $msg['is_read'] ? 'read' : 'unread'; ?>">
                                <i class="fas <?php echo $msg['is_read'] ? 'fa-envelope-open' : 'fa-envelope'; ?>"></i>
                                <?php echo $msg['is_read'] ? 'Lu' : 'Nouveau'; ?>
                            </span>
                        </div>
                        <div class="sender-info">
                            <div>
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($msg['email']); ?>
                            </div>
                            <div>
                                <i class="fas fa-clock"></i> 
                                <?php echo date('d/m/Y à H:i', strtotime($msg['submitted_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <a href="mailto:<?php echo urlencode($msg['email']); ?>" 
                           class="btn-action btn-reply" title="Répondre">
                            <i class="fas fa-reply"></i>
                        </a>
                        <a href="?toggle_read=1&id=<?php echo $msg['id']; ?>" 
                           class="btn-action <?php echo $msg['is_read'] ? 'btn-read' : 'btn-unread'; ?>" 
                           title="<?php echo $msg['is_read'] ? 'Marquer comme non lu' : 'Marquer comme lu'; ?>">
                            <i class="fas <?php echo $msg['is_read'] ? 'fa-envelope' : 'fa-envelope-check'; ?>"></i>
                        </a>
                        <a href="?delete=1&id=<?php echo $msg['id']; ?>" 
                           class="btn-action btn-delete" 
                           title="Supprimer"
                           onclick="return confirm('Supprimer ce message ?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
                
                <?php if ($msg['subject']): ?>
                <div class="message-subject">
                    <i class="fas fa-comment-dots"></i><?php echo htmlspecialchars($msg['subject']); ?>
                </div>
                <?php endif; ?>
                
                <div class="message-content collapsed">
                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                    <span class="expand-btn">
                        <i class="fas fa-chevron-down me-2"></i>Voir plus
                    </span>
                </div>
                
                <div class="message-date">
                    <i class="fas fa-history"></i>
                    Message reçu il y a 
                    <?php
                    $now = new DateTime();
                    $msgDate = new DateTime($msg['submitted_at']);
                    $interval = $now->diff($msgDate);
                    
                    if ($interval->y > 0) {
                        echo $interval->y . ' an' . ($interval->y > 1 ? 's' : '');
                    } elseif ($interval->m > 0) {
                        echo $interval->m . ' mois';
                    } elseif ($interval->d > 0) {
                        echo $interval->d . ' jour' . ($interval->d > 1 ? 's' : '');
                    } elseif ($interval->h > 0) {
                        echo $interval->h . ' heure' . ($interval->h > 1 ? 's' : '');
                    } elseif ($interval->i > 0) {
                        echo $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
                    } else {
                        echo 'quelques secondes';
                    }
                    ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Footer -->
            <?php if (!empty($messages)): ?>
            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    <i class="fas fa-list me-2"></i>
                    <?php echo count($messages); ?> message<?php echo count($messages) > 1 ? 's' : ''; ?>
                    <?php 
                    $unread_count = array_reduce($messages, function($carry, $msg) {
                        return $carry + ($msg['is_read'] ? 0 : 1);
                    }, 0);
                    if ($unread_count > 0): ?>
                    (<span style="color: var(--accent-1); font-weight: 600;"><?php echo $unread_count; ?> non lu<?php echo $unread_count > 1 ? 's' : ''; ?></span>)
                    <?php endif; ?>
                </div>
                <div>
                    <button class="btn-outline-modern btn-sm" onclick="printMessages()">
                        <i class="fas fa-print me-2"></i>Imprimer
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>
                <i class="fas fa-shield-alt" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Back-office Administrateur v1.0
                <span class="stats-badge ms-3">
                    <i class="fas fa-envelope"></i>
                    <?php echo $stats['total']; ?> message<?php echo $stats['total'] > 1 ? 's' : ''; ?> reçu<?php echo $stats['total'] > 1 ? 's' : ''; ?>
                </span>
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
        // Auto-refresh toutes les 60 secondes
        setTimeout(function() {
            location.reload();
        }, 60000);

        // Gestion de l'expansion des messages
        document.querySelectorAll('.message-content').forEach(content => {
            // Vérifier si le message est long
            if (content.textContent.length > 500) {
                content.classList.add('collapsed');
                
                // Ajouter l'événement de clic
                content.addEventListener('click', function(e) {
                    if (e.target.classList.contains('expand-btn')) {
                        this.classList.toggle('collapsed');
                        const icon = this.querySelector('.expand-btn i');
                        const text = this.querySelector('.expand-btn');
                        if (this.classList.contains('collapsed')) {
                            icon.className = 'fas fa-chevron-down me-2';
                            text.innerHTML = '<i class="fas fa-chevron-down me-2"></i>Voir plus';
                        } else {
                            icon.className = 'fas fa-chevron-up me-2';
                            text.innerHTML = '<i class="fas fa-chevron-up me-2"></i>Voir moins';
                        }
                    }
                });
            }
        });
        
        // Impression des messages
        function printMessages() {
            const printContent = document.querySelector('.card-modern').outerHTML;
            const originalContent = document.body.innerHTML;
            
            document.body.innerHTML = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>FUS Denim - Messages Contact</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        body { padding: 20px; font-family: 'Inter', sans-serif; }
                        .message-card { break-inside: avoid; margin-bottom: 20px; }
                        .print-header { margin-bottom: 30px; text-align: center; border-bottom: 2px solid #3B82F6; padding-bottom: 20px; }
                        .print-header h3 { color: #3B82F6; font-weight: 700; }
                        @media print {
                            .action-buttons { display: none !important; }
                            .btn { display: none !important; }
                            .expand-btn { display: none !important; }
                            .message-content { max-height: none !important; }
                        }
                    </style>
                </head>
                <body>
                    <div class="print-header">
                        <h3>FUS Denim - Messages Contact</h3>
                        <p>Imprimé le <?php echo date('d/m/Y H:i'); ?></p>
                    </div>
                    ${printContent}
                </body>
                </html>
            `;
            
            window.print();
            document.body.innerHTML = originalContent;
            window.location.reload();
        }
        
        // Recherche en temps réel
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            let timeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    this.form.submit();
                }, 500);
            });
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
        const currentPage = window.location.pathname.split('/').pop() || 'dashboard.php';
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.getAttribute('href') === currentPage) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Animation pour l'apparition des cartes
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.message-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(card);
        });

        // Animation des nombres pour les stats
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

        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const value = entry.target.querySelector('.stat-value');
                    if (value && !value.dataset.animated) {
                        const finalValue = parseInt(value.textContent.replace(/\s/g, ''));
                        animateValue(value, 0, finalValue, 800);
                        value.dataset.animated = 'true';
                    }
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.stat-box').forEach(box => statsObserver.observe(box));
    </script>
</body>
</html>