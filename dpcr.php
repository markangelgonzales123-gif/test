<?php
// Start output buffering at the very beginning of the file
ob_start();

// Set page title
$page_title = "Department Performance Commitment and Review - EPMS";

// Include header
include_once('includes/header.php');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'department_head' && $_SESSION['user_role'] != 'admin')) {
    header("Location: access_denied.php");
    exit();
}

// Database connection
require_once 'includes/db_connect.php';

// Get user info
$user_id = $_SESSION['user_id'];
$department_id = $_SESSION['user_department_id'];

// Get department info
$dept_query = "SELECT d.*, u.name as head_name 
               FROM departments d 
               LEFT JOIN users u ON d.head_id = u.id
               WHERE d.id = ?";
$stmt = $conn->prepare($dept_query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$dept_result = $stmt->get_result();
$department = ($dept_result->num_rows > 0) ? $dept_result->fetch_assoc() : null;
$stmt->close();

// Get action from URL
$action = isset($_GET['action']) ? $_GET['action'] : 'view';
$record_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Function to generate periods (Quarters)
function generatePeriods() {
    $current_year = date('Y');
    $periods = [];
    
    // Generate quarters for the current year and the next year
    for ($i = 0; $i < 2; $i++) {
        $year = $current_year + $i;
        for ($q = 1; $q <= 4; $q++) {
            $periods[] = "Q$q $year";
        }
    }
    
    return $periods;
}

// Get computation types from database (or hardcoded defaults if not in DB)
function getComputationTypes() {
    // Updated descriptions to reflect weight changes for Type 2
    return [
        'Type1' => 'Strategic (45%) and Core (55%)',
        'Type2' => 'Strategic (45%), Core (45%), and Support (10%)'
    ];
}

// Handle form submission (Commitment and Review)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_dpcr'])) {

    // Validate that a Performance Period is selected
    $period = $_POST['period'] ?? '';
    if (empty($period)) {
        $_SESSION['error_message'] = "Please select a Performance Period before submitting.";
        // Redirect back to the previous form page
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // --- START: NEW SERVER-SIDE VALIDATION ---
    function validateDPCREntries($category, $post_data, &$error_message) {
        $cat_lower = strtolower($category);
        $mfo_key = $cat_lower . '_major_output';

        // Check if the primary array key exists. If not, there are no entries to validate.
        if (!isset($post_data[$mfo_key]) || !is_array($post_data[$mfo_key])) {
            $error_message = "You must have at least one entry for $category Functions.";
            return false; // No entries submitted for a required category.
        }

        $entry_count = count($post_data[$mfo_key]);
        $has_at_least_one_complete_entry = false;

        for ($i = 0; $i < $entry_count; $i++) {
            // These are the primary fields for the "Commitment" phase
            $major_output = trim($post_data[$cat_lower . '_major_output'][$i] ?? '');
            $indicators = trim($post_data[$cat_lower . '_success_indicators'][$i] ?? '');
            $accountable = trim($post_data[$cat_lower . '_accountable'][$i] ?? '');

            $commitment_fields = [$major_output, $indicators, $accountable];
            $filled_commitment_fields_count = count(array_filter($commitment_fields));
            
            // A row is considered "active" if any of its commitment fields are filled.
            if ($filled_commitment_fields_count > 0) {
                // If it's active but not all fields are filled, it's an error.
                if ($filled_commitment_fields_count < count($commitment_fields)) {
                    $error_message = "In $category Functions, row #" . ($i + 1) . " is incomplete. Please fill all commitment fields (Major Final Output, Success Indicators, Accountable Person/Office) or clear the row completely.";
                    return false;
                }
                
                // If all commitment fields are filled, this counts as a valid entry.
                if ($filled_commitment_fields_count === count($commitment_fields)) {
                    $has_at_least_one_complete_entry = true;
                }
            }
        }

        if (!$has_at_least_one_complete_entry) {
            $error_message = "You must have at least one complete entry for $category Functions. An entry is complete if the 'Major Final Output', 'Success Indicators', and 'Accountable Person/Office' fields are all filled.";
            return false;
        }

        return true;
    }

    $validation_error = '';
    $computation_type_val = $_POST['computation_type'] ?? 'Type1';

    // Get the correct redirect URL, preserving the record ID if it exists
    $redirect_url = 'dpcr.php?action=new';
    if (!empty($_GET['id'])) {
        $redirect_url = 'dpcr.php?action=edit&id=' . intval($_GET['id']);
    }

    if (!validateDPCREntries('Strategic', $_POST, $validation_error) || !validateDPCREntries('Core', $_POST, $validation_error)) {
        $_SESSION['error_message'] = $validation_error;
        header("Location: " . $redirect_url);
        exit();
    }
    if ($computation_type_val === 'Type2' && !validateDPCREntries('Support', $_POST, $validation_error)) {
        $_SESSION['error_message'] = $validation_error;
        header("Location: " . $redirect_url);
        exit();
    }
    // --- END: NEW SERVER-SIDE VALIDATION ---
    
    
    $period = $_POST['period'] ?? '';
    $status = $_POST['document_status'] ?? 'Draft';
    $computation_type = $_POST['computation_type'] ?? 'Type1';
    
    // Handle date submission only if status is changing to Pending or date wasn't set for review
    $date_submitted = null;
    if ($status === 'Submitted' || $status === 'Pending') {
        $date_submitted = date('Y-m-d H:i:s');
    }

    $conn->begin_transaction();
    try {
        
        // 1. Insert/Update Record in 'records' table
        if ($record_id > 0) {
            // Update existing record
            $update_record = "UPDATE records SET 
                             period = ?, 
                             document_status = ?,
                             date_submitted = ?,
                             computation_type = ?
                             WHERE id = ?";
            $stmt = $conn->prepare($update_record);
            $stmt->bind_param("ssssi", $period, $status, $date_submitted, $computation_type, $record_id);
            $stmt->execute();
            $stmt->close();
            
            // Delete existing entries to replace with new ones (full overwrite is safer)
            $delete_entries = "DELETE FROM dpcr_entries WHERE record_id = ?";
            $stmt_delete = $conn->prepare($delete_entries);
            $stmt_delete->bind_param("i", $record_id);
            $stmt_delete->execute();
            $stmt_delete->close();

        } else {
            // Insert new record
            $form_type = 'DPCR';
            $insert_record = "INSERT INTO records (user_id, form_type, period, document_status, date_submitted, computation_type) 
                             VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_record);
            $stmt->bind_param("isssss", $user_id, $form_type, $period, $status, $date_submitted, $computation_type);
            $stmt->execute();
            $record_id = $conn->insert_id;
            $stmt->close();
        }

        // 2. Insert new entries (Commitment + Review data)
        $insert_entry = "INSERT INTO dpcr_entries 
                         (record_id, major_output, success_indicators, budget, accountable, category, 
                          actual_accomplishments, q_rating, e_rating, t_rating, a_rating, remarks) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert_entry = $conn->prepare($insert_entry);

        // Function to process and insert entries for a category
        function processAndInsertEntries($conn, $stmt_insert_entry, $record_id, $category, $post_data) {
            if (isset($post_data[strtolower($category) . '_major_output']) && is_array($post_data[strtolower($category) . '_major_output'])) {
                $count = count($post_data[strtolower($category) . '_major_output']);
                
                for ($i = 0; $i < $count; $i++) {
                    $major_output = $post_data[strtolower($category) . '_major_output'][$i] ?? '';
                    if (empty($major_output)) continue;
                    
                    $success_indicators = $post_data[strtolower($category) . '_success_indicators'][$i] ?? '';
                    $budget = !empty($post_data[strtolower($category) . '_budget'][$i]) ? floatval($post_data[strtolower($category) . '_budget'][$i]) : null;
                    $accountable = $post_data[strtolower($category) . '_accountable'][$i] ?? '';

                    // Review Fields
                    $actual_accomplishments = $post_data[strtolower($category) . '_actual_accomplishments'][$i] ?? '';
                    $q_rating = !empty($post_data[strtolower($category) . '_q_rating'][$i]) ? floatval($post_data[strtolower($category) . '_q_rating'][$i]) : null;
                    $e_rating = !empty($post_data[strtolower($category) . '_e_rating'][$i]) ? floatval($post_data[strtolower($category) . '_e_rating'][$i]) : null;
                    $t_rating = !empty($post_data[strtolower($category) . '_t_rating'][$i]) ? floatval($post_data[strtolower($category) . '_t_rating'][$i]) : null;
                    
                    // Auto-calculate A-rating
                    $a_rating = ($q_rating !== null && $e_rating !== null && $t_rating !== null) ? round(($q_rating + $e_rating + $t_rating) / 3, 2) : null;
                    $remarks = $post_data[strtolower($category) . '_remarks'][$i] ?? '';

                    // Bind and execute
                    // Types: int, str, str, dec, str, str, str, dec, dec, dec, dec, str
                    // Note: 'd' for decimal (float), 's' for string (text/varchar)
                    $stmt_insert_entry->bind_param("issss" . "sssddds", 
                        $record_id, $major_output, $success_indicators, $budget, $accountable, $category,
                        $actual_accomplishments, $q_rating, $e_rating, $t_rating, $a_rating, $remarks
                    );
                    $stmt_insert_entry->execute();
                }
            }
        }
        
        processAndInsertEntries($conn, $stmt_insert_entry, $record_id, 'Strategic', $_POST);
        processAndInsertEntries($conn, $stmt_insert_entry, $record_id, 'Core', $_POST);
        
        if ($computation_type === 'Type2') {
            processAndInsertEntries($conn, $stmt_insert_entry, $record_id, 'Support', $_POST);
        }
        
        $stmt_insert_entry->close();
        
        $conn->commit();
        
        $message = ($status === 'Submitted' || $status === 'Pending') ? 'DPCR submitted successfully!' : 'DPCR saved as draft!';
        $_SESSION['success_message'] = $message;
        
        // This is the line that caused the error, but ob_start() fixes it.
        header("Location: dpcr.php?action=view&id=" . $record_id);
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        // Redirect back to the form in edit mode
        $redirect_id = $record_id > 0 ? $record_id : '';
        // This is another line that caused the error.
        header("Location: dpcr.php?action=edit&id=" . $redirect_id);
        exit();
    }
}

// Load existing DPCR data if editing or viewing
$dpcr_data = [];
$strategic_entries = [];
$core_entries = [];
$support_entries = [];

if ($record_id > 0) {
    // Get record data
    $record_query = "SELECT * FROM records WHERE id = ? AND form_type = 'DPCR'";
    $stmt = $conn->prepare($record_query);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $record_result = $stmt->get_result();
    
    if ($record_result->num_rows > 0) {
        $dpcr_data = $record_result->fetch_assoc();
        
        // Check permissions and status
        if ($action === 'edit' && $dpcr_data['user_id'] != $user_id && $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error_message'] = "You don't have permission to edit this DPCR!";
            header("Location: records.php");
            exit();
        }
        if ($action === 'edit' && $dpcr_data['document_status'] !== 'Draft') {
            $_SESSION['error_message'] = "Only drafts can be edited!";
            header("Location: dpcr.php?action=view&id=" . $record_id);
            exit();
        }
        
        // Get DPCR entries
        $entries_query = "SELECT * FROM dpcr_entries WHERE record_id = ? ORDER BY id ASC";
        $stmt = $conn->prepare($entries_query);
        $stmt->bind_param("i", $record_id);
        $stmt->execute();
        $entries_result = $stmt->get_result();
        
        // Organize entries by category
        while ($entry = $entries_result->fetch_assoc()) {
            if ($entry['category'] === 'Strategic') {
                $strategic_entries[] = $entry;
            } elseif ($entry['category'] === 'Core') {
                $core_entries[] = $entry;
            } elseif ($entry['category'] === 'Support') {
                $support_entries[] = $entry;
            }
        }
    } else {
        // Record ID provided but not found or not DPCR for this department
        $action = 'new';
        $record_id = 0;
    }
}

// NEW: If no record ID is provided on initial load, default to 'new' action
if ($record_id === 0) {
    $action = 'new';
}


// Initialize default variables for display if not set (to prevent "Undefined variable" warnings)
$period = $dpcr_data['period'] ?? '';
$status = $dpcr_data['document_status'] ?? 'Draft';
$computation_type = $dpcr_data['computation_type'] ?? 'Type1';

// Determine initial weights for display
$strategic_weight_display = ($computation_type === 'Type2') ? '(45%)' : '(45%)';
$core_weight_display = ($computation_type === 'Type2') ? '(45%)' : '(55%)';
$support_display_style = ($computation_type === 'Type2') ? 'block' : 'none';

// Ensure we have at least one empty entry for each category when creating/editing
if ($action === 'new' || $action === 'edit') {
    if (empty($strategic_entries)) {
        $strategic_entries[] = ['major_output' => '', 'success_indicators' => '', 'budget' => '', 'accountable' => '', 'actual_accomplishments' => '', 'q_rating' => '', 'e_rating' => '', 't_rating' => '', 'remarks' => ''];
    }
    if (empty($core_entries)) {
        $core_entries[] = ['major_output' => '', 'success_indicators' => '', 'budget' => '', 'accountable' => '', 'actual_accomplishments' => '', 'q_rating' => '', 'e_rating' => '', 't_rating' => '', 'remarks' => ''];
    }
    // Only add default empty entry for Support if Type2 is the current type, or if we loaded data
    if (empty($support_entries) && $computation_type === 'Type2') {
         $support_entries[] = ['major_output' => '', 'success_indicators' => '', 'budget' => '', 'accountable' => '', 'actual_accomplishments' => '', 'q_rating' => '', 'e_rating' => '', 't_rating' => '', 'remarks' => ''];
    }
}


// Override defaults for new record
if ($action === 'new') {
    // Default period to the current Q and Year + 1 year, e.g., Q4 2025
    $periods = generatePeriods();
    $period = $periods[0] ?? date('Y') . '-' . (date('Y') + 1);
    $status = 'Draft';
    $computation_type = 'Type1';
    
    // Reset display based on new default
    $strategic_weight_display = '(45%)';
    $core_weight_display = '(55%)';
    $support_display_style = 'none';
}

// Check if we have success/error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';

// Clear session messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Function to generate the HTML for a single entry row
function generateEntryHtml($entry, $category, $action) {
    $cat_prefix = strtolower($category);
    $is_view = $action === 'view';
    
    // Check if review fields are populated to determine if review should be visible in view mode
    $has_review_data = !empty($entry['actual_accomplishments']) || !empty($entry['q_rating']) || !empty($entry['e_rating']) || !empty($entry['t_rating']) || !empty($entry['remarks']);
    
    // Determine visibility of review section. Always show in edit/new, show in view if data exists.
    $show_review = !$is_view || $has_review_data;

    $html = '<div class="row g-2 mb-4 p-3 border rounded shadow-sm ' . $cat_prefix . '-entry">';
    
    // ---------------------- Commitment Phase (Always Visible) ----------------------
    $html .= '<div class="col-md-3"><label class="form-label fw-bold">Major Final Output</label>';
    $html .= $is_view ? nl2br(htmlspecialchars($entry['major_output'])) : '<textarea class="form-control" name="' . $cat_prefix . '_major_output[]" rows="3" required>' . htmlspecialchars($entry['major_output'] ?? '') . '</textarea>';
    $html .= '</div>';

    $html .= '<div class="col-md-3"><label class="form-label fw-bold">Success Indicators</label>';
    $html .= $is_view ? nl2br(htmlspecialchars($entry['success_indicators'])) : '<textarea class="form-control" name="' . $cat_prefix . '_success_indicators[]" rows="3" required>' . htmlspecialchars($entry['success_indicators'] ?? '') . '</textarea>';
    $html .= '</div>';
    
    $html .= '<div class="col-md-2"><label class="form-label fw-bold">Budget</label>';
    $budget_display = $entry['budget'] ? number_format($entry['budget'], 2) : 'N/A';
    $html .= $is_view ? $budget_display : '<input type="number" class="form-control" name="' . $cat_prefix . '_budget[]" step="1" value="' . htmlspecialchars($entry['budget'] ?? '') . '">';
    $html .= '</div>';
    
    $html .= '<div class="col-md-3"><label class="form-label fw-bold">Accountable Person/Office</label>';
    $html .= $is_view ? htmlspecialchars($entry['accountable']) : '<input type="text" class="form-control" name="' . $cat_prefix . '_accountable[]" value="' . htmlspecialchars($entry['accountable'] ?? '') . '" required>';
    $html .= '</div>';

    $html .= '<div class="col-md-1 d-flex align-items-end">';
    if (!$is_view) {
        $html .= '<button type="button" class="btn btn-danger btn-sm d-block w-100 remove-entry"><i class="bi bi-trash"></i> Remove</button>';
    }
    $html .= '</div>';
    
    // ---------------------- Review Phase (Visibility Controlled) ----------------------
    if ($show_review) {
        $html .= '<div class="col-12"><hr class="my-2"></div>';
        $html .= '<div class="col-md-6"><label class="form-label fw-bold text-success">Actual Accomplishments</label>';
        $html .= $is_view ? nl2br(htmlspecialchars($entry['actual_accomplishments'] ?? '')) : '<textarea class="form-control" name="' . $cat_prefix . '_actual_accomplishments[]" rows="3">' . htmlspecialchars($entry['actual_accomplishments'] ?? '') . '</textarea>';
        $html .= '</div>';

        $html .= '<div class="col-md-3"><label class="form-label fw-bold text-success">Ratings (Q/E/T)</label>';
        $html .= '<div class="input-group">';
        if ($is_view) {
            $html .= '<span class="form-control text-center">Q: <strong>' . htmlspecialchars($entry['q_rating'] ?? '-') . '</strong></span>';
            $html .= '<span class="form-control text-center">E: <strong>' . htmlspecialchars($entry['e_rating'] ?? '-') . '</strong></span>';
            $html .= '<span class="form-control text-center">T: <strong>' . htmlspecialchars($entry['t_rating'] ?? '-') . '</strong></span>';
        } else {
            $html .= '<input type="number" class="form-control" name="' . $cat_prefix . '_q_rating[]" placeholder="Q" step="1" min="1" max="5" value="' . htmlspecialchars($entry['q_rating'] ?? '') . '">';
            $html .= '<input type="number" class="form-control" name="' . $cat_prefix . '_e_rating[]" placeholder="E" step="1" min="1" max="5" value="' . htmlspecialchars($entry['e_rating'] ?? '') . '">';
            $html .= '<input type="number" class="form-control" name="' . $cat_prefix . '_t_rating[]" placeholder="T" step="1" min="1" max="5" value="' . htmlspecialchars($entry['t_rating'] ?? '') . '">';
        }
        $html .= '</div>';
        
        if ($is_view) {
             $html .= '<div class="mt-1 text-center bg-light border p-1">Average (A): <strong>' . htmlspecialchars($entry['a_rating'] ?? '-') . '</strong></div>';
        }

        $html .= '</div>';

        $html .= '<div class="col-md-3"><label class="form-label fw-bold text-success">Remarks</label>';
        $html .= $is_view ? nl2br(htmlspecialchars($entry['remarks'] ?? '')) : '<textarea class="form-control" name="' . $cat_prefix . '_remarks[]" rows="3">' . htmlspecialchars($entry['remarks'] ?? '') . '</textarea>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    return $html;
}

?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <?php 
            if ($action === 'new') echo 'Create New DPCR';
            else if ($action === 'edit') echo 'Edit DPCR';
            else echo 'View DPCR';
            ?>
        </h1>
        <div>
            <a href="records.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Records
            </a>
            <?php if ($action === 'view' && $record_id > 0 && isset($dpcr_data['document_status']) && $dpcr_data['document_status'] === 'Approved'): ?>
            <a href="print_record.php?id=<?php echo $record_id; ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-printer"></i> Print DPCR
            </a>
        <?php endif; ?>
        </div>
    </div>
    
    <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="card shadow">
        <div class="card-header bg-white">
            <h5 class="mb-0">Department Performance Commitment and Review (DPCR)</h5>
        </div>
        <div class="card-body">
            <?php if ($action === 'new' && $record_id === 0): ?>
                <form method="post" action="dpcr.php" id="dpcrForm">
            <?php elseif (($action === 'edit' || $action === 'view') && $record_id > 0): ?>
                <form method="post" action="dpcr.php?id=<?php echo $record_id; ?>" id="dpcrForm">
            <?php else: ?>
                <div class="text-center py-5">
                    <p class="lead">No DPCR found for the current department for the current period or request.</p>
                    <a href="dpcr.php?action=new" class="btn btn-lg btn-success">
                        <i class="bi bi-plus-circle"></i> Create New DPCR
                    </a>
                </div>
            </form>
            <?php 
            // End output buffering and flush the content for this block only
            ob_end_flush(); 
            include_once('includes/footer.php'); return; endif; ?>


                <div class="row mb-4 bg-light p-3 border rounded">
                    <div class="col-md-4">
                        <label for="department" class="form-label fw-bold">Department</label>
                        <input type="text" class="form-control" id="department" value="<?php echo htmlspecialchars($department['name'] ?? ''); ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label for="head" class="form-label fw-bold">Department Head</label>
                        <input type="text" class="form-control" id="head" value="<?php echo htmlspecialchars($department['head_name'] ?? ''); ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label for="period" class="form-label fw-bold">Performance Period</label>
                        <?php if ($action === 'view'): ?>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($period); ?>" readonly>
                            <input type="hidden" name="period" value="<?php echo htmlspecialchars($period); ?>">
                        <?php else: ?>
                            <select class="form-select" id="period" name="period" required>
                                <option value="">-- Select Period --</option>
                                <?php foreach (generatePeriods() as $p): ?>
                                    <option value="<?php echo $p; ?>" <?php echo ($period === $p) ? 'selected' : ''; ?>>
                                        <?php echo $p; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="col-12 mt-3">
                        <span class="badge bg-primary fs-6 p-2">Current Status: <?php echo htmlspecialchars($status); ?></span>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="computation_type" class="form-label fw-bold">DPCR Weight Distribution</label>
                    <?php if ($action === 'view'): ?>
                        <input type="text" class="form-control" value="<?php echo getComputationTypes()[$computation_type] ?? 'Type 1 Default'; ?>" readonly>
                        <input type="hidden" name="computation_type" value="<?php echo htmlspecialchars($computation_type); ?>">
                    <?php else: ?>
                        <select class="form-select" name="computation_type" id="computation_type" required>
                            <?php
                            $computation_types_list = getComputationTypes();
                            foreach ($computation_types_list as $type => $description) {
                                $selected = ($computation_type === $type) ? 'selected' : '';
                                echo "<option value=\"$type\" $selected>$description</option>";
                            }
                            ?>
                        </select>
                        <div class="form-text">Changing this affects which sections are required/visible.</div>
                    <?php endif; ?>
                </div>
                
                <h4 class="mt-4 text-primary">
                    I. Strategic Functions 
                    <span id="strategic_weight" class="float-end"><?php echo $strategic_weight_display; ?></span>
                </h4>
                <div id="strategic_functions">
                    <?php foreach ($strategic_entries as $entry): ?>
                        <?php echo generateEntryHtml($entry, 'Strategic', $action); ?>
                    <?php endforeach; ?>
                </div>
                <?php if ($action === 'new' || $action === 'edit'): ?>
                <button type="button" class="btn btn-sm btn-success mb-4" id="add_strategic_entry">
                    <i class="bi bi-plus-circle"></i> Add Strategic Output
                </button>
                <?php endif; ?>

                <h4 class="mt-4 text-primary">
                    II. Core Functions 
                    <span id="core_weight" class="float-end"><?php echo $core_weight_display; ?></span>
                </h4>
                <div id="core_functions">
                    <?php foreach ($core_entries as $entry): ?>
                        <?php echo generateEntryHtml($entry, 'Core', $action); ?>
                    <?php endforeach; ?>
                </div>
                <?php if ($action === 'new' || $action === 'edit'): ?>
                <button type="button" class="btn btn-sm btn-success mb-4" id="add_core_entry">
                    <i class="bi bi-plus-circle"></i> Add Core Output
                </button>
                <?php endif; ?>
                
                <!-- The Support Section: Visibility controlled by PHP on initial load and JS on change -->
                <h4 class="mt-4 text-primary" id="support_title" style="display: <?php echo $support_display_style; ?>;">
                    III. Support Functions <span class="float-end">(10%)</span>
                </h4>
                <div id="support_functions" style="display: <?php echo $support_display_style; ?>;">
                    <?php 
                    // This conditional ensures that if we are viewing a saved Type2 form, the data is shown.
                    // If we are editing/new and it's Type2, it loads the default empty entry (handled above).
                    if ($computation_type === 'Type2' || !empty($support_entries)):
                        foreach ($support_entries as $entry): 
                            echo generateEntryHtml($entry, 'Support', $action); 
                        endforeach;
                    endif;
                    ?>
                </div>
                <?php if ($action === 'new' || $action === 'edit'): ?>
                <button type="button" class="btn btn-sm btn-info mb-4" id="add_support_entry" style="display: <?php echo $support_display_style; ?>;">
                    <i class="bi bi-plus-circle"></i> Add Support Output
                </button>
                <?php endif; ?>
                
                <?php if ($action === 'new' || $action === 'edit'): ?>
                <div class="d-flex justify-content-between mt-4 border-top pt-3">
                    <button type="submit" name="document_status" value="Draft" class="btn btn-primary me-2">Save as Draft</button>
                    <button type="submit" name="document_status" value="Pending" class="btn btn-success">Submit DPCR</button>
                    <input type="hidden" name="save_dpcr" value="1">
                </div>
                <?php endif; ?>
                
                <?php if ($action === 'view' && $record_id > 0): ?>
                <div class="mt-4 text-center">
                    <?php if ($status === 'Draft'): ?>
                        <a href="dpcr.php?action=edit&id=<?php echo $record_id; ?>" class="btn btn-warning"><i class="bi bi-pencil"></i> Edit DPCR</a>
                    <?php endif; ?>
                    <a href="records.php" class="btn btn-secondary">Done Viewing</a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<!-- FIX: Load jQuery before our custom script uses the $ alias -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<script>
// JavaScript for dynamic row adding and removal and weight display
$(document).ready(function() {
    
    // --- Computation Type Logic ---
    function updateComputationTypeDisplay() {
        var computationType = $('#computation_type').val();
        
        console.log("DPCR Type changed to:", computationType);
        
        if (computationType === 'Type2') {
            // Weights change to 45% / 45% / 10%
            $('#strategic_weight').text('(45%)');
            $('#core_weight').text('(45%)');
            
            // Support section becomes visible
            // Use .css('display', 'block') for explicit visibility
            $('#support_functions').css('display', 'block');
            $('#support_title').css('display', 'block');
            $('#add_support_entry').css('display', 'block');
            
            console.log("-> Displaying Support section with 45/45/10 weights.");
            
            // If there are no entries in the Support section yet, add one empty row
            if ($('#support_functions').children('.support-entry').length === 0) {
                 // Add an empty entry template on first display for a new form
                 if ($('#dpcrForm').attr('action').indexOf('action=new') > -1 || $('#dpcrForm').attr('action').indexOf('action=edit') > -1) {
                     $('#support_functions').append(entryTemplate('Support'));
                 }
            }
            
        } else {
            // Weights revert to 45% / 55% / (0%)
            $('#strategic_weight').text('(45%)');
            $('#core_weight').text('(55%)');
            
            // Support section is hidden
            $('#support_functions').css('display', 'none');
            $('#support_title').css('display', 'none');
            $('#add_support_entry').css('display', 'none');
            
            console.log("-> Hiding Support section with 45/55 weights.");
        }
    }
    
    // Bind the function to the change event
    $('#computation_type').on('change', function() {
        updateComputationTypeDisplay();
    });
    
    // Initialize display on load
    updateComputationTypeDisplay();
    
    // --- Dynamic Row Functions (using the PHP function structure) ---
    const entryTemplate = (category) => {
        const cat_prefix = category.toLowerCase();
        // The HTML template includes the Commitment part and the Review part (which is visible in edit/new mode)
        return `
            <div class="row g-2 mb-4 p-3 border rounded shadow-sm ${cat_prefix}-entry">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Major Final Output</label>
                    <textarea class="form-control" name="${cat_prefix}_major_output[]" rows="3" required></textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Success Indicators</label>
                    <textarea class="form-control" name="${cat_prefix}_success_indicators[]" rows="3" required></textarea>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Budget</label>
                    <input type="number" class="form-control" name="${cat_prefix}_budget[]" step="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Accountable Person/Office</label>
                    <input type="text" class="form-control" name="${cat_prefix}_accountable[]" required>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-sm d-block w-100 remove-entry"><i class="bi bi-trash"></i> Remove</button>
                </div>
                
                <div class="col-12"><hr class="my-2"></div>
                
                <div class="col-md-6">
                    <label class="form-label fw-bold text-success">Actual Accomplishments</label>
                    <textarea class="form-control" name="${cat_prefix}_actual_accomplishments[]" rows="3"></textarea>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label fw-bold text-success">Ratings (Q/E/T)</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="${cat_prefix}_q_rating[]" placeholder="Q" step="1" min="1" max="5">
                        <input type="number" class="form-control" name="${cat_prefix}_e_rating[]" placeholder="E" step="1" min="1" max="5">
                        <input type="number" class="form-control" name="${cat_prefix}_t_rating[]" placeholder="T" step="1" min="1" max="5">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label fw-bold text-success">Remarks</label>
                    <textarea class="form-control" name="${cat_prefix}_remarks[]" rows="3"></textarea>
                </div>
            </div>
        `;
    };

    // Add Strategic entry
    $('#add_strategic_entry').click(function() {
        $('#strategic_functions').append(entryTemplate('Strategic'));
    });

    // Add Core entry
    $('#add_core_entry').click(function() {
        $('#core_functions').append(entryTemplate('Core'));
    });
    
    // Add Support entry
    $('#add_support_entry').click(function() {
        $('#support_functions').append(entryTemplate('Support'));
    });
    
    // Remove entry
    $(document).on('click', '.remove-entry', function() {
        $(this).closest('.row').remove();
    });
});
</script>

<?php
// Include footer
include_once('includes/footer.php');

// End output buffering and flush the content to the browser
ob_end_flush(); 
?>
