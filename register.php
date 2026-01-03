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

// Get departments for dropdown
$sql = "SELECT * FROM departments ORDER BY name";
$departments = $conn->query($sql);

// Process registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $department_id = $_POST["department_id"];
    
    // Validate input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Email already exists";
        }
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($department_id)) {
        $errors[] = "Department is required";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, department_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $email, $hashed_password, $department_id);
        
        if ($stmt->execute()) {
            $_SESSION["success"] = "Registration successful! You can now login.";
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - City College of Angeles</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #f0f2f5;
        }
        .register-card {
            max-width: 550px;
            margin: 0 auto;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background-color: white;
        }
        .register-btn {
            background-color: #2d5d2a;
            color: white;
        }
        .register-btn:hover {
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
        <div class="register-card">
            <div class="text-center mb-4">
                <div class="flex items-center justify-center mb-2">
                    <img src="images/logo.png" alt="College Logo" class="college-logo">
                </div>
                <h2 class="text-2xl font-bold text-center text-green-800">CITY COLLEGE OF ANGELES</h2>
                <h3 class="text-xl mt-4 mb-2">Register</h3>
                <p class="text-gray-600">Create a new account</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="register.php" method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($name) ? $name : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? $email : ''; ?>" required>
                    <div class="form-text">Please use your institutional email if possible.</div>
                </div>
                
                <div class="mb-3">
                    <label for="department_id" class="form-label">Department</label>
                    <select class="form-select" id="department_id" name="department_id" required>
                        <option value="">Select Department</option>
                        <?php
                        if ($departments->num_rows > 0) {
                            while($dept = $departments->fetch_assoc()) {
                                $selected = (isset($department_id) && $department_id == $dept["id"]) ? "selected" : "";
                                echo '<option value="' . $dept["id"] . '" ' . $selected . '>' . $dept["name"] . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="form-text">Password must be at least 6 characters long.</div>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn register-btn w-100 mb-3">Register</button>
            </form>
            
            <div class="text-center mt-3">
                <p>Already have an account? <a href="index.php" class="text-blue-500">Login here</a></p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 