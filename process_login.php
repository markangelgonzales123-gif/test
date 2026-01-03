<?php
session_start();

// Database connection parameters
$host = "localhost";
$username = "root";
$password = "";
$database = "epms_db"; // You may need to change this to your database name

// Create database connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process login form data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];
    $remember = isset($_POST["remember"]) ? true : false;
    
    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION["error"] = "Email and password are required";
        header("Location: index.php");
        exit();
    }
    
    // Query to find user
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if password is correct
        // First try direct comparison (for non-hashed passwords)
        $password_correct = ($password === $user["password"]);
        
        // If direct comparison fails, try password_verify (for hashed passwords)
        if (!$password_correct && strlen($user["password"]) > 20) {
            $password_correct = password_verify($password, $user["password"]);
        }
        
        if ($password_correct) {
            // Set session variables
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["user_email"] = $user["email"];
            $_SESSION["user_role"] = $user["role"];
            $_SESSION["user_department_id"] = $user["department_id"];
            
            // Set remember me cookie if checked
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + (30 * 24 * 60 * 60); // 30 days
                
                // Store token in database
                $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $stmt->bind_param("si", $token, $user["id"]);
                $stmt->execute();
                
                // Set cookie
                setcookie("remember_token", $token, $expires, "/", "", true, true);
            }
            
            // Redirect based on user role
            switch ($user["role"]) {
                case 'admin':
                    header("Location: admin/dashboard.php");
                    break;
                case 'president':
                    header("Location: dashboard.php");
                    break;
                case 'department_head':
                    header("Location: dpcr.php");
                    break;
                case 'regular_employee':
                    header("Location: ipcr.php");
                    break;
                default:
                    header("Location: records.php");
                    break;
            }
            exit();
        } else {
            $_SESSION["error"] = "Invalid email or password";
        }
    } else {
        $_SESSION["error"] = "Invalid email or password";
    }
    
    header("Location: index.php");
    exit();
} else {
    // If someone tries to access this file directly without form submission
    header("Location: index.php");
    exit();
}

$conn->close();
?> 