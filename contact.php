<?php
session_start();
$page_title = "Contact FUS | Request B2B Access";
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
        
        /* Page Header adapté */
        .page-header {
            background: linear-gradient(135deg, rgba(13, 13, 13, 0.85) 0%, rgba(26, 26, 26, 0.9) 100%), 
                        url('https://images.unsplash.com/photo-1497366754035-f200968a6e72?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 10rem 0 6rem;
            margin-top: 76px;
            position: relative;
            overflow: hidden;
            min-height: 80vh;
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
        }
        
        .page-title {
            font-size: 4rem;
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
        
        /* Contact Cards - Style moderne */
        .contact-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 24px;
            padding: 3rem;
            height: 100%;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .contact-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(168, 85, 247, 0.05));
            opacity: 0;
            transition: opacity 0.4s;
        }
        
        .contact-card:hover::before {
            opacity: 1;
        }
        
        .contact-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        .contact-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            transition: transform 0.3s;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }
        
        .contact-card:hover .contact-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .contact-icon i {
            font-size: 2rem;
            color: white;
        }
        
        .contact-card h4 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--primary-black);
        }
        
        .contact-card p {
            color: var(--gray-600);
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .contact-card a {
            color: var(--accent-indigo);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
        }
        
        .contact-card a:hover {
            color: var(--accent-purple);
            transform: translateX(5px);
        }
        
        /* Form Container - Style moderne */
        .form-container {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 24px;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(168, 85, 247, 0.05));
            opacity: 0;
            transition: opacity 0.4s;
        }
        
        /* Form Tabs - Style moderne */
        .form-tabs {
            display: flex;
            border-bottom: 2px solid rgba(99, 102, 241, 0.1);
            margin-bottom: 2rem;
            position: relative;
        }
        
        .form-tab {
            padding: 1rem 2rem;
            background: none;
            border: none;
            font-weight: 600;
            color: var(--gray-500);
            position: relative;
            transition: all 0.3s;
            cursor: pointer;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.95rem;
        }
        
        .form-tab:hover {
            color: var(--accent-indigo);
        }
        
        .form-tab.active {
            color: var(--accent-indigo);
        }
        
        .form-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--accent-indigo), var(--accent-purple));
        }
        
        /* Form Content */
        .form-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-content.active {
            display: block;
        }
        
        /* Form Styling */
        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--gray-800);
            font-size: 0.95rem;
        }
        
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(99, 102, 241, 0.2);
            padding: 0.875rem 1rem;
            border-radius: 12px;
            transition: all 0.3s;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.95rem;
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.9);
            border-color: var(--accent-indigo);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            outline: none;
        }
        
        .form-text {
            color: var(--gray-500);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        
        /* Alert Box */
        .alert-info {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.05));
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-left: 4px solid var(--accent-indigo);
            border-radius: 12px;
            color: var(--gray-700);
        }
        
        .alert-info i {
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Checkbox Styling */
        .form-check-input {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(99, 102, 241, 0.3);
            width: 1.1em;
            height: 1.1em;
            margin-top: 0.25em;
        }
        
        .form-check-input:checked {
            background-color: var(--accent-indigo);
            border-color: var(--accent-indigo);
        }
        
        .form-check-input:focus {
            border-color: var(--accent-indigo);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25);
        }
        
        .form-check-label {
            color: var(--gray-700);
            font-size: 0.95rem;
        }
        
        .form-check-label a {
            color: var(--accent-indigo);
            text-decoration: none;
            font-weight: 600;
        }
        
        .form-check-label a:hover {
            color: var(--accent-purple);
            text-decoration: underline;
        }
        
        /* Success Message - Style moderne */
        .success-message {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.9), rgba(5, 150, 105, 0.9));
            color: white;
            padding: 3rem;
            border-radius: 24px;
            text-align: center;
            display: none;
            box-shadow: 0 20px 60px rgba(16, 185, 129, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .success-message::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .success-icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            background: white;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }
        
        /* Map Container - Style moderne */
        .map-container {
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            height: 300px;
            position: relative;
        }
        
        .map-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
            z-index: 1;
            pointer-events: none;
        }
        
        .map-container iframe {
            width: 100%;
            height: 100%;
            border: 0;
            filter: grayscale(20%);
            transition: filter 0.3s;
        }
        
        .map-container:hover iframe {
            filter: grayscale(0%);
        }
        
        /* Office Cards - Style moderne */
        .office-card {
            padding: 1.75rem;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            margin-bottom: 1rem;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .office-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(168, 85, 247, 0.05));
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .office-card:hover::before {
            opacity: 1;
        }
        
        .office-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.1);
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        .office-card h6 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--primary-black);
        }
        
        .office-card p {
            color: var(--gray-600);
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            line-height: 1.5;
        }
        
        /* Sticky sidebar */
        .sticky-sidebar {
            position: sticky;
            top: 100px;
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
        
        /* Responsive */
        @media (max-width: 991px) {
            .page-title {
                font-size: 3rem;
            }

            .section-title {
                font-size: 2.25rem;
            }
            
            .form-tabs {
                flex-wrap: wrap;
            }
            
            .form-tab {
                padding: 0.75rem 1.25rem;
                font-size: 0.9rem;
            }
            
            .contact-card,
            .form-container {
                padding: 2.5rem;
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
            
            .contact-card,
            .form-container {
                padding: 2rem;
            }
            
            .form-tabs {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .form-tab {
                text-align: left;
                padding: 1rem;
                border-radius: 12px;
                background: rgba(255, 255, 255, 0.5);
                margin-bottom: 0.5rem;
            }
            
            .form-tab.active::after {
                display: none;
            }
            
            .form-tab.active {
                background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
                border: 1px solid rgba(99, 102, 241, 0.2);
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
                        <a class="nav-link" href="collections.php">Collections</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="certifications.php">Certifications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="contact.php">Contact</a>
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
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="page-title fade-in-up">
                        Contact & <span class="highlight gradient-text">Request Access</span>
                    </h1>
                    <p class="page-subtitle fade-in-up">
                        Get in touch with our team or request access to our exclusive B2B portal for international clients.
                    </p>
                    <div class="d-flex flex-wrap gap-3 fade-in-up">
                        <a href="#contact-form" class="btn btn-modern">
                            <i class="fas fa-envelope me-2"></i>Send Message
                        </a>
                        <a href="#contact-cards" class="btn btn-outline-modern">
                            <i class="fas fa-phone me-2"></i>Contact Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Methods -->
    <section id="contact-cards" class="section-padding">
        <div class="container">
            <div class="section-header text-center">
                <span class="section-badge fade-in-up">Get in Touch</span>
                <h2 class="section-title fade-in-up">Contact Methods</h2>
                <p class="section-subtitle mx-auto fade-in-up">
                    Choose your preferred way to connect with our team for inquiries, support, or partnership opportunities.
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="contact-card fade-in-up">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h4 class="mb-3">Visit Us</h4>
                        <p>
                            Industrial Zone Sidi Rezig<br>
                            2035 Tunis, Tunisia
                        </p>
                        <a href="#map">
                            View on map <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="contact-card fade-in-up" style="transition-delay: 0.1s;">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h4 class="mb-3">Call Us</h4>
                        <p>
                            Main Office: +216 70 000 000<br>
                            Sales: +216 70 000 001
                        </p>
                        <p class="small text-gray-600">Mon-Fri: 8:00-18:00 CET</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="contact-card fade-in-up" style="transition-delay: 0.2s;">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h4 class="mb-3">Email Us</h4>
                        <p>
                            General: info@fus-denim.com<br>
                            Sales: sales@fus-denim.com<br>
                            Support: support@fus-denim.com
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form & Map -->
    <section id="contact-form" class="section-padding" style="background: var(--gray-50);">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-7">
                    <div class="form-container fade-in-up">
                        <!-- Form Tabs -->
                        <div class="form-tabs">
                            <button class="form-tab active" data-target="general-form">General Inquiry</button>
                            <button class="form-tab" data-target="access-form">Request B2B Access</button>
                            <button class="form-tab" data-target="sample-form">Request Samples</button>
                        </div>
                        
                        <!-- Success Message -->
                        <div class="success-message" id="success-message">
                            <div class="success-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 class="mb-3">Message Sent Successfully!</h3>
                            <p class="mb-0">Thank you for contacting FUS Denim. Our team will get back to you within 24 hours.</p>
                        </div>
                        
                        <!-- General Inquiry Form -->
                        <form id="general-form" class="form-content active">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Company Name</label>
                                    <input type="text" class="form-control">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Subject *</label>
                                <input type="text" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Message *</label>
                                <textarea class="form-control" rows="5" required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-modern">
                                <i class="fas fa-paper-plane me-2"></i>Send Message
                            </button>
                        </form>
                        
                        <!-- B2B Access Request Form -->
                        <form id="access-form" class="form-content">
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle me-2"></i>
                                B2B portal access is reserved for verified business clients. Our team will review your application within 48 hours.
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Job Title *</label>
                                    <input type="text" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Company Name *</label>
                                <input type="text" class="form-control" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Company Website *</label>
                                    <input type="url" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Company Size *</label>
                                    <select class="form-select" required>
                                        <option value="">Select...</option>
                                        <option>1-10 employees</option>
                                        <option>11-50 employees</option>
                                        <option>51-200 employees</option>
                                        <option>201-1000 employees</option>
                                        <option>1000+ employees</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Country *</label>
                                <select class="form-select" required>
                                    <option value="">Select country...</option>
                                    <option>France</option>
                                    <option>Germany</option>
                                    <option>Poland</option>
                                    <option>Italy</option>
                                    <option>Spain</option>
                                    <option>United Kingdom</option>
                                    <option>Other European</option>
                                    <option>North America</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Business Type *</label>
                                <select class="form-select" required>
                                    <option value="">Select...</option>
                                    <option>Fashion Brand</option>
                                    <option>Retailer</option>
                                    <option>Wholesaler</option>
                                    <option>Private Label</option>
                                    <option>Designer</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Annual Denim Order Volume (approx.) *</label>
                                <select class="form-select" required>
                                    <option value="">Select...</option>
                                    <option>Less than 1,000 units</option>
                                    <option>1,000 - 5,000 units</option>
                                    <option>5,000 - 20,000 units</option>
                                    <option>20,000 - 50,000 units</option>
                                    <option>50,000+ units</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Additional Information</label>
                                <textarea class="form-control" rows="3" placeholder="Tell us about your business and specific needs..."></textarea>
                            </div>
                            
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a> *
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-modern">
                                <i class="fas fa-user-plus me-2"></i>Submit Application
                            </button>
                        </form>
                        
                        <!-- Sample Request Form -->
                        <form id="sample-form" class="form-content">
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle me-2"></i>
                                Sample requests are available for qualified business clients. Please provide detailed information for faster processing.
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Company Name *</label>
                                    <input type="text" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Interested Collection</label>
                                <select class="form-select" multiple>
                                    <option>Heritage Raw Denim</option>
                                    <option>Tech Stretch Collection</option>
                                    <option>Eco Organic Collection</option>
                                    <option>Luxury Finish Collection</option>
                                    <option>Everyday Essentials</option>
                                    <option>Utility Collection</option>
                                </select>
                                <div class="form-text">Hold Ctrl/Cmd to select multiple collections</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Sample Type *</label>
                                <select class="form-select" required>
                                    <option value="">Select...</option>
                                    <option>Swatch Cards</option>
                                    <option>Full Garment Samples</option>
                                    <option>Technical Specifications</option>
                                    <option>All of the above</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Shipping Address *</label>
                                <textarea class="form-control" rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Project Timeline</label>
                                <select class="form-select">
                                    <option value="">Select...</option>
                                    <option>Urgent (within 2 weeks)</option>
                                    <option>Short-term (1-3 months)</option>
                                    <option>Medium-term (3-6 months)</option>
                                    <option>Long-term (6+ months)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Additional Notes</label>
                                <textarea class="form-control" rows="3" placeholder="Specific requirements, colors, sizes, or special finishes..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-modern">
                                <i class="fas fa-box-open me-2"></i>Request Samples
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="col-lg-5">
                    <div class="sticky-sidebar">
                        <!-- Map -->
                        <div class="map-container mb-4 fade-in-up" id="map">
                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d12783.715894957594!2d10.181530368224575!3d36.80653317338069!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x12fd337c5f567589%3A0x5f93e8e7c71f8d17!2sTunis%2C%20Tunisia!5e0!3m2!1sen!2s!4v1678900000000!5m2!1sen!2s" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
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

        // Form tab switching
        document.querySelectorAll('.form-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Update active tab
                document.querySelectorAll('.form-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Show corresponding form
                const targetId = this.getAttribute('data-target');
                document.querySelectorAll('.form-content').forEach(form => {
                    form.classList.remove('active');
                });
                document.getElementById(targetId).classList.add('active');
                
                // Hide success message if visible
                document.getElementById('success-message').style.display = 'none';
            });
        });
        
        // Form submission
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Show success message
                const successMessage = document.getElementById('success-message');
                successMessage.style.display = 'block';
                
                // Hide form
                this.style.display = 'none';
                
                // Reset form after 5 seconds
                setTimeout(() => {
                    successMessage.style.display = 'none';
                    this.style.display = 'block';
                    this.reset();
                }, 5000);
            });
        });
        
        // Check URL for action parameter
        const urlParams = new URLSearchParams(window.location.search);
        const action = urlParams.get('action');
        
        if (action === 'request_access') {
            document.querySelector('[data-target="access-form"]').click();
        }

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
    </script>
</body>
</html>