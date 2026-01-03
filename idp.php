<?php
// Set page title
$page_title = "Individual Development Plan - EPMS";

// Include header
include_once('includes/header.php');

// Check if user has the right role to access this page
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'regular_employee' && $_SESSION['user_role'] !== 'department_head' && $_SESSION['user_role'] !== 'president' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: access_denied.php");
    exit();
}

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "epms_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_department_id = $_SESSION['user_department_id'];
$employee_name = $_SESSION['user_name'] ?? 'Employee Name N/A';

// Get department name
$dept_query = "SELECT name FROM departments WHERE id = ?";
$stmt = $conn->prepare($dept_query);
$stmt->bind_param("i", $user_department_id);
$stmt->execute();
$dept_result = $stmt->get_result();
$department_name = ($dept_result->num_rows > 0) ? $dept_result->fetch_assoc()['name'] : 'N/A';
$stmt->close();

// --- HANDLE POST SUBMISSION ---
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Collect Form Data
    $record_id = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;
    $period = $_POST['period'] ?? '';
    
    // Build IDP Data Array
    $main_objectives = $_POST['main_objectives'] ?? [];
    $plan_of_action = $_POST['plan_of_action'] ?? [];
    $target_date = $_POST['target_date'] ?? [];
    
    $new_idp_goals = [];
    $count = count($main_objectives);
    
    for ($i = 0; $i < $count; $i++) {
        $obj = trim($main_objectives[$i]);
        // Save row if objective is not empty (or if it's the only row, keep it empty structure)
        if (!empty($obj) || $count === 1) {
            $new_idp_goals[] = [
                'objective' => $obj,
                'action_plan' => trim($plan_of_action[$i] ?? ''),
                'target_date' => trim($target_date[$i] ?? ''),
            ];
        }
    }
    
    // Prepare JSON content
    $content_array = ['idp_goals' => $new_idp_goals];
    $content_json = json_encode($content_array, JSON_UNESCAPED_UNICODE);

    // 2. Process Action (Draft or Submit)
    if (isset($_POST['save_draft'])) {
        if ($record_id > 0) {
            // Update Existing Draft
            $update_query = "UPDATE records SET content = ?, period = ? WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssii", $content_json, $period, $record_id, $user_id);
            if ($stmt->execute()) {
                $message = "IDP draft updated successfully.";
                $message_type = "success";
            } else {
                $message = "Error updating draft: " . $conn->error;
                $message_type = "danger";
            }
            $stmt->close();
        } else {
            // Create New Draft
            $insert_query = "INSERT INTO records (user_id, form_type, period, content, status) VALUES (?, 'IDP', ?, ?, 'Draft')";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iss", $user_id, $period, $content_json);
            if ($stmt->execute()) {
                $record_id = $conn->insert_id; // Capture new ID
                $message = "IDP saved as draft successfully.";
                $message_type = "success";
            } else {
                $message = "Error saving draft: " . $conn->error;
                $message_type = "danger";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['submit_idp'])) {
        // Submit for Review
        $dept_head_name = "your department head"; // Default value
        
        // Use the same logic as IPCR to get the department head's name
        $dept_head_query = "SELECT u.name, u.email FROM users u 
                            WHERE u.department_id = ? AND u.role = 'department_head'";
        $dept_stmt = $conn->prepare($dept_head_query);
        $dept_stmt->bind_param("i", $user_department_id);
        $dept_stmt->execute();
        $dept_head_result = $dept_stmt->get_result();
        $dept_head = ($dept_head_result->num_rows > 0) ? $dept_head_result->fetch_assoc() : null;
        if ($dept_head) {
            $dept_head_name = $dept_head['name'];
        }
        $dept_stmt->close();


        if (function_exists('submitForm')) {
            // Based on IPCR, we pass parameters to create/submit.
            $result = submitForm($conn, $user_id, 'IDP', $period, $content_json);
            
            if ($result['success']) {
                $message = "
                    <div class='mb-3'><strong class='fs-5'>IDP form submitted successfully!</strong></div>
                    <div class='d-flex align-items-center mb-2'>
                        <div class='me-3'><i class='fas fa-check-circle text-success fs-3'></i></div>
                        <div>
                            Your form has been sent to <strong>" . htmlspecialchars($dept_head_name) . "</strong> for review. 
                            You will be notified when your submission has been reviewed.
                        </div>
                    </div>";
                $message_type = "success";
                // If submitForm returns a record ID or we can fetch the latest, we could set $record_id here to show the print button immediately.
            } else {
                $message = "Error submitting IDP: " . $result['message'];
                $message_type = "danger";
            }
        } else {
            // Fallback manual submission if function missing
            $status = 'Pending'; // Or 'Submitted'
            if ($record_id > 0) {
                $update_query = "UPDATE records SET content = ?, period = ?, status = ? WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("sssi", $content_json, $period, $status, $record_id);
                $stmt->execute();
            } else {
                $insert_query = "INSERT INTO records (user_id, form_type, period, content, status) VALUES (?, 'IDP', ?, ?, ?)";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("isss", $user_id, $period, $content_json, $status);
                $stmt->execute();
                $record_id = $conn->insert_id;
            }
            $message = "<strong>Success!</strong> Your IDP has been submitted to " . htmlspecialchars($dept_head_name) . " for review.";
            $message_type = "success";
        }
    }
}

// --- FETCH DATA (If Editing or after Save) ---
$current_record_id = isset($_GET['id']) ? intval($_GET['id']) : ($record_id ?? 0);
$idp_entries = [];
$current_period = '';
$record_status = '';

if ($current_record_id > 0) {
    $fetch_query = "SELECT content, period, status FROM records WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($fetch_query);
    $stmt->bind_param("ii", $current_record_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $content_json = $row['content'];
        $content_array = json_decode($content_json, true);
        $idp_entries = $content_array['idp_goals'] ?? [];
        $current_period = $row['period'];
        $record_status = $row['status'];
    }
    $stmt->close();
}

// Default empty row if new
if (empty($idp_entries)) {
    $idp_entries[] = ['objective' => '', 'action_plan' => '', 'target_date' => ''];
}
?>
<!-- HTML Content -->
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Individual Development Plan</h1>
        
        <?php if ($current_record_id > 0): ?>
            <!-- Print Button only shows if we have a saved record ID -->
            <a href="print_record.php?id=<?php echo $current_record_id; ?>" target="_blank" class="btn btn-primary">
                <i class="fas fa-print"></i> Print IDP
            </a>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Employee Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Employee:</strong> <?php echo htmlspecialchars($employee_name); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($department_name); ?></p>
                </div>
            </div>
            <?php if($record_status): ?>
                <p><strong>Status:</strong> <span class="badge bg-<?php echo ($record_status == 'Approved' ? 'success' : ($record_status == 'Pending' ? 'warning' : 'secondary')); ?>"><?php echo $record_status; ?></span></p>
            <?php endif; ?>
        </div>
    </div>

    <form method="POST" action="idp.php<?php echo ($current_record_id > 0 ? '?id='.$current_record_id : ''); ?>">
        <!-- Hidden input for the record ID used for saving -->
        <input type="hidden" name="record_id" value="<?php echo $current_record_id; ?>">

        <!-- Period Selection -->
        <div class="mb-3">
            <label for="period" class="form-label fw-bold">Period Covered:</label>
            <input type="text" class="form-control" name="period" id="period" value="<?php echo htmlspecialchars($current_period); ?>" placeholder="e.g. January 2024 to December 2024" required>
        </div>

        <div class="card shadow mt-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Development Goals (IDP)</h5>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 40%;">Main Objective/s</th>
                            <th style="width: 40%;">Plan of Action</th>
                            <th style="width: 20%;">Target Date</th>
                        </tr>
                    </thead>
                    <tbody id="idp-entries-body">
                        <?php foreach ($idp_entries as $index => $entry): ?>
                            <tr class="idp-entry-row">
                                <td>
                                    <textarea class="form-control" name="main_objectives[]" rows="5" placeholder="Enter objectives here..."><?php echo htmlspecialchars($entry['objective'] ?? ''); ?></textarea>
                                </td>
                                <td>
                                    <textarea class="form-control" name="plan_of_action[]" rows="5" placeholder="Enter plan of action here..."><?php echo htmlspecialchars($entry['action_plan'] ?? ''); ?></textarea>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="target_date[]" value="<?php echo htmlspecialchars($entry['target_date'] ?? ''); ?>" placeholder="e.g. May 2024">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <button type="submit" name="save_draft" class="btn btn-secondary btn-lg">
                <i class="fas fa-save"></i> Save as Draft
            </button>
            <button type="submit" name="submit_idp" class="btn btn-success btn-lg" onclick="return confirm('Are you sure you want to submit this IDP to your Department Head?');">
                <i class="fas fa-paper-plane"></i> Submit for Review
            </button>
        </div>
    </form>
</div>

<?php
// Close database connection
$conn->close();
?>