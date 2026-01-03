<?php
// Database connection parameters
$host = "localhost";
$username = "root";
$password = "";
$database = "epms_db";

// Create database connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Admin user details
$name = "New Admin";
$email = "admin@cca.edu.ph";
$password = "admin123"; // Non-hashed password for testing
$role = "admin";
$department_id = 1;

// Insert admin user with non-hashed password
$sql = "INSERT INTO users (name, email, password, role, department_id) 
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssi", $name, $email, $password, $role, $department_id);

if ($stmt->execute()) {
    echo "New admin user created successfully:<br>";
    echo "Email: " . $email . "<br>";
    echo "Password: " . $password . "<br>";
} else {
    echo "Error: " . $stmt->error;
}

$conn->close();
?> 