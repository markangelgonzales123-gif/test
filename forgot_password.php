<?php
session_start();

// If user is already logged in, redirect to records page
if (isset($_SESSION['user_id'])) {
    header("Location: records.php");
    exit();
}

// Database connection
require_once 'includes/db_connect.php';

$message = "";
$message_type = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    
    if (empty($email)) {
        $message = "Email address is required.";
        $message_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $message_type = "danger";
    } else {
        // Check if email exists in the database
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $message = "No account found with that email address.";
            $message_type = "danger";
        } else {
            // Generate a reset token
            $token = bin2hex(random_bytes(32));
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database (in a real application, you would have a password_resets table)
            // For this example, we'll just update the user's remember_token field
            $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE email = ?");
            $stmt->bind_param("ss", $token, $email);
            $stmt->execute();
            
            // In a real application, you would send an email with a reset link
            // For this example, we'll just show a success message with the reset link
            $reset_link = "reset_password.php?token=" . $token;
            
            $message = "Password reset instructions have been sent to your email. <a href='$reset_link'>Click here to reset your password</a>";
            $message_type = "success";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - City College of Angeles</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #f0f2f5;
        }
        .forgot-card {
            max-width: 450px;
            margin: 0 auto;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background-color: white;
        }
        .reset-btn {
            background-color: #2d5d2a;
            color: white;
        }
        .reset-btn:hover {
            background-color: #224221;
        }
        .college-logo {
            width: 80px;
            height: auto;
        }
    </style>
</head>
<body class="py-5">
    <div class="container mt-5">
        <div class="forgot-card">
            <div class="text-center mb-4">
                <div class="flex items-center justify-center mb-2">
                    <img src="images/logo.png" alt="College Logo" class="college-logo">
                </div>
                <h2 class="text-2xl font-bold text-center text-green-800">CITY COLLEGE OF ANGELES</h2>
                <h3 class="text-xl mt-4 mb-2">Forgot Password</h3>
                <p class="text-gray-600">Enter your email address below and we'll send you a link to reset your password.</p>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> mb-4">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form action="forgot_password.php" method="POST">
                <div class="mb-4">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <button type="submit" class="btn reset-btn w-100 mb-3">Send Reset Link</button>
            </form>
            
            <div class="text-center mt-3">
                <p>Remembered your password? <a href="index.php" class="text-blue-500">Back to login</a></p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 