<?php
// Start session for confirmation messages
session_start();

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

// First, backup existing users with specific roles
$sql = "SELECT * FROM users WHERE role IN ('admin', 'president', 'regular_employee')";
$result = $conn->query($sql);
$preserved_users = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $preserved_users[] = $row;
    }
}

// Drop all existing tables
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Get all tables
$tables_result = $conn->query("SHOW TABLES");
while ($table = $tables_result->fetch_array()) {
    $table_name = $table[0];
    $conn->query("DROP TABLE IF EXISTS $table_name");
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// Recreate database structure from SQL file
$sql = file_get_contents('epms_db.sql');
$conn->multi_query($sql);

// Wait for all queries to execute
do {
    // Store the result of the query (if any)
    if ($result = $conn->store_result()) {
        $result->free();
    }
} while ($conn->more_results() && $conn->next_result());

// Insert Academic Affairs departments and users
// Create Academic Affairs department
$conn->query("INSERT INTO departments (name, description) VALUES ('Academic Affairs', 'Oversees academic programs, faculty, and educational policies')");
$academic_affairs_id = $conn->insert_id;

// VP for Academic Affairs
$conn->query("INSERT INTO users (name, email, password, role, department_id) 
              VALUES ('DR. CAROLINA A. SARMIENTO', 'carolinasarmiento@cca.edu.ph', 
              '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'president', $academic_affairs_id)");
$vp_id = $conn->insert_id;

// Set VP as department head
$conn->query("UPDATE departments SET head_id = $vp_id WHERE id = $academic_affairs_id");

// Insert IBM department
$conn->query("INSERT INTO departments (name, description) VALUES ('Institute of Business and Management', 'Department for business programs and management education')");
$ibm_id = $conn->insert_id;

// Insert ICSLIS department
$conn->query("INSERT INTO departments (name, description) VALUES ('Institute of Computing Studies and Library Information Science', 'Department for computing and library science programs')");
$icslis_id = $conn->insert_id;

// Insert Education department
$conn->query("INSERT INTO departments (name, description) VALUES ('Institute of Education, Arts and Sciences', 'Department for education, arts and sciences programs')");
$education_id = $conn->insert_id;

// Insert Student Affairs department
$conn->query("INSERT INTO departments (name, description) VALUES ('Student Affairs and Services Office', 'Provides support services for students')");
$student_affairs_id = $conn->insert_id;

// Insert ARO department
$conn->query("INSERT INTO departments (name, description) VALUES ('Admissions and Registrar\'s Office', 'Manages student admissions and registration')");
$aro_id = $conn->insert_id;

// Insert Library department
$conn->query("INSERT INTO departments (name, description) VALUES ('College Library', 'Provides library resources and services')");
$library_id = $conn->insert_id;

// Insert CGFO department
$conn->query("INSERT INTO departments (name, description) VALUES ('College Guidance and Formation Office', 'Provides guidance and counseling services')");
$cgfo_id = $conn->insert_id;

// Insert all users from the Academic Affairs structure
// IBM Dean
$conn->query("INSERT INTO users (name, email, password, role, department_id) 
              VALUES ('MS. AMOR L. BARBA', 'amorbarba@cca.edu.ph', 
              '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'department_head', $ibm_id)");
$ibm_dean_id = $conn->insert_id;
$conn->query("UPDATE departments SET head_id = $ibm_dean_id WHERE id = $ibm_id");

// ICSLIS Dean
$conn->query("INSERT INTO users (name, email, password, role, department_id) 
              VALUES ('MS. MAIKA V. GARBES', 'maikagarbes@cca.edu.ph', 
              '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'department_head', $icslis_id)");
$icslis_dean_id = $conn->insert_id;
$conn->query("UPDATE departments SET head_id = $icslis_dean_id WHERE id = $icslis_id");

// Education Dean
$conn->query("INSERT INTO users (name, email, password, role, department_id) 
              VALUES ('DR. LEVITA DE GUZMAN', 'levitaguzman@cca.edu.ph', 
              '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'department_head', $education_id)");
$education_dean_id = $conn->insert_id;
$conn->query("UPDATE departments SET head_id = $education_dean_id WHERE id = $education_id");

// Student Affairs Dean
$conn->query("INSERT INTO users (name, email, password, role, department_id) 
              VALUES ('MS. MARIA TERESSA G. LAPUZ', 'mariateressalapuz@cca.edu.ph', 
              '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'department_head', $student_affairs_id)");
$student_affairs_dean_id = $conn->insert_id;
$conn->query("UPDATE departments SET head_id = $student_affairs_dean_id WHERE id = $student_affairs_id");

// ARO Head
$conn->query("INSERT INTO users (name, email, password, role, department_id) 
              VALUES ('MR. LESSANDRO YUCON', 'lessandroyucon@cca.edu.ph', 
              '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'department_head', $aro_id)");
$aro_head_id = $conn->insert_id;
$conn->query("UPDATE departments SET head_id = $aro_head_id WHERE id = $aro_id");

// Library Head
$conn->query("INSERT INTO users (name, email, password, role, department_id) 
              VALUES ('MS. JASMINE ANGELICA MARIE CANLAS', 'jasmineangelicacanlas@cca.edu.ph', 
              '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'department_head', $library_id)");
$library_head_id = $conn->insert_id;
$conn->query("UPDATE departments SET head_id = $library_head_id WHERE id = $library_id");

// CGFO Head
$conn->query("INSERT INTO users (name, email, password, role, department_id) 
              VALUES ('DR. RHENAN ESTACIO', 'rhenanestacio@cca.edu.ph', 
              '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'department_head', $cgfo_id)");
$cgfo_head_id = $conn->insert_id;
$conn->query("UPDATE departments SET head_id = $cgfo_head_id WHERE id = $cgfo_id");

// Update all passwords to the specified one
$password = "DeanAko123";
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$conn->query("UPDATE users SET password = '$hashed_password' WHERE role = 'department_head' OR role = 'president'");

// Re-insert preserved users
foreach ($preserved_users as $user) {
    // Skip if email already exists in the database
    $check_sql = "SELECT id FROM users WHERE email = '{$user['email']}'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows == 0) {
        $sql = "INSERT INTO users (name, email, password, role, department_id, avatar, remember_token, created_at, updated_at) 
                VALUES ('{$user['name']}', '{$user['email']}', '{$user['password']}', '{$user['role']}', 
                {$user['department_id']}, '{$user['avatar']}', '{$user['remember_token']}', 
                '{$user['created_at']}', '{$user['updated_at']}')";
        $conn->query($sql);
    }
}

// Set success message
$_SESSION['success'] = "Database has been reset successfully. Academic Affairs structure has been created with all users having password: DeanAko123";

// Close the database connection
$conn->close();

// Redirect to index page
header("Location: index.php");
exit;
?> 