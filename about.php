<?php
session_start();
$page_title = "About FUS | Premium Denim Manufacturer";
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
        
        /* Mission & Vision Cards - Glassmorphism comme index.php */
        .mission-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 24px;
            padding: 3rem;
            height: 100%;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .mission-card::before {
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
        
        .mission-card:hover::before {
            opacity: 1;
        }
        
        .mission-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        .mission-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            transition: transform 0.3s;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }
        
        .mission-card:hover .mission-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .mission-icon i {
            font-size: 2rem;
            color: white;
        }

        .mission-card h3 {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        .mission-card p {
            color: var(--gray-600);
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }

        .mission-card ul {
            list-style: none;
            padding: 0;
        }

        .mission-card ul li {
            margin-bottom: 0.75rem;
            color: var(--gray-700);
        }

        .mission-card ul li i {
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-right: 0.75rem;
        }
        
        /* Timeline modernisée */
        .timeline {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--accent-indigo), var(--accent-purple), var(--accent-cyan));
            transform: translateX(-50%);
        }
        
        .timeline-item {
            margin-bottom: 3rem;
            position: relative;
            width: 50%;
            padding-right: 4rem;
        }
        
        .timeline-item:nth-child(even) {
            margin-left: 50%;
            padding-left: 4rem;
            padding-right: 0;
        }
        
        .timeline-date {
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple));
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 12px;
            display: inline-block;
            margin-bottom: 1rem;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }
        
        .timeline-content {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }
        
        .timeline-content:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        .timeline-content h4 {
            font-size: 1.4rem;
            margin-bottom: 0.75rem;
            color: var(--primary-black);
        }
        
        .timeline-content p {
            color: var(--gray-600);
            margin: 0;
        }
        
        /* Team Cards - style moderne */
        .team-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 24px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            position: relative;
        }
        
        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        .team-img {
            height: 300px;
            object-fit: cover;
            width: 100%;
            transition: transform 0.6s;
        }
        
        .team-card:hover .team-img {
            transform: scale(1.05);
        }
        
        .team-info {
            padding: 2rem;
            position: relative;
        }
        
        .team-position {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(168, 85, 247, 0.1));
            color: var(--accent-indigo);
            padding: 0.4rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-block;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .team-card h4 {
            font-size: 1.4rem;
            margin-bottom: 0.75rem;
            color: var(--primary-black);
        }
        
        .team-card p {
            color: var(--gray-600);
            font-size: 0.95rem;
            line-height: 1.6;
            margin: 0;
        }
        
        /* Image hover effect */
        .image-hover {
            position: relative;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.2);
        }
        
        .image-hover img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.6s;
        }
        
        .image-hover:hover img {
            transform: scale(1.05);
        }
        
        .image-hover::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(168, 85, 247, 0.2));
            z-index: 1;
            mix-blend-mode: overlay;
            opacity: 0;
            transition: opacity 0.4s;
        }
        
        .image-hover:hover::before {
            opacity: 1;
        }
        
        .floating-badge {
            position: absolute;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 1.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            animation: float 6s ease-in-out infinite;
            z-index: 10;
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

            .timeline::before {
                left: 30px;
            }
            
            .timeline-item {
                width: 100%;
                padding-left: 4rem;
                padding-right: 0;
            }
            
            .timeline-item:nth-child(even) {
                margin-left: 0;
                padding-left: 4rem;
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

            .mission-card,
            .timeline-content,
            .team-card {
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
                        <a class="nav-link active" href="about.php">About</a>
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
                        About <span class="highlight gradient-text">FUS Denim</span>
                    </h1>
                    <p class="page-subtitle fade-in-up">
                        For over 15 years, Fashion Unique Solutions has been at the forefront of premium denim manufacturing, 
                        combining Tunisian craftsmanship with innovative technology to serve international fashion brands.
                    </p>
                    <div class="d-flex flex-wrap gap-3 fade-in-up">
                        <a href="#timeline" class="btn btn-modern">
                            <i class="fas fa-history me-2"></i>Our Journey
                        </a>
                        <a href="#team" class="btn btn-outline-modern">
                            <i class="fas fa-users me-2"></i>Meet Our Team
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Story -->
    <section class="section-padding">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <span class="section-badge fade-in-up">Our Legacy</span>
                    <h2 class="section-title fade-in-up">Our Story</h2>
                    <p class="mb-4 fade-in-up">
                        Founded in 2008 in Tunis, FUS began as a small workshop specializing in artisanal denim finishing. 
                        What started with just five skilled artisans has grown into a state-of-the-art manufacturing facility 
                        serving over 200 international clients across Europe.
                    </p>
                    <p class="mb-4 fade-in-up">
                        Our journey has been defined by a relentless pursuit of quality, innovation, and sustainable practices. 
                        We've invested heavily in modern technology while preserving the traditional craftsmanship that makes 
                        Tunisian denim unique.
                    </p>
                    <p class="fade-in-up">
                        Today, FUS stands as a bridge between European fashion trends and North African manufacturing excellence, 
                        delivering premium denim products that meet the highest international standards.
                    </p>
                </div>
                <div class="col-lg-6">
                    <div class="position-relative image-hover fade-in-up">
                        <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                             alt="FUS Factory" class="rounded-3">
                        <div class="floating-badge" style="bottom: 30px; left: 30px;">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-calendar-alt gradient-text" style="font-size: 2rem;"></i>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; color: var(--gray-500); font-weight: 600;">Since 2008</div>
                                    <div style="font-weight: 700; color: var(--primary-black);">15+ Years Excellence</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="section-padding" style="background: var(--gray-50);">
        <div class="container">
            <div class="section-header text-center">
                <span class="section-badge fade-in-up">Our Philosophy</span>
                <h2 class="section-title fade-in-up">Mission & Vision</h2>
                <p class="section-subtitle mx-auto fade-in-up">
                    Guiding principles that shape our approach to denim manufacturing and client partnerships.
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="mission-card fade-in-up">
                        <div class="mission-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3 class="mb-3">Our Mission</h3>
                        <p class="text-muted">
                            To deliver exceptional denim products through a perfect balance of traditional craftsmanship 
                            and innovative technology, while maintaining the highest standards of quality, sustainability, 
                            and ethical manufacturing.
                        </p>
                        <ul class="list-unstyled mt-3">
                            <li class="mb-2"><i class="fas fa-check me-2"></i>Premium quality at every stage</li>
                            <li class="mb-2"><i class="fas fa-check me-2"></i>Sustainable production practices</li>
                            <li><i class="fas fa-check me-2"></i>Client-centric approach</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="mission-card fade-in-up" style="transition-delay: 0.1s;">
                        <div class="mission-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3 class="mb-3">Our Vision</h3>
                        <p class="text-muted">
                            To become the preferred denim manufacturing partner for luxury and premium fashion brands worldwide, 
                            recognized for our innovation, reliability, and commitment to excellence in every aspect of production.
                        </p>
                        <ul class="list-unstyled mt-3">
                            <li class="mb-2"><i class="fas fa-check me-2"></i>Global recognition for quality</li>
                            <li class="mb-2"><i class="fas fa-check me-2"></i>Innovation in sustainable denim</li>
                            <li><i class="fas fa-check me-2"></i>Partnership with top fashion houses</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Journey Timeline -->
    <section id="timeline" class="section-padding">
        <div class="container">
            <div class="section-header text-center">
                <span class="section-badge fade-in-up">Milestones</span>
                <h2 class="section-title fade-in-up">Our Journey</h2>
                <p class="section-subtitle mx-auto fade-in-up">
                    Key moments that defined our growth and commitment to excellence.
                </p>
            </div>
            
            <div class="timeline">
                <div class="timeline-item fade-in-up">
                    <div class="timeline-date">2008</div>
                    <div class="timeline-content">
                        <h4>Foundation</h4>
                        <p>FUS established as a small denim workshop in Tunis with 5 artisans specializing in traditional washing techniques.</p>
                    </div>
                </div>
                
                <div class="timeline-item fade-in-up" style="transition-delay: 0.1s;">
                    <div class="timeline-date">2012</div>
                    <div class="timeline-content">
                        <h4>European Expansion</h4>
                        <p>First major contracts with French and German brands. Investment in modern production equipment.</p>
                    </div>
                </div>
                
                <div class="timeline-item fade-in-up" style="transition-delay: 0.2s;">
                    <div class="timeline-date">2015</div>
                    <div class="timeline-content">
                        <h4>Certification Milestone</h4>
                        <p>Achieved OEKO-TEX and ISO 9001 certifications, marking our commitment to quality and safety standards.</p>
                    </div>
                </div>
                
                <div class="timeline-item fade-in-up" style="transition-delay: 0.3s;">
                    <div class="timeline-date">2018</div>
                    <div class="timeline-content">
                        <h4>Sustainability Initiative</h4>
                        <p>Launch of our eco-friendly production line and water recycling system. First GOTS certification.</p>
                    </div>
                </div>
                
                <div class="timeline-item fade-in-up" style="transition-delay: 0.4s;">
                    <div class="timeline-date">2022</div>
                    <div class="timeline-content">
                        <h4>Digital Transformation</h4>
                        <p>Investment in Industry 4.0 technology and initiation of B2B portal development for global clients.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Leadership Team -->
    <section id="team" class="section-padding" style="background: var(--gray-50);">
        <div class="container">
            <div class="section-header text-center">
                <span class="section-badge fade-in-up">Leadership</span>
                <h2 class="section-title fade-in-up">Our Leadership Team</h2>
                <p class="section-subtitle mx-auto fade-in-up">
                    Experienced professionals combining decades of textile industry expertise with innovation.
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="team-card fade-in-up">
                        <img src="" 
                             alt="CEO" class="team-img">
                        <div class="team-info">
                            <span class="team-position">CEO & Founder</span>
                            <h4 class="mb-2">Nader Bahraoui</h4>
                            <p>25+ years in textile manufacturing. Former production director at major European denim brand.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="team-card fade-in-up" style="transition-delay: 0.1s;">
                        <img src="" 
                             alt="Head of Production" class="team-img">
                        <div class="team-info">
                            <span class="team-position">Partner</span>
                            <h4 class="mb-2">slim tounsi</h4>
                            <p>Expert in denim washing techniques and quality control. 18 years of experience in premium denim.</p>
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