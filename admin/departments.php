<?php
// Set page title
$page_title = "Department Management - EPMS";

// Include header with a different path
$admin_page = true;
include_once('../includes/header.php');

// Check if user has the right role to access this page
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../access_denied.php");
    exit();
}

// Database connection
require_once '../includes/db_connect.php';

// Handle actions (add, edit, delete departments)
$message = '';
$message_type = '';

// Handle department deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $dept_id = $_GET['delete'];
    
    // First check if department has associated users
    $check_users_sql = "SELECT COUNT(*) as user_count FROM users WHERE department_id = ?";
    $stmt = $conn->prepare($check_users_sql);
    $stmt->bind_param("i", $dept_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_count = $result->fetch_assoc()['user_count'];
    
    if ($user_count > 0) {
        $message = "Cannot delete department with associated users. Please reassign users first.";
        $message_type = "danger";
    } else {
        // Delete the department
        $delete_sql = "DELETE FROM departments WHERE id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $dept_id);
        
        if ($stmt->execute()) {
            $message = "Department deleted successfully.";
            $message_type = "success";
        } else {
            $message = "Error deleting department: " . $conn->error;
            $message_type = "danger";
        }
    }
}

// Handle department addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    // Validate inputs
    if (empty($name)) {
        $message = "Department name is required";
        $message_type = "danger";
    } else {
        // Check if department name already exists
        $check_sql = "SELECT id FROM departments WHERE name = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $check_result = $stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $message = "Department name already exists";
            $message_type = "danger";
        } else {
            // Insert new department
            $insert_sql = "INSERT INTO departments (name, description) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ss", $name, $description);
            
            if ($stmt->execute()) {
                $message = "New department added successfully";
                $message_type = "success";
            } else {
                $message = "Error adding department: " . $conn->error;
                $message_type = "danger";
            }
        }
    }
}

// Handle department edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $dept_id = $_POST['dept_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    // Validate inputs
    if (empty($name)) {
        $message = "Department name is required";
        $message_type = "danger";
    } else {
        // Check if department name already exists for other departments
        $check_sql = "SELECT id FROM departments WHERE name = ? AND id != ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("si", $name, $dept_id);
        $stmt->execute();
        $check_result = $stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $message = "Department name already exists";
            $message_type = "danger";
        } else {
            // Update the department
            $update_sql = "UPDATE departments SET name = ?, description = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssi", $name, $description, $dept_id);
            
            if ($stmt->execute()) {
                $message = "Department updated successfully";
                $message_type = "success";
            } else {
                $message = "Error updating department: " . $conn->error;
                $message_type = "danger";
            }
        }
    }
}

// Get search parameters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build the query
$sql = "SELECT d.*, COUNT(u.id) as user_count 
        FROM departments d 
        LEFT JOIN users u ON d.id = u.department_id";

$params = [];
$types = "";

if (!empty($search_query)) {
    $sql .= " WHERE d.name LIKE ? OR d.description LIKE ?";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$sql .= " GROUP BY d.id ORDER BY d.name";

// Prepare and execute the statement
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$departments_result = $stmt->get_result();
?>

<!-- Department Management Content -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Department Management</h1>
        <div>
            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                <i class="bi bi-plus-circle"></i> Add New Department
            </button>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="departments.php" method="GET" class="row g-3">
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search departments" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="d-flex">
                        <div class="d-grid flex-grow-1">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                        <?php if (!empty($search_query)): ?>
                            <div class="ms-2">
                                <a href="departments.php" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Departments Table -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Departments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Users</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($departments_result->num_rows > 0): ?>
                            <?php while ($dept = $departments_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $dept['id']; ?></td>
                                    <td><?php echo htmlspecialchars($dept['name']); ?></td>
                                    <td><?php echo htmlspecialchars($dept['description'] ?? 'No description'); ?></td>
                                    <td>
                                        <?php echo $dept['user_count']; ?>
                                        <?php if ($dept['user_count'] > 0): ?>
                                            <a href="users.php?department=<?php echo $dept['id']; ?>" class="text-decoration-none ms-1">
                                                <i class="bi bi-eye-fill small"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary edit-dept-btn" 
                                                data-bs-toggle="modal" data-bs-target="#editDepartmentModal"
                                                data-id="<?php echo $dept['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($dept['name']); ?>"
                                                data-description="<?php echo htmlspecialchars($dept['description'] ?? ''); ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if ($dept['user_count'] == 0): ?>
                                            <a href="departments.php?delete=<?php echo $dept['id']; ?>" class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to delete this department?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No departments found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDepartmentModalLabel">Add New Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="departments.php" method="POST" id="addDepartmentForm">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Department Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addDepartmentForm" class="btn btn-primary">Add Department</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-labelledby="editDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDepartmentModalLabel">Edit Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="departments.php" method="POST" id="editDepartmentForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="dept_id" id="edit_dept_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Department Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editDepartmentForm" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Handle Edit Department button clicks
    document.addEventListener('DOMContentLoaded', function() {
        const editButtons = document.querySelectorAll('.edit-dept-btn');
        
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Set form values
                document.getElementById('edit_dept_id').value = this.dataset.id;
                document.getElementById('edit_name').value = this.dataset.name;
                document.getElementById('edit_description').value = this.dataset.description;
            });
        });
    });
</script>

<?php
// Include footer
include_once('../includes/footer.php');
?> 