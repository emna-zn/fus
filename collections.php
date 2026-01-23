<?php
session_start();
$page_title = "Denim Collections | FUS Premium Denim";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* Variables et styles de base identiques à index.php */
        :root {
            --primary-black: #0D0D0D;
            --primary-dark: #1A1A1A;
            --accent-indigo: #6366F1;
            --accent-purple: #A855F7;
            --accent-cyan: #06B6D4;
            --accent-pink: #EC4899;
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
            color: var(--gray-900);
            line-height: 1.6;
            overflow-x: hidden;
            background: var(--white);
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            line-height: 1.2;
        }

        .gradient-text {
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple), var(--accent-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .gradient-text-alt {
            background: linear-gradient(135deg, var(--accent-pink), var(--accent-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Navigation identique à index.php */
        .navbar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        
        .navbar.scrolled {
            padding: 0.5rem 0;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
        }
        
        .navbar-brand {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.8rem;
            color: var(--primary-black) !important;
            letter-spacing: -1px;
            position: relative;
        }
        
        .navbar-brand::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 30px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-indigo), var(--accent-purple));
            border-radius: 2px;
        }
        
        .nav-link {
            font-weight: 500;
            color: var(--gray-700) !important;
            margin: 0 1rem;
            padding: 0.5rem 0 !important;
            position: relative;
            transition: all 0.3s;
            font-size: 0.95rem;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--accent-indigo), var(--accent-purple));
            transition: all 0.3s;
            transform: translateX(-50%);
        }
        
        .nav-link:hover::before,
        .nav-link.active::before {
            width: 100%;
        }
        
        .nav-link:hover {
            color: var(--accent-indigo) !important;
        }
        
        /* Boutons identiques à index.php */
        .btn-modern {
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple));
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3);
            position: relative;
            overflow: hidden;
            font-size: 0.95rem;
        }
        
        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-pink));
            transition: left 0.4s;
            z-index: -1;
        }
        
        .btn-modern:hover::before {
            left: 0;
        }
        
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.4);
            color: white;
        }
        
        .btn-outline-modern {
            color: var(--accent-indigo);
            border: 2px solid var(--accent-indigo);
            background: transparent;
            padding: 0.875rem 2rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            font-size: 0.95rem;
        }
        
        .btn-outline-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple));
            transition: left 0.4s;
            z-index: -1;
        }
        
        .btn-outline-modern:hover::before {
            left: 0;
        }
        
        .btn-outline-modern:hover {
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }
        
        /* Page Header moderne */
        .page-header {
            background: linear-gradient(135deg, #0D0D0D 0%, #1A1A1A 50%, #0D0D0D 100%);
            position: relative;
            overflow: hidden;
            padding: 8rem 0 4rem;
            margin-top: 76px;
            min-height: 70vh;
            display: flex;
            align-items: center;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(168, 85, 247, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(6, 182, 212, 0.1) 0%, transparent 50%);
            animation: gradient-shift 15s ease infinite;
        }

        @keyframes gradient-shift {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .page-grid {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                linear-gradient(rgba(99, 102, 241, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: grid-flow 20s linear infinite;
        }

        @keyframes grid-flow {
            0% { transform: translateY(0); }
            100% { transform: translateY(50px); }
        }
        
        .page-title {
            font-size: 4.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.1;
            color: white;
            letter-spacing: -2px;
        }

        .page-title .highlight {
            position: relative;
            display: inline-block;
        }

        .page-title .highlight::after {
            content: '';
            position: absolute;
            bottom: 10px;
            left: 0;
            width: 100%;
            height: 20px;
            background: linear-gradient(90deg, var(--accent-indigo), var(--accent-purple));
            opacity: 0.3;
            z-index: -1;
            border-radius: 4px;
        }
        
        .page-subtitle {
            font-size: 1.25rem;
            color: var(--gray-300);
            margin-bottom: 2.5rem;
            max-width: 600px;
            line-height: 1.7;
            font-weight: 400;
        }
        
        /* Sections identiques */
        .section-padding {
            padding: 7rem 0;
        }

        .section-header {
            margin-bottom: 4rem;
        }

        .section-badge {
            display: inline-block;
            padding: 0.5rem 1.25rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--accent-indigo);
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .section-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -1px;
        }

        .section-subtitle {
            font-size: 1.15rem;
            color: var(--gray-600);
            max-width: 700px;
            line-height: 1.7;
        }
        
        /* Filter Buttons - Style moderne amélioré */
        .filter-container {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
        }
        
        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
        }
        
        .filter-btn {
            background: white;
            border: 1px solid var(--gray-200);
            padding: 0.75rem 1.75rem;
            border-radius: 12px;
            color: var(--gray-700);
            transition: all 0.3s;
            font-weight: 600;
            font-size: 0.95rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .filter-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple));
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 1;
        }
        
        .filter-btn span {
            position: relative;
            z-index: 2;
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }
        
        .filter-btn:hover::before,
        .filter-btn.active::before {
            opacity: 1;
        }
        
        /* Collections Grid - Style moderne amélioré */
        .collection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2.5rem;
            margin-top: 3rem;
        }
        
        @media (max-width: 768px) {
            .collection-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }
        
        .collection-item {
            position: relative;
            border-radius: 24px;
            overflow: hidden;
            height: 500px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            opacity: 1;
            transform: translateY(0);
            transition: all 0.4s;
            background: white;
        }
        
        .collection-item.hidden {
            opacity: 0;
            transform: translateY(20px);
            height: 0;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        
        .collection-item:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.2);
        }
        
        .collection-image-container {
            position: relative;
            height: 70%;
            overflow: hidden;
        }
        
        .collection-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .collection-item:hover .collection-image {
            transform: scale(1.15);
        }
        
        .collection-content {
            padding: 2rem;
            height: 30%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .collection-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--accent-indigo);
            margin-bottom: 0.75rem;
        }
        
        .collection-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary-black);
            line-height: 1.3;
        }
        
        .collection-description {
            color: var(--gray-600);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .collection-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-100);
        }
        
        .season-badge {
            background: linear-gradient(135deg, var(--accent-pink), var(--accent-purple));
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .collection-details {
            color: var(--gray-500);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .collection-details i {
            color: var(--accent-indigo);
        }
        
        /* Hover overlay effect */
        .collection-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to top, rgba(13, 13, 13, 0.8) 0%, transparent 50%);
            opacity: 0;
            transition: opacity 0.4s;
            z-index: 1;
        }
        
        .collection-item:hover .collection-overlay {
            opacity: 1;
        }
        
        /* Login Prompt - Style moderne amélioré */
        .login-prompt {
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple));
            border-radius: 32px;
            padding: 5rem 3rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(99, 102, 241, 0.3);
            margin-top: 4rem;
        }

        .login-prompt::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .login-prompt::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .login-prompt h2 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 800;
        }

        .login-prompt p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            line-height: 1.7;
        }

        .btn-white {
            background: white;
            color: var(--accent-indigo);
            border: none;
            padding: 1rem 2.5rem;
            font-weight: 700;
            border-radius: 12px;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            font-size: 1rem;
        }

        .btn-white:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
            color: var(--accent-indigo);
        }
        
        .btn-outline-white {
            background: transparent;
            color: white;
            border: 2px solid white;
            padding: 1rem 2.5rem;
            font-weight: 700;
            border-radius: 12px;
            transition: all 0.3s;
            font-size: 1rem;
        }
        
        .btn-outline-white:hover {
            background: white;
            color: var(--accent-indigo);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        /* Footer identique à index.php */
        footer {
            background: var(--primary-black);
            color: var(--gray-300);
            position: relative;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .footer-brand {
            font-family: 'Syne', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1rem;
        }

        .footer-link {
            color: var(--gray-400);
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
            position: relative;
            padding-left: 0;
        }

        .footer-link::before {
            content: '';
            position: absolute;
            left: 0;
            bottom: -2px;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--accent-indigo), var(--accent-purple));
            transition: width 0.3s;
        }

        .footer-link:hover {
            color: white;
        }

        .footer-link:hover::before {
            width: 100%;
        }

        .social-link {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-400);
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .social-link:hover {
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple));
            color: white;
            transform: translateY(-3px);
            border-color: transparent;
        }
        
        /* Animations identiques à index.php */
        .fade-in-up {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .fade-in-up.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, var(--primary-black), var(--primary-dark));
            position: relative;
            overflow: hidden;
            padding: 6rem 0;
        }

        .stats-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 30% 50%, rgba(99, 102, 241, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 70% 50%, rgba(168, 85, 247, 0.1) 0%, transparent 50%);
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            transition: all 0.4s;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
            opacity: 0;
            transition: opacity 0.4s;
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.2);
        }
        
        .stat-number {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple), var(--accent-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            font-family: 'Syne', sans-serif;
        }
        
        .stat-label {
            font-size: 1rem;
            color: var(--gray-300);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
        }
        
        /* Responsive */
        @media (max-width: 991px) {
            .page-title {
                font-size: 3rem;
            }

            .section-title {
                font-size: 2.25rem;
            }
            
            .login-prompt {
                padding: 4rem 2rem;
            }
            
            .filter-buttons {
                gap: 0.5rem;
            }
            
            .filter-btn {
                padding: 0.6rem 1.5rem;
                font-size: 0.9rem;
            }
            
            .collection-item {
                height: 450px;
            }
        }

        @media (max-width: 767px) {
            .page-title {
                font-size: 2.5rem;
            }

            .section-title {
                font-size: 1.85rem;
            }

            .section-padding {
                padding: 4rem 0;
            }
            
            .login-prompt {
                padding: 3rem 1.5rem;
            }
            
            .login-prompt h2 {
                font-size: 2rem;
            }
            
            .collection-item {
                height: 400px;
            }
            
            .collection-content {
                padding: 1.5rem;
            }
            
            .filter-buttons {
                gap: 0.3rem;
            }
            
            .filter-btn {
                padding: 0.5rem 1.25rem;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation identique à index.php -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">FUS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="expertise.php">Expertise</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="collections.php">Collections</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="certifications.php">Certifications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-modern" href="login.php">
                            <i class="fas fa-arrow-right me-2"></i>Client Portal
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header">
        <div class="page-grid"></div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="page-title fade-in-up">
                        Premium Denim <span class="highlight gradient-text">Collections</span>
                    </h1>
                    <p class="page-subtitle fade-in-up">
                        Discover our curated denim collections. Each piece represents our commitment to quality, 
                        innovation, and craftsmanship. Login to access complete technical specifications and pricing.
                    </p>
                    <div class="d-flex flex-wrap gap-3 fade-in-up">
                        <a href="#collections" class="btn btn-modern">
                            <i class="fas fa-eye me-2"></i>Explore Collections
                        </a>
                        <a href="#login-prompt" class="btn btn-outline-modern">
                            <i class="fas fa-lock me-2"></i>Request Access
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 mt-5 mt-lg-0 fade-in-up">
                    <div class="text-center">
                        <div style="width: 200px; height: 200px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(168, 85, 247, 0.2)); border-radius: 24px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-layer-group gradient-text" style="font-size: 4rem;"></i>
                        </div>
                        <p class="text-muted mt-3">6 Collections • 50+ Fabrics • Premium Quality</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Collections Stats -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card fade-in-up">
                        <div class="stat-number">6</div>
                        <div class="stat-label">Collections</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card fade-in-up" style="transition-delay: 0.1s;">
                        <div class="stat-number">50+</div>
                        <div class="stat-label">Fabric Options</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card fade-in-up" style="transition-delay: 0.2s;">
                        <div class="stat-number">15</div>
                        <div class="stat-label">Finishing Techniques</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card fade-in-up" style="transition-delay: 0.3s;">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Support</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Filter Section -->
    <section id="collections" class="section-padding">
        <div class="container">
            <div class="section-header text-center">
                <span class="section-badge fade-in-up">Curated Selection</span>
                <h2 class="section-title fade-in-up">Premium Denim Collections</h2>
                <p class="section-subtitle mx-auto fade-in-up">
                    Filter by category to explore our specialized denim lines, each crafted with distinct characteristics 
                    and innovative techniques.
                </p>
            </div>
            
            <div class="filter-container fade-in-up">
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="all">
                        <span>All Collections</span>
                    </button>
                    <button class="filter-btn" data-filter="heritage">
                        <span>Heritage</span>
                    </button>
                    <button class="filter-btn" data-filter="modern">
                        <span>Modern</span>
                    </button>
                    <button class="filter-btn" data-filter="sustainable">
                        <span>Sustainable</span>
                    </button>
                    <button class="filter-btn" data-filter="premium">
                        <span>Premium</span>
                    </button>
                    <button class="filter-btn" data-filter="casual">
                        <span>Casual</span>
                    </button>
                </div>
            </div>
            
            <div class="collection-grid">
                <!-- Heritage Collection -->
                <div class="collection-item fade-in-up" data-category="heritage premium">
                    <div class="collection-image-container">
                        <img src="https://images.unsplash.com/photo-1541099649105-f69ad21f3246?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                             alt="Heritage Raw Denim" class="collection-image">
                        <div class="collection-overlay"></div>
                    </div>
                    <div class="collection-content">
                        <div>
                            <span class="collection-badge">
                                <i class="fas fa-star me-2"></i>Heritage
                            </span>
                            <h3 class="collection-title">Raw Selvedge Collection</h3>
                            
                        </div>
                        
                    </div>
                </div>
                
                <!-- Modern Collection -->
                <div class="collection-item fade-in-up" data-category="modern" style="transition-delay: 0.1s;">
                    <div class="collection-image-container">
                        <img src="https://images.unsplash.com/photo-1582418702059-97ebafb35d09?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                             alt="Modern Stretch Denim" class="collection-image">
                        <div class="collection-overlay"></div>
                    </div>
                    <div class="collection-content">
                        <div>
                            <span class="collection-badge" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(59, 130, 246, 0.1)); color: var(--accent-cyan);">
                                <i class="fas fa-bolt me-2"></i>Modern
                            </span>
                            <h3 class="collection-title">Tech Stretch Collection</h3>
                            
                        </div>
                        
                    </div>
                </div>
                
                <!-- Sustainable Collection -->
                <div class="collection-item fade-in-up" data-category="sustainable" style="transition-delay: 0.2s;">
                    <div class="collection-image-container">
                        <img src="https://capecodelegues.goodplanet.org/media/images/AdobeStock_282280661-min.max-900x600.jpg" 
                             alt="Eco Denim" class="collection-image">
                        <div class="collection-overlay"></div>
                    </div>
                    <div class="collection-content">
                        <div>
                            <span class="collection-badge" style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(16, 185, 129, 0.1)); color: #10B981;">
                                <i class="fas fa-leaf me-2"></i>Sustainable
                            </span>
                            <h3 class="collection-title">Eco Organic Collection</h3>
                            
                        </div>
                        
                    </div>
                </div>
                
                <!-- Premium Collection -->
                <div class="collection-item fade-in-up" data-category="premium" style="transition-delay: 0.3s;">
                    <div class="collection-image-container">
                        <img src="https://images.unsplash.com/photo-1520006403909-838d6b92c22e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                             alt="Premium Denim" class="collection-image">
                        <div class="collection-overlay"></div>
                    </div>
                    <div class="collection-content">
                        <div>
                            <span class="collection-badge" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.1)); color: #F59E0B;">
                                <i class="fas fa-gem me-2"></i>Premium
                            </span>
                            <h3 class="collection-title">Luxury Finish Collection</h3>
                            
                        </div>
                        
                    </div>
                </div>
                
                <!-- Casual Collection -->
                <div class="collection-item fade-in-up" data-category="casual" style="transition-delay: 0.4s;">
                    <div class="collection-image-container">
                        <img src="https://images.unsplash.com/photo-1542272604-787c3835535d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                             alt="Casual Denim" class="collection-image">
                        <div class="collection-overlay"></div>
                    </div>
                    <div class="collection-content">
                        <div>
                            <span class="collection-badge" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1)); color: var(--accent-indigo);">
                                <i class="fas fa-tshirt me-2"></i>Casual
                            </span>
                            <h3 class="collection-title">Everyday Essentials</h3>
                            
                        </div>
                        
                    </div>
                </div>
                
                <!-- Workwear Collection -->
                <div class="collection-item fade-in-up" data-category="heritage" style="transition-delay: 0.5s;">
                    <div class="collection-image-container">
                        <img src="https://images.unsplash.com/photo-1576566588028-4147f3842f27?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                             alt="Workwear Denim" class="collection-image">
                        <div class="collection-overlay"></div>
                    </div>
                    <div class="collection-content">
                        <div>
                            <span class="collection-badge" style="background: linear-gradient(135deg, rgba(107, 114, 128, 0.1), rgba(75, 85, 99, 0.1)); color: var(--gray-600);">
                                <i class="fas fa-tools me-2"></i>Workwear
                            </span>
                            <h3 class="collection-title">Utility Collection</h3>
                            
                        </div>
                       
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Prompt -->
    <section id="login-prompt" class="section-padding">
        <div class="container">
            <div class="login-prompt fade-in-up">
                <div class="row justify-content-center">
                    <div class="col-lg-8 text-center">
                        <h2 class="mb-4">Access Complete Product Details</h2>
                        <p class="mb-4">
                            Our client portal provides detailed technical specifications, pricing, minimum order quantities, 
                            and exclusive collections not shown publicly.
                        </p>
                        <div class="d-flex flex-wrap gap-3 justify-content-center">
                            <a href="login.php" class="btn btn-white">
                                <i class="fas fa-sign-in-alt me-2"></i>Client Login
                            </a>
                            <a href="contact.php?action=request_access" class="btn btn-outline-white">
                                <i class="fas fa-user-plus me-2"></i>Request Access
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer identique à index.php -->
    <footer class="pt-5 pb-4">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <div class="footer-brand">FUS</div>
                    <p class="mb-4" style="color: var(--gray-400);">Premium denim manufacturing for discerning international brands.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4">
                    <h6 class="mb-4" style="color: white; font-weight: 700; font-size: 0.95rem;">Company</h6>
                    <ul class="list-unstyled">
                        <li class="mb-3"><a href="about.php" class="footer-link">About Us</a></li>
                        <li class="mb-3"><a href="expertise.php" class="footer-link">Our Expertise</a></li>
                        <li class="mb-3"><a href="certifications.php" class="footer-link">Certifications</a></li>
                        <li><a href="contact.php" class="footer-link">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-4">
                    <h6 class="mb-4" style="color: white; font-weight: 700; font-size: 0.95rem;">Client Portal</h6>
                    <ul class="list-unstyled">
                        <li class="mb-3"><a href="login.php" class="footer-link">Client Login</a></li>
                        <li class="mb-3"><a href="contact.php?action=request_access" class="footer-link">Request Access</a></li>
                        <li class="mb-3"><a href="#" class="footer-link">FAQ</a></li>
                        <li><a href="#" class="footer-link">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-4">
                    <h6 class="mb-4" style="color: white; font-weight: 700; font-size: 0.95rem;">Contact</h6>
                    <ul class="list-unstyled">
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-map-marker-alt mt-1 me-3 gradient-text"></i>
                            <span>Tunis, Tunisia</span>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-phone mt-1 me-3 gradient-text"></i>
                            <span>+216 XX XXX XXX</span>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-envelope mt-1 me-3 gradient-text"></i>
                            <span>contact@fus-denim.com</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <hr style="border-color: rgba(255, 255, 255, 0.1); margin: 3rem 0 2rem;">
            
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0" style="color: var(--gray-500); font-size: 0.9rem;">
                        &copy; 2026 Fashion Unique Solutions. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0" style="color: var(--gray-500); font-size: 0.9rem;">
                        Crafted with excellence for global fashion
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Identique à index.php
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });

        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in-up').forEach(el => observer.observe(el));

        // Filter functionality améliorée
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Animation du bouton
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
                
                // Update active button
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                const items = document.querySelectorAll('.collection-item');
                
                items.forEach((item, index) => {
                    setTimeout(() => {
                        if (filter === 'all' || item.getAttribute('data-category').includes(filter)) {
                            item.classList.remove('hidden');
                            setTimeout(() => {
                                item.style.opacity = '1';
                                item.style.transform = 'translateY(0)';
                            }, 10);
                        } else {
                            item.style.opacity = '0';
                            item.style.transform = 'translateY(20px)';
                            setTimeout(() => {
                                item.classList.add('hidden');
                            }, 300);
                        }
                    }, index * 50);
                });
            });
        });

        // Smooth scroll pour les ancres
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    window.scrollTo({
                        top: target.offsetTop - 100,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Animation des cartes au survol
        document.querySelectorAll('.collection-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.zIndex = '10';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.zIndex = '1';
            });
        });

        // Animation des stats
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counters = entry.target.querySelectorAll('.stat-number');
                    counters.forEach((counter, index) => {
                        const target = parseInt(counter.textContent);
                        const speed = 200;
                        let count = 0;

                        const updateCount = () => {
                            count += target / speed;
                            if (count < target) {
                                counter.textContent = Math.ceil(count);
                                requestAnimationFrame(updateCount);
                            } else {
                                counter.textContent = target;
                            }
                        };
                        updateCount();
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        document.querySelector('.stats-section').forEach(section => statsObserver.observe(section));
    </script>
</body>
</html>