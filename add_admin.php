<?php
require_once 'includes/db_connect.php';

// Admin user details
$name = "New Admin";
$email = "admin@cca.edu.ph";
$password = "admin123"; // Plain text password
$role = "admin";
$department_id = 1;

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert admin user with hashed password
$sql = "INSERT INTO users (name, email, password, role, department_id) 
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssi", $name, $email, $hashed_password, $role, $department_id);

if ($stmt->execute()) {
    echo "New admin user created successfully:<br>";
    echo "Email: " . $email . "<br>";
    echo "Password: " . $password . " (Hashed: " . $hashed_password . ")<br>";
} else {
    echo "Error: " . $stmt->error;
}

?>