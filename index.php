<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FUS | Premium Denim Manufacturing for International Brands</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
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

        /* Gradient Text Utility */
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
        
        /* Navigation */
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
        
        /* Modern Buttons */
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
        
        /* Hero Section */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #0D0D0D 0%, #1A1A1A 50%, #0D0D0D 100%);
            position: relative;
            overflow: hidden;
            padding: 8rem 0 4rem;
        }

        .hero-section::before {
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

        .hero-grid {
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
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-title {
            font-size: 4.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.1;
            color: white;
            letter-spacing: -2px;
        }

        .hero-title .highlight {
            position: relative;
            display: inline-block;
        }

        .hero-title .highlight::after {
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
        
        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--gray-300);
            margin-bottom: 2.5rem;
            max-width: 600px;
            line-height: 1.7;
            font-weight: 400;
        }

        .hero-image-wrapper {
            position: relative;
            z-index: 2;
        }

        .hero-image-card {
            position: relative;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            transform: perspective(1000px) rotateY(-5deg);
            transition: transform 0.6s;
        }

        .hero-image-card:hover {
            transform: perspective(1000px) rotateY(0deg);
        }

        .hero-image-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(168, 85, 247, 0.2));
            z-index: 1;
            mix-blend-mode: overlay;
        }

        .hero-image-card img {
            width: 100%;
            height: auto;
            display: block;
        }

        .floating-badge {
            position: absolute;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 1.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            animation: float 6s ease-in-out infinite;
        }

        .floating-badge-1 {
            top: 10%;
            right: -10%;
            animation-delay: 0s;
        }

        .floating-badge-2 {
            bottom: 15%;
            left: -5%;
            animation-delay: 2s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        /* Feature Cards - Glassmorphism */
        .feature-card {
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

        .feature-card::before {
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
        
        .feature-card:hover::before {
            opacity: 1;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.3);
        }
        
        .feature-icon {
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
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .feature-icon i {
            font-size: 1.8rem;
            color: white;
        }

        .feature-card h4 {
            font-size: 1.35rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .feature-card p {
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
        
        /* Collection Cards - Modern Bento Style */
        .collection-card {
            position: relative;
            border-radius: 24px;
            overflow: hidden;
            height: 400px;
            transition: all 0.4s;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .collection-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0.3) 50%, transparent 100%);
            z-index: 1;
            transition: opacity 0.4s;
        }
        
        .collection-card:hover::before {
            opacity: 0.9;
        }

        .collection-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }
        
        .collection-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s;
        }
        
        .collection-card:hover .collection-img {
            transform: scale(1.1);
        }
        
        .collection-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 2rem;
            color: white;
            z-index: 2;
            transform: translateY(10px);
            transition: transform 0.4s;
        }

        .collection-card:hover .collection-content {
            transform: translateY(0);
        }

        .collection-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .collection-card h4 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            color: white;
        }

        .collection-card p {
            color: var(--gray-200);
            margin: 0;
        }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple));
            border-radius: 32px;
            padding: 5rem 3rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(99, 102, 241, 0.3);
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .cta-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .cta-section h3 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 800;
        }

        .cta-section p {
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
        
        /* Certifications */
        .cert-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: all 0.4s;
            border: 2px solid var(--gray-100);
            height: 100%;
        }

        .cert-card:hover {
            transform: translateY(-8px);
            border-color: var(--accent-indigo);
            box-shadow: 0 20px 50px rgba(99, 102, 241, 0.15);
        }

        .cert-logo {
            height: 80px;
            object-fit: contain;
            margin-bottom: 1rem;
            filter: grayscale(100%);
            transition: filter 0.4s;
        }

        .cert-card:hover .cert-logo {
            filter: grayscale(0);
        }
        
        /* Footer */
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

        /* Section Styling */
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
        
        /* Responsive */
        @media (max-width: 991px) {
            .hero-title {
                font-size: 3rem;
            }

            .section-title {
                font-size: 2.25rem;
            }

            .hero-section {
                padding: 7rem 0 3rem;
            }

            .navbar-brand {
                font-size: 1.5rem;
            }

            .floating-badge-1,
            .floating-badge-2 {
                display: none;
            }
        }

        @media (max-width: 767px) {
            .hero-title {
                font-size: 2.25rem;
            }

            .section-title {
                font-size: 1.85rem;
            }

            .section-padding {
                padding: 4rem 0;
            }

            .cta-section {
                padding: 3rem 1.5rem;
            }

            .stat-number {
                font-size: 2.5rem;
            }

            .collection-card {
                height: 300px;
            }
        }

        /* Animations */
        .fade-in-up {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .fade-in-up.visible {
            opacity: 1;
            transform: translateY(0);
        }

        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">FUS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-grid"></div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1 class="hero-title fade-in-up">
                        Crafting <span class="highlight gradient-text">Premium Denim</span> for Global Fashion
                    </h1>
                    <p class="hero-subtitle fade-in-up">
                        In the heart of Tunisia, our factory is dedicated to crafting exceptional denim. Here, every step is orchestrated to achieve a level of finish and reliability that meets the absolute demands of our customers.                    </p>
                    <div class="d-flex flex-wrap gap-3 fade-in-up">
                        <a href="contact.php?action=request_access" class="btn btn-modern">
                            <i class="fas fa-rocket me-2"></i>Request B2B Access
                        </a>
                        <a href="collections.php" class="btn btn-outline-modern">
                            <i class="fas fa-eye me-2"></i>View Collections
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 mt-5 mt-lg-0">
                    <div class="hero-image-wrapper">
                        <div class="hero-image-card">
                            <img src="https://www.greenybirddress.com/wp-content/uploads/2022/08/maude-frederique-lavoie-EDSTj4kCUcw-unsplash.jpg" 
                                 alt="Premium Denim Fabric">
                        </div>
                        <div class="floating-badge floating-badge-1">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-certificate gradient-text" style="font-size: 2rem;"></i>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; color: var(--gray-500); font-weight: 600;">ISO 9001</div>
                                    <div style="font-weight: 700; color: var(--primary-black);">Certified</div>
                                </div>
                            </div>
                        </div>
                        <div class="floating-badge floating-badge-2">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-leaf gradient-text-alt" style="font-size: 2rem;"></i>
                                </div>
                                <div>
                                    <div style="font-size: 0.75rem; color: var(--gray-500); font-weight: 600;">100% Eco</div>
                                    <div style="font-weight: 700; color: var(--primary-black);">Sustainable</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section-padding" style="background: var(--gray-50);">
        <div class="container">
            <div class="section-header text-center">
                <span class="section-badge fade-in-up">Why Choose Us</span>
                <h2 class="section-title fade-in-up">Excellence in Every Thread</h2>
                <p class="section-subtitle mx-auto fade-in-up">
                    15+ years of denim mastery, serving the world's most discerning fashion brands with unparalleled quality and innovation.
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in-up">
                        <div class="feature-icon">
                            <i class="fas fa-gem"></i>
                        </div>
                        <h4>Premium Quality</h4>
                        <p>
                            12-point quality control on every piece. Premium raw materials meet rigorous testing protocols for perfection you can trust.
                        </p>
                        <div>
                            <span class="badge-modern">ISO 9001</span>
                            <span class="badge-modern">Six Sigma</span>
                            <span class="badge-modern">OEKO-TEX</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in-up" style="transition-delay: 0.1s;">
                        <div class="feature-icon">
                            <i class="fas fa-globe-europe"></i>
                        </div>
                        <h4>European Expertise</h4>
                        <p>
                            Deep understanding of European markets, trends, and regulations. Continuous trend analysis keeps you ahead of the curve.
                        </p>
                        <div>
                            <span class="badge-modern">REACH</span>
                            <span class="badge-modern">CE Mark</span>
                            <span class="badge-modern">EU Standards</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card fade-in-up" style="transition-delay: 0.2s;">
                        <div class="feature-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h4>Sustainable Innovation</h4>
                        <p>
                            Eco-friendly production with advanced water recycling. Sustainable sourcing meets environmental responsibility at every step.
                        </p>
                        <div>
                            <span class="badge-modern">GOTS</span>
                            <span class="badge-modern">Organic</span>
                            <span class="badge-modern">Carbon Neutral</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card fade-in-up">
                        <div class="stat-number" data-count="15">0</div>
                        <div class="stat-label">Years Excellence</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card fade-in-up" style="transition-delay: 0.1s;">
                        <div class="stat-number" data-count="200">0</div>
                        <div class="stat-label">Active Clients</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card fade-in-up" style="transition-delay: 0.2s;">
                        <div class="stat-number" data-count="12">0</div>
                        <div class="stat-label">EU Markets</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card fade-in-up" style="transition-delay: 0.3s;">
                        <div class="stat-number" data-count="98">0</div>
                        <div class="stat-label">% Satisfaction</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Collections -->
    <section class="section-padding">
        <div class="container">
            <div class="section-header">
                <div class="row align-items-end">
                    <div class="col-lg-8">
                        <span class="section-badge fade-in-up">Our Collections</span>
                        <h2 class="section-title fade-in-up">Signature Denim Lines</h2>
                        <p class="section-subtitle fade-in-up">
                            Curated collections where craftsmanship meets innovation. Each piece tells a unique story of quality and design excellence.
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end fade-in-up">
                        <a href="login.php" class="btn btn-outline-modern">
                            <i class="fas fa-lock me-2"></i>Access Full Catalog
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="collection-card fade-in-up">
                        <img src="https://images.unsplash.com/photo-1582418702059-97ebafb35d09?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                             alt="Heritage Collection" class="collection-img">
                        <div class="collection-content">
                            <span class="collection-badge">
                                <i class="fas fa-star me-2"></i>Flagship
                            </span>
                            <h4>Heritage Collection</h4>
                            <p>Timeless designs with artisanal finishes</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="collection-card fade-in-up" style="transition-delay: 0.1s;">
                        <img src="https://capecodelegues.goodplanet.org/media/images/AdobeStock_282280661-min.max-900x600.jpg" 
                             alt="Modern Collection" class="collection-img">
                        <div class="collection-content">
                            <span class="collection-badge">
                                <i class="fas fa-bolt me-2"></i>Innovation
                            </span>
                            <h4>Modern Collection</h4>
                            <p>Contemporary cuts & innovative treatments</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="collection-card fade-in-up" style="transition-delay: 0.2s;">
                        <img src="https://estherbancel-lab.com/wp-content/uploads/2024/05/Quelle-est-lorigine-du-jean-denim--scaled.jpeg" 
                             alt="Sustainable Collection" class="collection-img">
                        <div class="collection-content">
                            <span class="collection-badge">
                                <i class="fas fa-seedling me-2"></i>Eco-Friendly
                            </span>
                            <h4>Sustainable Collection</h4>
                            <p>Organic materials & eco-conscious processes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

   

    <!-- CTA -->
    <section class="section-padding">
        <div class="container">
            <div class="cta-section fade-in-up">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h3>Ready to Transform Your Denim Collection?</h3>
                        <p class="mb-0">
                            Join our exclusive network of international brands. Access premium collections, 
                            technical specs, and streamlined ordering through our secure B2B portal.
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                        <a href="contact.php?action=request_access" class="btn btn-white btn-lg">
                            <i class="fas fa-arrow-right me-2"></i>Start Partnership
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
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
                            <span>Monestir, Tunisia</span>
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
                
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Navbar scroll
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });

        // Intersection Observer for animations
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

        // Counter animation
        const animateCounter = (counter) => {
            const target = +counter.dataset.count;
            const speed = 300;
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
        };

        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counter = entry.target.querySelector('.stat-number');
                    animateCounter(counter);
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.stat-card').forEach(card => statsObserver.observe(card));

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>