<?php
session_start();
$page_title = "Denim Expertise | FUS Premium Denim Manufacturing";
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
                        url('https://images.unsplash.com/photo-1520006403909-838d6b92c22e?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
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
        
        /* Process Steps - Modern Design */
        .process-step {
            text-align: center;
            padding: 2rem;
            position: relative;
            z-index: 2;
        }
        
        .process-number {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 1.5rem;
            position: relative;
            z-index: 2;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
            transition: all 0.3s;
        }
        
        .process-step:hover .process-number {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.4);
        }
        
        .process-connector {
            position: absolute;
            top: 35px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--accent-indigo), var(--accent-purple), var(--accent-cyan));
            z-index: 1;
            opacity: 0.3;
        }
        
        .process-step h4 {
            font-size: 1.3rem;
            margin-bottom: 0.75rem;
            color: var(--primary-black);
        }
        
        .process-step p {
            color: var(--gray-600);
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        /* Fabric Cards - Glassmorphism */
        .fabric-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 24px;
            padding: 2.5rem;
            height: 100%;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .fabric-card::before {
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
        
        .fabric-card:hover::before {
            opacity: 1;
        }
        
        .fabric-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        .fabric-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple));
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            transition: transform 0.3s;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }
        
        .fabric-card:hover .fabric-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .fabric-icon i {
            font-size: 1.8rem;
            color: white;
        }

        .fabric-card h4 {
            font-size: 1.35rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .fabric-card p {
            color: var(--gray-600);
            margin-bottom: 1.25rem;
            line-height: 1.7;
        }
        
        .badge-modern {
            display: inline-block;
            padding: 0.4rem 0.9rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--accent-indigo);
            margin-right: 0.5rem;
            margin-top: 0.5rem;
        }
        
        /* Washing Techniques */
        .washing-technique {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 16px;
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--accent-indigo);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .washing-technique::before {
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
        
        .washing-technique:hover::before {
            opacity: 1;
        }
        
        .washing-technique:hover {
            transform: translateX(5px);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.1);
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        .washing-technique h5 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--primary-black);
            position: relative;
            z-index: 1;
        }
        
        .washing-technique p {
            color: var(--gray-600);
            margin: 0;
            font-size: 0.95rem;
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }
        
        /* Quality Control Steps */
        .qc-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 16px;
            transition: all 0.3s;
        }
        
        .qc-step:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.1);
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        .qc-number {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple));
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 1.5rem;
            flex-shrink: 0;
            font-size: 1.1rem;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }
        
        .qc-content h5 {
            font-size: 1.15rem;
            margin-bottom: 0.5rem;
            color: var(--primary-black);
        }
        
        .qc-content p {
            color: var(--gray-600);
            margin: 0;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        /* R&D Section */
        .rnd-section {
            background: linear-gradient(135deg, rgba(13, 13, 13, 0.95) 0%, rgba(26, 26, 26, 0.95) 100%);
            border-radius: 24px;
            padding: 4rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.2);
        }
        
        .rnd-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .rnd-section h2 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 800;
        }
        
        .rnd-section p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: 2rem;
        }
        
        .rnd-section ul {
            list-style: none;
            padding: 0;
        }
        
        .rnd-section ul li {
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
        }
        
        .rnd-section ul li i {
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        
        .rnd-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            transform: perspective(1000px) rotateY(-5deg);
            transition: transform 0.6s;
        }
        
        .rnd-image:hover {
            transform: perspective(1000px) rotateY(0deg);
        }
        
        .rnd-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            display: block;
        }
        
        .rnd-badge {
            position: absolute;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 1.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            top: 20px;
            right: 20px;
            animation: float 6s ease-in-out infinite;
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
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        /* Responsive */
        @media (max-width: 991px) {
            .page-title {
                font-size: 3rem;
            }

            .section-title {
                font-size: 2.25rem;
            }

            .process-connector {
                display: none;
            }
            
            .process-step {
                margin-bottom: 2rem;
            }
            
            .rnd-section {
                padding: 3rem;
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

            .fabric-card,
            .washing-technique,
            .qc-step {
                padding: 1.5rem;
            }
            
            .rnd-section {
                padding: 2rem;
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
                        <a class="nav-link active" href="expertise.php">Expertise</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="collections.php">Collections</a>
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
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="page-title fade-in-up">
                        Denim <span class="highlight gradient-text">Expertise</span>
                    </h1>
                    <p class="page-subtitle fade-in-up">
                        Mastery in every thread. Discover our specialized processes, innovative techniques, 
                        and commitment to quality that sets FUS apart in premium denim manufacturing.
                    </p>
                    <div class="d-flex flex-wrap gap-3 fade-in-up">
                        <a href="#process" class="btn btn-modern">
                            <i class="fas fa-industry me-2"></i>Manufacturing Process
                        </a>
                        <a href="#quality" class="btn btn-outline-modern">
                            <i class="fas fa-check-double me-2"></i>Quality Control
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Manufacturing Process -->
    <section id="process" class="section-padding">
        <div class="container">
            <div class="section-header text-center">
                <span class="section-badge fade-in-up">Our Process</span>
                <h2 class="section-title fade-in-up">Manufacturing Excellence</h2>
                <p class="section-subtitle mx-auto fade-in-up">
                    From raw material selection to finished product, each step is meticulously controlled to ensure 
                    premium quality and consistency.
                </p>
            </div>
            
            <div class="row position-relative">
                <div class="col-lg-3 col-md-6">
                    <div class="process-step fade-in-up">
                        <div class="process-connector d-none d-lg-block"></div>
                        <div class="process-number">1</div>
                        <h4>Material Selection</h4>
                        <p>Sourcing premium raw denim from trusted suppliers. Rigorous testing for weight, strength, and composition.</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="process-step fade-in-up" style="transition-delay: 0.1s;">
                        <div class="process-number">2</div>
                        <h4>Pattern Making</h4>
                        <p>Digital pattern creation using CAD technology. Precision cutting for optimal fabric utilization.</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="process-step fade-in-up" style="transition-delay: 0.2s;">
                        <div class="process-number">3</div>
                        <h4>Sewing & Assembly</h4>
                        <p>Expert craftsmanship using industrial sewing machines. Specialized stitching techniques for durability.</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="process-step fade-in-up" style="transition-delay: 0.3s;">
                        <div class="process-number">4</div>
                        <h4>Washing & Finishing</h4>
                        <p>Artisanal washing techniques. Laser finishing and hand-sanding for unique character.</p>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-lg-3 col-md-6">
                    <div class="process-step fade-in-up">
                        <div class="process-number">5</div>
                        <h4>Quality Control</h4>
                        <p>12-point inspection system. Measurement accuracy, stitch quality, and finish verification.</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="process-step fade-in-up" style="transition-delay: 0.1s;">
                        <div class="process-number">6</div>
                        <h4>Final Pressing</h4>
                        <p>Steam pressing for perfect shape retention. Temperature-controlled process to preserve fabric integrity.</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="process-step fade-in-up" style="transition-delay: 0.2s;">
                        <div class="process-number">7</div>
                        <h4>Packaging</h4>
                        <p>Eco-friendly packaging materials. Brand-specific labeling and tagging according to client requirements.</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="process-step fade-in-up" style="transition-delay: 0.3s;">
                        <div class="process-number">8</div>
                        <h4>Shipping</h4>
                        <p>Efficient logistics coordination. Real-time tracking and documentation for international shipments.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Fabric Specialization -->
    <section class="section-padding" style="background: var(--gray-50);">
        <div class="container">
            <div class="section-header text-center">
                <span class="section-badge fade-in-up">Materials</span>
                <h2 class="section-title fade-in-up">Fabric Specialization</h2>
                <p class="section-subtitle mx-auto fade-in-up">
                    We work with diverse fabric types to meet every design requirement and market demand.
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="fabric-card fade-in-up">
                        <div class="fabric-icon">
                            <i class="fas fa-weight"></i>
                        </div>
                        <h4>Weight Varieties</h4>
                        <p>
                            We work with a wide range of denim weights, from lightweight 4oz summer fabrics to heavyweight 16oz raw denim, 
                            each selected for specific applications and seasons.
                        </p>
                        <div class="mt-3">
                            <span class="badge-modern">4-6oz</span>
                            <span class="badge-modern">8-10oz</span>
                            <span class="badge-modern">12-16oz</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="fabric-card fade-in-up" style="transition-delay: 0.1s;">
                        <div class="fabric-icon">
                            <i class="fas fa-recycle"></i>
                        </div>
                        <h4>Sustainable Fabrics</h4>
                        <p>
                            Our eco-friendly range includes organic cotton, recycled denim, and innovative blends with Tencel, 
                            hemp, and recycled polyester for reduced environmental impact.
                        </p>
                        <div class="mt-3">
                            <span class="badge-modern">Organic Cotton</span>
                            <span class="badge-modern">Recycled Blend</span>
                            <span class="badge-modern">Tencel®</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="fabric-card fade-in-up" style="transition-delay: 0.2s;">
                        <div class="fabric-icon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <h4>Specialty Finishes</h4>
                        <p>
                            Innovative fabric treatments including stretch denim, coated finishes, waxed effects, and performance 
                            enhancements for water resistance and durability.
                        </p>
                        <div class="mt-3">
                            <span class="badge-modern">Stretch</span>
                            <span class="badge-modern">Coated</span>
                            <span class="badge-modern">Performance</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Washing Techniques -->
    <section class="section-padding">
        <div class="container">
            <div class="section-header text-center">
                <span class="section-badge fade-in-up">Techniques</span>
                <h2 class="section-title fade-in-up">Advanced Washing Techniques</h2>
                <p class="section-subtitle mx-auto fade-in-up">
                    Our artisanal washing methods create unique character and premium finishes for every denim piece.
                </p>
            </div>
            
            <div class="row">
                <div class="col-lg-6">
                    <div class="washing-technique fade-in-up">
                        <h5 class="mb-2">Stone Wash</h5>
                        <p class="mb-0">Traditional technique using pumice stones to achieve natural abrasion and soft hand feel.</p>
                    </div>
                    
                    <div class="washing-technique fade-in-up" style="transition-delay: 0.1s;">
                        <h5 class="mb-2">Enzyme Wash</h5>
                        <p class="mb-0">Eco-friendly process using natural enzymes for controlled indigo removal and softness.</p>
                    </div>
                    
                    <div class="washing-technique fade-in-up" style="transition-delay: 0.2s;">
                        <h5 class="mb-2">Acid Wash</h5>
                        <p class="mb-0">Chemical treatment with pumice stones soaked in chlorine for high-contrast marble effects.</p>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="washing-technique fade-in-up" style="transition-delay: 0.3s;">
                        <h5 class="mb-2">Laser Finishing</h5>
                        <p class="mb-0">Precision laser technology for intricate patterns, whiskers, and worn effects with water conservation.</p>
                    </div>
                    
                    <div class="washing-technique fade-in-up" style="transition-delay: 0.4s;">
                        <h5 class="mb-2">Ozone Treatment</h5>
                        <p class="mb-0">Advanced eco-process using ozone gas for color fading without chemicals or water.</p>
                    </div>
                    
                    <div class="washing-technique fade-in-up" style="transition-delay: 0.5s;">
                        <h5 class="mb-2">Hand Sanding</h5>
                        <p class="mb-0">Artisanal technique for creating authentic worn effects on specific garment areas.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quality Control -->
    <section id="quality" class="section-padding" style="background: var(--gray-50);">
        <div class="container">
            <div class="section-header text-center">
                <span class="section-badge fade-in-up">Quality Assurance</span>
                <h2 class="section-title fade-in-up">12-Point Quality Control System</h2>
                <p class="section-subtitle mx-auto fade-in-up">
                    Every garment undergoes rigorous inspection at multiple stages to ensure perfection.
                </p>
            </div>
            
            <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="qc-step fade-in-up">
                        <div class="qc-number">1</div>
                        <div class="qc-content">
                            <h5>Fabric Inspection</h5>
                            <p class="mb-0">Check for defects, color consistency, and weight accuracy before cutting.</p>
                        </div>
                    </div>
                    
                    <div class="qc-step fade-in-up" style="transition-delay: 0.1s;">
                        <div class="qc-number">2</div>
                        <div class="qc-content">
                            <h5>Pattern Accuracy</h5>
                            <p class="mb-0">Verify cutting precision against technical specifications and measurements.</p>
                        </div>
                    </div>
                    
                    <div class="qc-step fade-in-up" style="transition-delay: 0.2s;">
                        <div class="qc-number">3</div>
                        <div class="qc-content">
                            <h5>Stitch Quality</h5>
                            <p class="mb-0">Inspect stitch density, tension, and consistency throughout the garment.</p>
                        </div>
                    </div>
                    
                    <div class="qc-step fade-in-up" style="transition-delay: 0.3s;">
                        <div class="qc-number">4</div>
                        <div class="qc-content">
                            <h5>Seam Strength</h5>
                            <p class="mb-0">Test seam durability and reinforcement at stress points.</p>
                        </div>
                    </div>
                    
                    <div class="qc-step fade-in-up" style="transition-delay: 0.4s;">
                        <div class="qc-number">5</div>
                        <div class="qc-content">
                            <h5>Wash Effect</h5>
                            <p class="mb-0">Verify consistency of washing effects and color fading patterns.</p>
                        </div>
                    </div>
                    
                    <div class="qc-step fade-in-up" style="transition-delay: 0.5s;">
                        <div class="qc-number">6</div>
                        <div class="qc-content">
                            <h5>Hardware Quality</h5>
                            <p class="mb-0">Check buttons, rivets, and zippers for functionality and durability.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="qc-step fade-in-up" style="transition-delay: 0.6s;">
                        <div class="qc-number">7</div>
                        <div class="qc-content">
                            <h5>Measurement Verification</h5>
                            <p class="mb-0">Confirm all garment measurements against size specifications.</p>
                        </div>
                    </div>
                    
                    <div class="qc-step fade-in-up" style="transition-delay: 0.7s;">
                        <div class="qc-number">8</div>
                        <div class="qc-content">
                            <h5>Color Fastness</h5>
                            <p class="mb-0">Test for color bleeding and fading under various conditions.</p>
                        </div>
                    </div>
                    
                    <div class="qc-step fade-in-up" style="transition-delay: 0.8s;">
                        <div class="qc-number">9</div>
                        <div class="qc-content">
                            <h5>Final Pressing</h5>
                            <p class="mb-0">Inspect garment shape, crease placement, and overall appearance.</p>
                        </div>
                    </div>
                    
                    <div class="qc-step fade-in-up" style="transition-delay: 0.9s;">
                        <div class="qc-number">10</div>
                        <div class="qc-content">
                            <h5>Labeling Accuracy</h5>
                            <p class="mb-0">Verify all labels, tags, and care instructions are correct and properly attached.</p>
                        </div>
                    </div>
                    
                    <div class="qc-step fade-in-up" style="transition-delay: 1.0s;">
                        <div class="qc-number">11</div>
                        <div class="qc-content">
                            <h5>Packaging Quality</h5>
                            <p class="mb-0">Check packaging materials and presentation according to client requirements.</p>
                        </div>
                    </div>
                    
                    <div class="qc-step fade-in-up" style="transition-delay: 1.1s;">
                        <div class="qc-number">12</div>
                        <div class="qc-content">
                            <h5>Final Inspection</h5>
                            <p class="mb-0">Comprehensive final review before shipment approval.</p>
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