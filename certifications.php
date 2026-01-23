<?php
session_start();
$page_title = "Certifications & Compliance | FUS Premium Denim";
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
                        url('https://images.unsplash.com/photo-1490481651871-ab68de25d43d?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
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
        
        /* Certification Cards - Style moderne */
        .certification-card {
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

        .certification-card::before {
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
        
        .certification-card:hover::before {
            opacity: 1;
        }
        
        .certification-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        .certification-logo {
            height: 120px;
            margin-bottom: 2rem;
            filter: grayscale(100%);
            transition: filter 0.4s;
            object-fit: contain;
        }
        
        .certification-card:hover .certification-logo {
            filter: grayscale(0);
        }
        
        .certification-badge {
            display: inline-block;
            padding: 0.5rem 1.25rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--accent-indigo);
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .certification-card h4 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--primary-black);
        }
        
        .certification-card p {
            color: var(--gray-600);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .certification-details {
            background: rgba(255, 255, 255, 0.5);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .certification-details small {
            color: var(--gray-500);
            font-size: 0.85rem;
            display: block;
            line-height: 1.5;
        }
        
        /* Compliance Items - Style moderne */
        .compliance-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            border-left: 4px solid var(--accent-indigo);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .compliance-item::before {
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
        
        .compliance-item:hover::before {
            opacity: 1;
        }
        
        .compliance-item:hover {
            transform: translateX(5px);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.1);
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        .compliance-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.5rem;
            flex-shrink: 0;
            transition: transform 0.3s;
        }
        
        .compliance-item:hover .compliance-icon {
            transform: scale(1.1);
        }
        
        .compliance-icon i {
            font-size: 1.5rem;
            color: white;
        }
        
        .compliance-content h4 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--primary-black);
        }
        
        .compliance-content p {
            color: var(--gray-600);
            margin: 0;
            line-height: 1.6;
        }
        
        /* Environmental Stats - Style moderne */
        .environmental-stat {
            text-align: center;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 24px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .environmental-stat::before {
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
        
        .environmental-stat:hover::before {
            opacity: 1;
        }
        
        .environmental-stat:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        .stat-number {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple), var(--accent-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            line-height: 1;
        }
        
        .stat-label {
            color: var(--accent-indigo);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 0.75rem;
            font-weight: 700;
        }
        
        .environmental-stat p {
            color: var(--gray-600);
            font-size: 0.95rem;
            line-height: 1.6;
            margin: 0;
        }
        
        /* Quality Assurance Section */
        .quality-section {
            background: linear-gradient(135deg, rgba(13, 13, 13, 0.95) 0%, rgba(26, 26, 26, 0.95) 100%);
            border-radius: 32px;
            padding: 5rem 4rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
        }
        
        .quality-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .quality-section h2 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 800;
        }
        
        .quality-section p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: 2rem;
        }
        
        .quality-section ul {
            list-style: none;
            padding: 0;
            margin-bottom: 3rem;
        }
        
        .quality-section ul li {
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
        }
        
        .quality-section ul li i {
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        
        .quality-image {
            position: relative;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
            transform: perspective(1000px) rotateY(5deg);
            transition: transform 0.6s;
        }
        
        .quality-image:hover {
            transform: perspective(1000px) rotateY(0deg);
        }
        
        .quality-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            display: block;
        }
        
        .quality-badge {
            position: absolute;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 1.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            top: 20px;
            left: 20px;
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
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
            
            .quality-section {
                padding: 4rem 3rem;
            }
            
            .certification-logo {
                height: 100px;
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
            
            .quality-section {
                padding: 3rem 2rem;
            }
            
            .quality-section h2 {
                font-size: 2rem;
            }
            
            .certification-card,
            .compliance-item,
            .environmental-stat {
                padding: 2rem;
            }
            
            .compliance-item {
                flex-direction: column;
                text-align: center;
            }
            
            .compliance-icon {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .stat-number {
                font-size: 2.5rem;
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
                        <a class="nav-link active" href="certifications.php">Certifications</a>
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
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="page-title fade-in-up">
                        Certifications & <span class="highlight gradient-text">Compliance</span>
                    </h1>
                    <p class="page-subtitle fade-in-up">
                        Our commitment to quality, safety, and sustainability is verified by international certifications 
                        and compliance with the highest industry standards.
                    </p>
                    <div class="d-flex flex-wrap gap-3 fade-in-up">
                        <a href="#certifications" class="btn btn-modern">
                            <i class="fas fa-certificate me-2"></i>View Certifications
                        </a>
                        <a href="#environment" class="btn btn-outline-modern">
                            <i class="fas fa-leaf me-2"></i>Environmental Impact
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Certifications -->
    <section id="certifications" class="section-padding">
        <div class="container">
            <div class="section-header text-center">
                <span class="section-badge fade-in-up">Certified Excellence</span>
                <h2 class="section-title fade-in-up">International Certifications</h2>
                <p class="section-subtitle mx-auto fade-in-up">
                    Our manufacturing processes and products meet the highest international standards for quality, 
                    safety, and sustainability.
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="certification-card fade-in-up">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/0/0e/Oeko-tex_logo.svg/1280px-Oeko-tex_logo.svg.png" 
                             alt="OEKO-TEX" class="certification-logo">
                        <span class="certification-badge">Textile Safety</span>
                        <h4 class="mb-3">OEKO-TEX Standard 100</h4>
                        <p>
                            Certification verifying that our fabrics are free from harmful substances and safe for human health.
                        </p>
                        <div class="certification-details">
                            <small><strong>Certification #:</strong> FUS-OTX-2023-001</small>
                            <small><strong>Valid until:</strong> December 2025</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="certification-card fade-in-up" style="transition-delay: 0.1s;">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b0/ISO_9001.svg/1280px-ISO_9001.svg.png" 
                             alt="ISO 9001" class="certification-logo">
                        <span class="certification-badge">Quality Management</span>
                        <h4 class="mb-3">ISO 9001:2015</h4>
                        <p>
                            International standard for quality management systems, ensuring consistent product quality and customer satisfaction.
                        </p>
                        <div class="certification-details">
                            <small><strong>Certification #:</strong> FUS-ISO-2022-001</small>
                            <small><strong>Valid until:</strong> November 2024</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="certification-card fade-in-up" style="transition-delay: 0.2s;">
                        <img src="https://cdn.worldvectorlogo.com/logos/gots-1.svg" 
                             alt="GOTS" class="certification-logo">
                        <span class="certification-badge">Organic Textiles</span>
                        <h4 class="mb-3">Global Organic Textile Standard</h4>
                        <p>
                            Certification for organic fibers from harvesting through environmentally and socially responsible manufacturing.
                        </p>
                        <div class="certification-details">
                            <small><strong>Certification #:</strong> FUS-GOTS-2023-001</small>
                            <small><strong>Valid until:</strong> October 2024</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Compliance Standards -->
    <section class="section-padding" style="background: var(--gray-50);">
        <div class="container">
            <div class="section-header text-center">
                <span class="section-badge fade-in-up">Regulatory Compliance</span>
                <h2 class="section-title fade-in-up">Compliance Standards</h2>
                <p class="section-subtitle mx-auto fade-in-up">
                    We adhere to international regulations and standards across all aspects of our operations.
                </p>
            </div>
            
            <div class="row">
                <div class="col-lg-6">
                    <div class="compliance-item fade-in-up">
                        <div class="compliance-icon">
                            <i class="fas fa-flask"></i>
                        </div>
                        <div class="compliance-content">
                            <h4 class="mb-2">REACH Compliance</h4>
                            <p class="mb-0">
                                Full compliance with EU Regulation (EC) No 1907/2006 concerning the Registration, 
                                Evaluation, Authorization and Restriction of Chemicals.
                            </p>
                        </div>
                    </div>
                    
                    <div class="compliance-item fade-in-up" style="transition-delay: 0.1s;">
                        <div class="compliance-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <div class="compliance-content">
                            <h4 class="mb-2">Environmental Standards</h4>
                            <p class="mb-0">
                                Adherence to EU environmental regulations including wastewater treatment, 
                                energy consumption, and waste management protocols.
                            </p>
                        </div>
                    </div>
                    
                    <div class="compliance-item fade-in-up" style="transition-delay: 0.2s;">
                        <div class="compliance-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="compliance-content">
                            <h4 class="mb-2">Social Responsibility</h4>
                            <p class="mb-0">
                                Compliance with SA8000 standards for social accountability, including fair labor 
                                practices, safe working conditions, and ethical employment.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="compliance-item fade-in-up" style="transition-delay: 0.3s;">
                        <div class="compliance-icon">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                        <div class="compliance-content">
                            <h4 class="mb-2">Legal Compliance</h4>
                            <p class="mb-0">
                                Full compliance with Tunisian labor laws, export regulations, and international 
                                trade agreements governing textile manufacturing.
                            </p>
                        </div>
                    </div>
                    
                    <div class="compliance-item fade-in-up" style="transition-delay: 0.4s;">
                        <div class="compliance-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="compliance-content">
                            <h4 class="mb-2">Product Safety</h4>
                            <p class="mb-0">
                                Compliance with CPSIA (Consumer Product Safety Improvement Act) and EU product 
                                safety directives for textiles and apparel.
                            </p>
                        </div>
                    </div>
                    
                    <div class="compliance-item fade-in-up" style="transition-delay: 0.5s;">
                        <div class="compliance-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="compliance-content">
                            <h4 class="mb-2">Labeling Requirements</h4>
                            <p class="mb-0">
                                Adherence to international labeling standards including fiber content, 
                                care instructions, and country of origin labeling requirements.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Environmental Impact -->
    <section id="environment" class="section-padding">
        <div class="container">
            <div class="section-header text-center">
                <span class="section-badge fade-in-up">Sustainability</span>
                <h2 class="section-title fade-in-up">Environmental Commitment</h2>
                <p class="section-subtitle mx-auto fade-in-up">
                    Our sustainable practices demonstrate our commitment to reducing environmental impact.
                </p>
            </div>
            
            <div class="row text-center">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="environmental-stat fade-in-up">
                        <div class="stat-number">65%</div>
                        <div class="stat-label">Water Recycled</div>
                        <p>Closed-loop water system in washing processes</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="environmental-stat fade-in-up" style="transition-delay: 0.1s;">
                        <div class="stat-number">40%</div>
                        <div class="stat-label">Energy Reduction</div>
                        <p>Solar power implementation since 2020</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="environmental-stat fade-in-up" style="transition-delay: 0.2s;">
                        <div class="stat-number">95%</div>
                        <div class="stat-label">Waste Recycled</div>
                        <p>Fabric scraps and production waste</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="environmental-stat fade-in-up" style="transition-delay: 0.3s;">
                        <div class="stat-number">0%</div>
                        <div class="stat-label">Harmful Chemicals</div>
                        <p>Eco-friendly dyes and treatments only</p>
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