<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITY COLLEGE OF ANGELES - Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 15px;
        }
        .login-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 2rem;
        }
        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
        }
        .logo {
            width: 80px;
            height: auto;
            margin-bottom: 1rem;
        }
        .login-btn {
            background-color: #2d5d2a;
            border-color: #2d5d2a;
        }
        .login-btn:hover {
            background-color: #224221;
            border-color: #224221;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <?php
        session_start();
        
        // Display error message if any
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger mb-3">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        
        // Display success message if any
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success mb-3">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        
        // Check if user is already logged in
        if (isset($_SESSION['user_id'])) {
            if ($_SESSION['user_role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else if ($_SESSION['user_role'] === 'president') {
                header("Location: dashboard.php");
            } else if ($_SESSION['user_role'] === 'department_head') {
                header("Location: dpcr.php");
            } else if ($_SESSION['user_role'] === 'regular_employee') {
                header("Location: ipcr.php");
            } else {
                header("Location: records.php");
            }
            exit();
        }
        ?>
        
        <div class="login-card">
            <div class="logo-container">
                <img src="images/CCA.jpg" alt="City College of Angeles Logo" class="logo">
                <h3 class="text-center mb-0">CITY COLLEGE OF ANGELES</h3>
                <p class="text-muted small mb-4">Employee Performance Management System</p>
            </div>
            
            <form action="process_login.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary login-btn">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </button>
                </div>
                
                <div class="text-center mt-3">
                    <a href="forgot_password.php" class="text-decoration-none">Forgot password?</a>
                </div>
            </form>
            
            <div class="text-center mt-4">
                <p class="text-muted small">
                    © <?php echo date('Y'); ?> City College of Angeles<br>
                    All Rights Reserved
                </p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle the eye icon
                const eyeIcon = togglePassword.querySelector('i');
                eyeIcon.classList.toggle('bi-eye');
                eyeIcon.classList.toggle('bi-eye-slash');
            });
        });
    </script>
</body>
</html> 