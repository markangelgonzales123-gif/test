<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error_message'] = "Error uploading file. Please try again.";
    header("Location: profile.php");
    exit;
}

// Create uploads directory if it doesn't exist
$upload_dir = "uploads/avatars/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Check file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$file_info = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($file_info, $_FILES['avatar']['tmp_name']);
finfo_close($file_info);

if (!in_array($mime_type, $allowed_types)) {
    $_SESSION['error_message'] = "Only JPEG, PNG, and GIF files are allowed.";
    header("Location: profile.php");
    exit;
}

// Set max file size (2MB)
$max_size = 2 * 1024 * 1024; // 2MB in bytes
if ($_FILES['avatar']['size'] > $max_size) {
    $_SESSION['error_message'] = "File size must be less than 2MB.";
    header("Location: profile.php");
    exit;
}

// Generate unique filename
$file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
$filename = $_SESSION['user_id'] . '_' . uniqid() . '.' . $file_extension;
$target_file = $upload_dir . $filename;

// Move uploaded file to destination
if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
    // Database connection
    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "epms_db";
    
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Update user's avatar in database
    $user_id = $_SESSION['user_id'];
    
    // Check if user already has an avatar and delete the old one
    $sql = "SELECT avatar FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (!empty($user['avatar']) && file_exists($user['avatar']) && $user['avatar'] !== $target_file) {
            unlink($user['avatar']);
        }
    }
    
    // Update avatar path in database
    $sql = "UPDATE users SET avatar = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $target_file, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profile picture updated successfully.";
    } else {
        $_SESSION['error_message'] = "Error updating profile picture in database.";
    }
    
    $conn->close();
} else {
    $_SESSION['error_message'] = "Error moving uploaded file.";
}

// Redirect back to profile page
header("Location: profile.php");
exit;
?> 