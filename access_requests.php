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

if (isset($_POST['process_request'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'];
    $notes = trim($_POST['admin_notes'] ?? '');
    
    $stmt = $conn->prepare("SELECT * FROM access_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $stmt->close();
    
    if ($request) {
        $stmt = $conn->prepare("
            UPDATE access_requests 
            SET status = ?, processed_at = NOW(), admin_notes = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("ssi", $action, $notes, $request_id);
        
        if ($stmt->execute()) {
            if ($action == 'approved') {
                $temp_password = bin2hex(random_bytes(4));
                $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
                
                $stmt2 = $conn->prepare("
                    INSERT INTO users (email, password, company_name, country, contact_person, phone, role, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, 'client', 1)
                ");
                $stmt2->bind_param(
                    "ssssss",
                    $request['email'],
                    $hashed_password,
                    $request['company_name'],
                    $request['country'],
                    $request['contact_person'],
                    $request['phone']
                );
                
                if ($stmt2->execute()) {
                    $user_id = $stmt2->insert_id;
                    
                    $message = "Demande approuvée. Compte créé (ID: $user_id). Mot de passe temporaire: $temp_password";
                    $message_type = 'success';
                } else {
                    $message = "Erreur lors de la création du compte.";
                    $message_type = 'danger';
                }
                $stmt2->close();
            } else {
                $message = "Demande rejetée.";
                $message_type = 'info';
            }
        } else {
            $message = "Erreur lors du traitement de la demande.";
            $message_type = 'danger';
        }
        $stmt->close();
    }
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';

$query = "SELECT * FROM access_requests WHERE 1=1";
if ($status_filter) {
    $query .= " AND status = '$status_filter'";
}
$query .= " ORDER BY requested_at DESC";

$result = $conn->query($query);

$requests = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

$stats_result = $conn->query("
    SELECT status, COUNT(*) as count 
    FROM access_requests 
    GROUP BY status
");
$stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
if ($stats_result) {
    while($row = $stats_result->fetch_assoc()) {
        $stats[$row['status']] = $row['count'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demandes d'Accès - Tableau de bord Admin - FUS Denim</title>
    
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
            background: linear-gradient(90deg, var(--accent-5), #EF4444);
        }

        .stat-box:nth-child(2)::before {
            background: linear-gradient(90deg, var(--accent-4), var(--accent-1));
        }

        .stat-box:nth-child(3)::before {
            background: linear-gradient(90deg, #EF4444, var(--accent-3));
        }

        .stat-box:nth-child(4)::before {
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
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
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent-5);
        }

        .stat-box:nth-child(2) .stat-icon {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
        }

        .stat-box:nth-child(3) .stat-icon {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
        }

        .stat-box:nth-child(4) .stat-icon {
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-1);
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

        .btn-warning {
            background: linear-gradient(135deg, var(--accent-5), #D97706);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #EF4444, #DC2626);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
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

        /* Status Tabs */
        .status-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            border-bottom: 2px solid var(--gray-200);
            padding-bottom: 0.5rem;
        }

        .status-tab {
            padding: 0.75rem 1.5rem;
            border: none;
            background: transparent;
            color: var(--gray-600);
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
        }

        .status-tab:hover {
            background: var(--gray-100);
            color: var(--primary);
        }

        .status-tab.active {
            background: var(--white);
            color: var(--accent-1);
            box-shadow: var(--shadow-sm);
        }

        .status-tab.active::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--accent-1);
            border-radius: 3px;
        }

        /* Request Cards */
        .request-card {
            background: var(--white);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            transition: all 0.3s ease;
            border-left: 4px solid var(--gray-300);
        }

        .request-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--accent-1);
        }

        .request-card.pending {
            border-left-color: var(--accent-5);
        }

        .request-card.approved {
            border-left-color: var(--accent-4);
        }

        .request-card.rejected {
            border-left-color: #EF4444;
        }

        /* Company Info */
        .company-info {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .company-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .company-details {
            flex-grow: 1;
        }

        .company-name {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .company-meta {
            color: var(--gray-600);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .company-meta i {
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

        .badge-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent-5);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .badge-approved {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-4);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .badge-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Message Box */
        .message-box {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 1.25rem;
            margin: 1rem 0;
            border-left: 4px solid var(--accent-1);
        }

        .message-box strong {
            color: var(--gray-900);
            margin-right: 0.5rem;
        }

        /* Request Date */
        .request-date {
            color: var(--gray-500);
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        .request-date i {
            margin-right: 0.25rem;
            color: var(--accent-1);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .btn-sm-modern {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            transition: all 0.3s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-sm-modern:hover {
            transform: translateY(-2px);
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

        .alert-modern.alert-info {
            background: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.3);
            color: var(--accent-1);
        }

        .alert-modern.alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
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

        .system-status {
            color: var(--accent-4);
            font-weight: 600;
        }

        /* Modal */
        .modal-modern .modal-content {
            border-radius: 16px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-xl);
        }

        .modal-modern .modal-header {
            border-bottom: 1px solid var(--gray-100);
            padding: 1.5rem;
        }

        .modal-modern .modal-footer {
            border-top: 1px solid var(--gray-100);
            padding: 1.5rem;
        }

        /* Form Controls */
        .form-control-modern {
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control-modern:focus {
            border-color: var(--accent-1);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
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

        .stat-box, .card-modern, .request-card, .alert-modern {
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

            .company-info {
                flex-direction: column;
            }

            .action-buttons {
                flex-direction: column;
                width: 100%;
            }

            .action-buttons .btn-sm-modern {
                width: 100%;
                justify-content: center;
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

            .status-tabs {
                gap: 0.25rem;
            }

            .status-tab {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }

            .card-modern {
                padding: 1.25rem;
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
            <a href="access_requests.php" class="nav-item active">
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
                <h1>Demandes d'Accès</h1>
                <p>Gérez les demandes d'accès au portail B2B</p>
            </div>
            <div class="header-actions">
                <div class="time-display">
                    <i class="fas fa-clock me-2"></i><?php echo date('d/m/Y • H:i'); ?>
                </div>
                <a href="clients.php" class="btn-outline-modern">
                    <i class="fas fa-users"></i> Voir les clients
                </a>
            </div>
        </div>

        <!-- Alert Message -->
        <?php if ($message): ?>
        <div class="alert-modern alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas <?php 
                    if ($message_type == 'success') echo 'fa-check-circle';
                    elseif ($message_type == 'info') echo 'fa-info-circle';
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
                    <div class="stat-label">En attente</div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                <div class="stat-trend">À traiter</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Approuvées</div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['approved']; ?></div>
                <div class="stat-trend">Validées</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Rejetées</div>
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $stats['rejected']; ?></div>
                <div class="stat-trend">Refusées</div>
            </div>

            <div class="stat-box">
                <div class="stat-header">
                    <div class="stat-label">Total demandes</div>
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo array_sum($stats); ?></div>
                <div class="stat-trend">Historique</div>
            </div>
        </div>

        <!-- Status Tabs -->
        <div class="status-tabs">
            <a class="status-tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>" 
               href="?status=pending">
                <i class="fas fa-clock"></i>
                En attente
                <?php if ($stats['pending'] > 0): ?>
                <span class="ms-1" style="background: var(--accent-5); color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.75rem;">
                    <?php echo $stats['pending']; ?>
                </span>
                <?php endif; ?>
            </a>
            <a class="status-tab <?php echo $status_filter == 'approved' ? 'active' : ''; ?>" 
               href="?status=approved">
                <i class="fas fa-check-circle"></i>
                Approuvées
            </a>
            <a class="status-tab <?php echo $status_filter == 'rejected' ? 'active' : ''; ?>" 
               href="?status=rejected">
                <i class="fas fa-times-circle"></i>
                Rejetées
            </a>
            <a class="status-tab <?php echo !$status_filter ? 'active' : ''; ?>" 
               href="?">
                <i class="fas fa-list"></i>
                Toutes
            </a>
        </div>

        <!-- Requests List -->
        <div class="card-modern">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-user-plus"></i> Demandes d'accès
                    <span class="ms-2 text-muted" style="font-size: 0.9rem; font-weight: 400;">
                        (<?php echo count($requests); ?> demande<?php echo count($requests) > 1 ? 's' : ''; ?>)
                    </span>
                </div>
            </div>
            
            <?php if (empty($requests)): ?>
            <div class="empty-state">
                <i class="fas fa-door-closed"></i>
                <h4 class="mt-3 mb-2">Aucune demande <?php echo $status_filter ? ucfirst($status_filter) : ""; ?></h4>
                <p class="text-muted">
                    <?php if ($status_filter == 'pending'): ?>
                    Aucune demande d'accès en attente de traitement.
                    <?php else: ?>
                    Aucune demande trouvée pour ce filtre.
                    <?php endif; ?>
                </p>
                <a href="?" class="btn-outline-modern mt-3">
                    <i class="fas fa-eye me-2"></i>Voir toutes les demandes
                </a>
            </div>
            <?php else: ?>
            <?php foreach($requests as $index => $request): ?>
            <div class="request-card <?php echo $request['status']; ?>" 
                 style="animation-delay: <?php echo ($index * 0.1) + 0.2; ?>s">
                <div class="company-info">
                    <div class="company-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="company-details">
                        <div class="company-name"><?php echo htmlspecialchars($request['company_name']); ?></div>
                        <div class="company-meta">
                            <div>
                                <i class="fas fa-user"></i>
                                <strong><?php echo htmlspecialchars($request['contact_person']); ?></strong>
                                • <i class="fas fa-globe"></i> <?php echo htmlspecialchars($request['country']); ?>
                            </div>
                            <div>
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($request['email']); ?>
                                <?php if ($request['phone']): ?>
                                • <i class="fas fa-phone"></i> <?php echo htmlspecialchars($request['phone']); ?>
                                <?php endif; ?>
                                <?php if ($request['website']): ?>
                                • <i class="fas fa-link"></i> 
                                <a href="<?php echo htmlspecialchars($request['website']); ?>" target="_blank" 
                                   class="text-decoration-none">
                                    Site web
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="ms-auto">
                        <span class="status-badge badge-<?php echo $request['status']; ?>">
                            <i class="fas <?php 
                                echo $request['status'] == 'pending' ? 'fa-clock' : 
                                     ($request['status'] == 'approved' ? 'fa-check' : 'fa-times');
                            ?>"></i>
                            <?php 
                            $status_labels = [
                                'pending' => 'En attente',
                                'approved' => 'Approuvée',
                                'rejected' => 'Rejetée'
                            ];
                            echo $status_labels[$request['status']]; 
                            ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($request['message']): ?>
                <div class="message-box">
                    <strong><i class="fas fa-comment me-1"></i>Message:</strong>
                    <?php echo nl2br(htmlspecialchars($request['message'])); ?>
                </div>
                <?php endif; ?>
                
                <div class="request-date">
                    <div>
                        <i class="fas fa-paper-plane"></i>
                        Demande envoyée le <?php echo date('d/m/Y à H:i', strtotime($request['requested_at'])); ?>
                    </div>
                    <?php if ($request['processed_at']): ?>
                    <div class="mt-1">
                        <i class="fas fa-check"></i>
                        Traitée le <?php echo date('d/m/Y à H:i', strtotime($request['processed_at'])); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($request['admin_notes']): ?>
                    <div class="mt-1">
                        <i class="fas fa-sticky-note"></i>
                        Note: <?php echo htmlspecialchars($request['admin_notes']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($request['status'] == 'pending'): ?>
                <div class="action-buttons">
                    <form method="POST" class="d-inline" id="approveForm<?php echo $request['id']; ?>">
                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                        <input type="hidden" name="action" value="approved">
                        <button type="submit" name="process_request" 
                                class="btn-modern btn-success btn-sm-modern"
                                onclick="return confirmApprove()">
                            <i class="fas fa-check-circle"></i>Approuver
                        </button>
                    </form>
                    
                    <button type="button" 
                            class="btn-modern btn-warning btn-sm-modern" 
                            onclick="showRejectModal(<?php echo $request['id']; ?>)">
                        <i class="fas fa-times-circle"></i>Rejeter
                    </button>
                    
                    <a href="mailto:<?php echo urlencode($request['email']); ?>" 
                       class="btn-outline-modern btn-sm-modern">
                        <i class="fas fa-envelope"></i>Contacter
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>
                <i class="fas fa-shield-alt" style="color: var(--accent-1);"></i>
                <strong>FUS Denim</strong> - Back-office Administrateur v1.0
                <span class="stats-badge ms-3">
                    <i class="fas fa-user-plus"></i>
                    <?php echo array_sum($stats); ?> demande<?php echo array_sum($stats) > 1 ? 's' : ''; ?> d'accès
                </span>
            </div>
            <div>
                <span class="system-status">
                    <i class="fas fa-circle"></i> Système opérationnel
                </span>
            </div>
        </div>
    </div>

    <!-- Modal pour rejeter une demande -->
    <div class="modal fade modal-modern" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rejeter la demande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="rejectForm">
                    <div class="modal-body">
                        <input type="hidden" name="request_id" id="rejectRequestId">
                        <input type="hidden" name="action" value="rejected">
                        <div class="mb-3">
                            <label for="rejectReason" class="form-label">Raison du rejet (optionnel)</label>
                            <textarea class="form-control form-control-modern" id="rejectReason" 
                                      name="admin_notes" rows="4" 
                                      placeholder="Indiquez la raison du rejet..."></textarea>
                            <div class="form-text">Cette note sera conservée pour référence</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="process_request" class="btn-modern btn-danger">
                            Confirmer le rejet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh toutes les 60 secondes
        setTimeout(function() {
            location.reload();
        }, 60000);

        // Modal pour rejeter
        function showRejectModal(requestId) {
            document.getElementById('rejectRequestId').value = requestId;
            document.getElementById('rejectReason').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
            modal.show();
            
            // Focus sur le textarea
            setTimeout(() => {
                document.getElementById('rejectReason').focus();
            }, 300);
        }
        
        // Confirmation pour l'approbation
        function confirmApprove() {
            return confirm('Êtes-vous sûr de vouloir approuver cette demande ? Un compte client sera créé avec un mot de passe temporaire.');
        }
        
        // Auto-expand textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
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

        // Animation pour l'apparition des cartes
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.request-card').forEach(card => {
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