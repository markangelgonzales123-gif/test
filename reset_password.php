<?php
session_start();

// If user is already logged in, redirect to records page
if (isset($_SESSION['user_id'])) {
    header("Location: records.php");
    exit();
}

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "epms_db";

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$message_type = "";
$token_valid = false;
$user_email = "";

// Check if token is provided and valid
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    // Check if token exists in database
    $stmt = $conn->prepare("SELECT email FROM users WHERE remember_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $token_valid = true;
        $user = $result->fetch_assoc();
        $user_email = $user['email'];
    } else {
        $message = "Invalid or expired token. Please request a new password reset link.";
        $message_type = "danger";
    }
    var_dump("deyum");
} else {
    $message = "No reset token provided. Please request a password reset from the forgot password page.";
    $message_type = "danger";
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_valid) {
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];
    $token = $_POST["token"];
    
    if (empty($new_password)) {
        $message = "New password is required.";
        $message_type = "danger";
    } elseif (strlen($new_password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $message_type = "danger";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "danger";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update user's password and clear the token
        $stmt = $conn->prepare("UPDATE users SET password = ?, remember_token = NULL WHERE remember_token = ?");
        $stmt->bind_param("ss", $hashed_password, $token);
        
        if ($stmt->execute()) {
            $message = "Your password has been reset successfully. You can now <a href='index.php'>login</a> with your new password.";
            $message_type = "success";
            $token_valid = false; // Prevent further resets with this token
        } else {
            $message = "Error updating password. Please try again.";
            $message_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - City College of Angeles</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #f0f2f5;
        }
        .reset-card {
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
        <div class="reset-card">
            <div class="text-center mb-4">
                <div class="flex items-center justify-center mb-2">
                    <img src="images/logo.png" alt="College Logo" class="college-logo">
                </div>
                <h2 class="text-2xl font-bold text-center text-green-800">CITY COLLEGE OF ANGELES</h2>
                <h3 class="text-xl mt-4 mb-2">Reset Password</h3>
                <?php if ($token_valid): ?>
                    <p class="text-gray-600">Please enter your new password below.</p>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> mb-4">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($token_valid): ?>
                <form action="reset_password.php" method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user_email); ?>" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">Password must be at least 6 characters long.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn reset-btn w-100 mb-3">Reset Password</button>
                </form>
            <?php else: ?>
                <div class="text-center mt-3">
                    <a href="forgot_password.php" class="btn btn-outline-secondary">Back to Forgot Password</a>
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-3">
                <p>Remember your password? <a href="index.php" class="text-blue-500">Back to login</a></p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 