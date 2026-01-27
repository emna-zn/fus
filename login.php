<?php
session_start();
require_once 'connexion.php';
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit();
}
$database = new Database();
$conn = $database->getConnection();
$error = '';
$success = '';
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_SESSION['role'])) {
        switch ($_SESSION['role']) {
            case 'admin':
                header('Location: dashboard.php');
                break;
            case 'client':
                header('Location: dashboard_client.php');
                break;
            default:
                session_destroy();
                break;
        }
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs";
    } else {
        $query = "SELECT id, email, password, role, company_name, contact_person, country, is_active 
                  FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (!$user['is_active']) {
                    $error = "Votre compte est désactivé. Contactez l'administrateur.";
                } else {
                    if ($password === $user['password']) {
                        $_SESSION['logged_in'] = true;
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['company_name'] = $user['company_name'];
                        $_SESSION['contact_person'] = $user['contact_person'];
                        $_SESSION['country'] = $user['country'];
                        if ($user['role'] === 'admin') {
                            header('Location: dashboard.php');
                        } else {
                            header('Location: dashboard_client.php');
                        }
                        exit();
                    } else {
                        $error = "Mot de passe incorrect";
                    }
                }
            } else {
                $error = "Aucun compte trouvé avec cet email";
            }
            
            $stmt->close();
        } else {
            $error = "Erreur de connexion à la base de données";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FUS | Client Login</title>
    
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
            background: linear-gradient(135deg, var(--primary-black) 0%, var(--primary-dark) 50%, var(--primary-black) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
        }

        body::before {
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

        /* Modern Buttons */
        .btn-modern {
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-purple));
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3);
            position: relative;
            overflow: hidden;
            font-size: 1rem;
            width: 100%;
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

        /* Login Container */
        .login-container {
            position: relative;
            z-index: 2;
            max-width: 440px;
            width: 100%;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 35px 80px rgba(99, 102, 241, 0.25);
            border-color: rgba(99, 102, 241, 0.3);
        }

        /* Header */
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 2.2rem;
            color: var(--primary-black);
            letter-spacing: -1px;
            margin-bottom: 1rem;
            position: relative;
            display: inline-block;
        }

        .logo::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-indigo), var(--accent-purple));
            border-radius: 2px;
        }

        .login-header h2 {
            font-family: 'Syne', sans-serif;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary-black);
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: var(--gray-600);
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* Form Styles */
        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }

        .form-label i {
            margin-right: 0.5rem;
            color: var(--accent-indigo);
        }

        .form-control {
            padding: 0.875rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: var(--accent-indigo);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
            background: white;
        }

        .form-control::placeholder {
            color: var(--gray-400);
        }

        /* Alert Styles */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #DC2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: #16A34A;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        /* Link Styles */
        .login-link {
            color: var(--accent-indigo);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            position: relative;
            padding-bottom: 2px;
        }

        .login-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--accent-indigo), var(--accent-purple));
            transition: width 0.3s;
        }

        .login-link:hover {
            color: var(--accent-purple);
        }

        .login-link:hover::before {
            width: 100%;
        }

        /* Debug Section */
        .debug-section {
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(99, 102, 241, 0.05);
            border: 1px solid rgba(99, 102, 241, 0.1);
            border-radius: 16px;
        }

        .debug-section p {
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            color: var(--gray-600);
        }

        .debug-section .title {
            font-weight: 600;
            color: var(--accent-indigo);
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }

        /* Footer */
        .footer-text {
            text-align: center;
            margin-top: 2rem;
            color: var(--gray-400);
            font-size: 0.85rem;
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .login-container {
                padding: 2rem;
                margin: 1rem;
            }
            
            .logo {
                font-size: 1.8rem;
            }
            
            .login-header h2 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 400px) {
            .login-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="hero-grid"></div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-4 col-lg-5 col-md-6 col-sm-8">
                <div class="login-container fade-in">
                    <div class="login-header">
                        <div class="logo">FUS</div>
                        <h2>Client Portal</h2>
                        <p>Secure B2B access for partners</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i>Email Address
                            </label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   placeholder="partner@company.com" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i>Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required 
                                   placeholder="Enter your password">
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button type="submit" name="login" class="btn-modern">
                                <i class="fas fa-arrow-right-to-bracket me-2"></i>Login to Portal
                            </button>
                        </div>
                        
                        <div class="text-center mb-4">
                            <a href="index.php" class="login-link me-3">
                                <i class="fas fa-home me-1"></i>Back to Home
                            </a>
                            <span class="text-muted">|</span>
                            <a href="contact.php?action=request_access" class="login-link ms-3">
                                <i class="fas fa-user-plus me-1"></i>Request Access
                            </a>
                        </div>
                    </form>
                    
                    <div class="footer-text">
                        <p>&copy; 2026 FUS Denim. Premium Manufacturing Solutions.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Add focus effects to form inputs
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });

        // Add password visibility toggle
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.createElement('span');
        passwordToggle.innerHTML = '<i class="fas fa-eye"></i>';
        passwordToggle.style.cssText = `
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray-500);
            transition: color 0.3s;
            z-index: 10;
        `;
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        if (passwordInput) {
            passwordInput.parentElement.style.position = 'relative';
            passwordInput.parentElement.appendChild(passwordToggle);
        }

        // Add animation on load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.login-container').classList.add('fade-in');
        });
    </script>
</body>
</html>