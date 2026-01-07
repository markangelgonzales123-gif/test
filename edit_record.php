<?php
// Set page title
$page_title = "Edit Record - EPMS";

// Include header
include_once('includes/header.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if record ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: records.php");
    exit();
}

$record_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_department_id = $_SESSION['user_department_id'];

// Database connection
require_once 'includes/db_connect.php';

// Get record details
$query = "SELECT r.*, u.name as user_name, u.department_id, d.name as department_name
          FROM records r
          INNER JOIN users u ON r.user_id = u.id
          INNER JOIN departments d ON u.department_id = d.id
          WHERE r.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: records.php");
    exit();
}

$record = $result->fetch_assoc();

// Check permissions based on the new IPCR workflow
$can_edit = false;
$status = $record['document_status'];

if ($user_role == 'admin' || $user_role == 'president') {
    // Admins and Presidents have full edit access
    $can_edit = true;
} else if ($user_role == 'department_head' && $user_department_id == $record['department_id']) {
    // DH can edit templates, review submissions, or add comments to rejections
    if ($record['form_type'] == 'IPCR' && in_array($status, ['Distributed', 'For Review', 'Rejected'])) {
        $can_edit = true;
    }
    // Allow DH to edit DPCRs as before
    if ($record['form_type'] == 'DPCR' && in_array($status, ['Pending', 'Draft'])) {
         $can_edit = true;
    }
} else if ($user_role == 'regular_employee' && $user_id == $record['user_id']) {
    // Employee can only edit their own IPCR if it was rejected
    if ($record['form_type'] == 'IPCR' && $status == 'Rejected') {
        $can_edit = true;
    }
     // Allow employees to edit their own drafts of other forms (maintains original logic)
    if (in_array($status, ['Pending', 'Draft'])) {
        $can_edit = true;
    }
}

// If user doesn't have permission to edit
if (!$can_edit) {
    header("Location: access_denied.php");
    exit();
}

// Decode JSON content if exists
$content = null;
if (!empty($record['content'])) {
    $content = json_decode($record['content'], true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $form_type = $record['form_type'];
    $updated_content_json = $_POST['content'] ?? '';
    $new_status = $record['document_status']; // Default to current status
    $redirect_to_view = true;
    
    // =================================================================================
    // NEW IPCR WORKFLOW POST HANDLING
    // =================================================================================
    if ($form_type === 'IPCR') {
        if (isset($_POST['approve_ipcr'])) {
            $new_status = 'Approved';
            // Set the approval date
            $update_query = "UPDATE records SET content = ?, document_status = ?, date_approved = NOW(), date_submitted = NOW() WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssi", $updated_content_json, $new_status, $record_id);
        } elseif (isset($_POST['reject_ipcr'])) {
            $new_status = 'Rejected';
            $update_query = "UPDATE records SET content = ?, document_status = ?, date_submitted = NOW() WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssi", $updated_content_json, $new_status, $record_id);
        } elseif (isset($_POST['resubmit_ipcr'])) {
            $new_status = 'For Review';
            $update_query = "UPDATE records SET content = ?, document_status = ?, date_submitted = NOW() WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssi", $updated_content_json, $new_status, $record_id);
        } else { // Generic update (e.g., DH editing a template)
             $redirect_to_view = false; // Stay on edit page for template updates
             $update_query = "UPDATE records SET content = ?, date_submitted = NOW() WHERE id = ?";
             $stmt = $conn->prepare($update_query);
             $stmt->bind_param("si", $updated_content_json, $record_id);
        }

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "IPCR record updated successfully.";
            if ($redirect_to_view) {
                exit();
            } else {
                // Refresh the page to show the "updated" message and latest data
                header("Location: edit_record.php?id=" . $record_id . "&update=success");
                exit();
            }
        } else {
            $message = "Error updating IPCR record: " . $conn->error;
            $message_type = "danger";
        }

    // Original DPCR form handling
    } elseif ($form_type === 'DPCR' && isset($_POST['update_record'])) {
        // First delete existing entries
        $delete_query = "DELETE FROM dpcr_entries WHERE record_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $record_id);
        $delete_stmt->execute();
        
        // Then insert new entries
        $entry_query = "INSERT INTO dpcr_entries (record_id, major_output, success_indicators, budget, accountable, accomplishments, q1_rating, q2_rating, q3_rating, q4_rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $entry_stmt = $conn->prepare($entry_query);
        
        for ($i = 0; $i < count($_POST['major_output']); $i++) {
            $major_output = $_POST['major_output'][$i] ?? '';
            $success_indicators = $_POST['success_indicators'][$i] ?? '';
            $budget = $_POST['budget'][$i] ?? '';
            $accountable = $_POST['accountable'][$i] ?? '';
            $accomplishments = $_POST['accomplishments'][$i] ?? '';
            $q1 = !empty($_POST['q1'][$i]) ? $_POST['q1'][$i] : null;
            $q2 = !empty($_POST['q2'][$i]) ? $_POST['q2'][$i] : null;
            $q3 = !empty($_POST['q3'][$i]) ? $_POST['q3'][$i] : null;
            $q4 = !empty($_POST['q4'][$i]) ? $_POST['q4'][$i] : null;
            
            $entry_stmt->bind_param("issssdddd", $record_id, $major_output, $success_indicators, $budget, $accountable, $accomplishments, $q1, $q2, $q3, $q4);
            $entry_stmt->execute();
        }
        
        // Update the main record's JSON content as well
        $update_query = "UPDATE records SET content = ?, date_submitted = NOW() WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $updated_content_json, $record_id);
        $stmt->execute();

        $_SESSION['success_message'] = "DPCR record updated successfully.";
        header("Location: view_record.php?id=" . $record_id);
        exit();
        
    } else {
        // Fallback for other form types if needed
        $update_query = "UPDATE records SET content = ?, date_submitted = NOW() WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $updated_content_json, $record_id);
        
        if ($stmt->execute()) {
             $_SESSION['success_message'] = "Record updated successfully.";
             header("Location: view_record.php?id=" . $record_id);
             exit();
        } else {
            $message = "Error updating record: " . $conn->error;
            $message_type = "danger";
        }
    }
}

// Check for success message from URL param
if(isset($_GET['update']) && $_GET['update'] == 'success') {
    $message = "Record updated successfully.";
    $message_type = "success";
}

// Set form-specific title
switch ($record['form_type']) {
    case 'DPCR':
        $form_title = "Edit Department Performance Commitment and Review";
        break;
    case 'IPCR':
        $form_title = "Edit Individual Performance Commitment and Review";
        break;
    case 'IDP':
        $form_title = "Edit Individual Development Plan";
        break;
    default:
        $form_title = "Edit Record";
}
?>

<!-- Edit Record Content -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><?php echo $form_title; ?></h1>
        <div>
            <a href="view_record.php?id=<?php echo $record_id; ?>" class="btn btn-outline-secondary btn-sm me-2">
                <i class="bi bi-arrow-left"></i> Back to Record
            </a>
            <a href="records.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-list"></i> All Records
            </a>
        </div>
    </div>
    
    <!-- <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?> -->
    
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Edit Form</h5>
        </div>
        <div class="card-body">
            <form method="POST" id="edit-form">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <p><strong>Form Type:</strong> <?php echo $record['form_type']; ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Employee:</strong> <?php echo $record['user_name']; ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Department:</strong> <?php echo $record['department_name']; ?></p>
                    </div>
                </div>
                
                <?php if ($record['form_type'] === 'DPCR'): ?>
                <!-- DPCR Edit Form -->
                <div id="dpcr-edit-section">
                    <!-- DPCR specific fields will be populated via JavaScript -->
                    <div class="table-responsive">
                        <table class="table table-bordered mb-4">
                            <thead class="table-light">
                                <tr>
                                    <th rowspan="2" class="align-middle text-center">MAJOR FINAL OUTPUT/PAP</th>
                                    <th rowspan="2" class="align-middle text-center">SUCCESS INDICATORS<br>(Targets + Measures)</th>
                                    <th rowspan="2" class="align-middle text-center">ALLOTED BUDGET</th>
                                    <th rowspan="2" class="align-middle text-center">DIVISION/INDIVIDUALS ACCOUNTABLE</th>
                                    <th rowspan="2" class="align-middle text-center">ACTUAL ACCOMPLISHMENTS</th>
                                    <th colspan="4" class="text-center">RATINGS</th>
                                    <th rowspan="2" class="align-middle text-center">Actions</th>
                                </tr>
                                <tr>
                                    <th class="text-center">Q1</th>
                                    <th class="text-center">Q2</th>
                                    <th class="text-center">Q3</th>
                                    <th class="text-center">Q4</th>
                                </tr>
                            </thead>
                            <tbody id="dpcr-table-body">
                                <?php
                                // Get existing DPCR entries
                                $dpcr_entries_query = "SELECT * FROM dpcr_entries WHERE record_id = ? ORDER BY id";
                                $stmt = $conn->prepare($dpcr_entries_query);
                                $stmt->bind_param("i", $record_id);
                                $stmt->execute();
                                $dpcr_entries = $stmt->get_result();
                                
                                if ($dpcr_entries->num_rows > 0) {
                                    while ($entry = $dpcr_entries->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td><input type="text" name="major_output[]" class="form-control form-control-sm" value="' . htmlspecialchars($entry['major_output']) . '"></td>';
                                        echo '<td><input type="text" name="success_indicators[]" class="form-control form-control-sm" value="' . htmlspecialchars($entry['success_indicators']) . '"></td>';
                                        echo '<td><input type="text" name="budget[]" class="form-control form-control-sm" value="' . htmlspecialchars($entry['budget']) . '"></td>';
                                        echo '<td><input type="text" name="accountable[]" class="form-control form-control-sm" value="' . htmlspecialchars($entry['accountable']) . '"></td>';
                                        echo '<td><input type="text" name="accomplishments[]" class="form-control form-control-sm" value="' . htmlspecialchars($entry['accomplishments']) . '"></td>';
                                        echo '<td><input type="number" name="q1[]" class="form-control form-control-sm" min="1" max="5" value="' . ($entry['q1_rating'] > 0 ? $entry['q1_rating'] : '') . '"></td>';
                                        echo '<td><input type="number" name="q2[]" class="form-control form-control-sm" min="1" max="5" value="' . ($entry['q2_rating'] > 0 ? $entry['q2_rating'] : '') . '"></td>';
                                        echo '<td><input type="number" name="q3[]" class="form-control form-control-sm" min="1" max="5" value="' . ($entry['q3_rating'] > 0 ? $entry['q3_rating'] : '') . '"></td>';
                                        echo '<td><input type="number" name="q4[]" class="form-control form-control-sm" min="1" max="5" value="' . ($entry['q4_rating'] > 0 ? $entry['q4_rating'] : '') . '"></td>';
                                        echo '<td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="bi bi-trash"></i></button></td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    // If no entries, add a blank row
                                    echo '<tr>';
                                    echo '<td><input type="text" name="major_output[]" class="form-control form-control-sm"></td>';
                                    echo '<td><input type="text" name="success_indicators[]" class="form-control form-control-sm"></td>';
                                    echo '<td><input type="text" name="budget[]" class="form-control form-control-sm"></td>';
                                    echo '<td><input type="text" name="accountable[]" class="form-control form-control-sm"></td>';
                                    echo '<td><input type="text" name="accomplishments[]" class="form-control form-control-sm"></td>';
                                    echo '<td><input type="number" name="q1[]" class="form-control form-control-sm" min="1" max="5"></td>';
                                    echo '<td><input type="number" name="q2[]" class="form-control form-control-sm" min="1" max="5"></td>';
                                    echo '<td><input type="number" name="q3[]" class="form-control form-control-sm" min="1" max="5"></td>';
                                    echo '<td><input type="number" name="q4[]" class="form-control form-control-sm" min="1" max="5"></td>';
                                    echo '<td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="bi bi-trash"></i></button></td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mb-3">
                        <button type="button" id="add-dpcr-row" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add Row
                        </button>
                    </div>
                </div>
                
                <?php elseif ($record['form_type'] === 'IPCR' && $content): ?>
                
                <?php
                // =================================================================================
                // NEW IPCR EDITING LOGIC
                // Determine which fields should be disabled based on role and status
                // =================================================================================
                $is_dh = ($user_role == 'department_head');
                $is_employee = ($user_role == 'regular_employee');
                $status = $record['document_status'];

                // DH can edit MFOs if form is just distributed or was rejected back to them for template correction
                $dh_can_edit_template = $is_dh && ($status == 'Distributed' || $status == 'Rejected');

                // Employee can edit their accomplishments and ratings if it was rejected
                $employee_can_edit_submission = $is_employee && $status == 'Rejected';
                
                // DH can add their ratings and comments when it's submitted for their review
                $dh_can_review = $is_dh && $status == 'For Review';

                // Field state booleans
                $template_disabled = !$dh_can_edit_template;
                $submission_disabled = !($employee_can_edit_submission || ($is_dh && $status == 'For Review')); // DH can see but not edit
                $review_disabled = !$dh_can_review;
                
                // For Rejected status, employee submission fields are open
                if ($status == 'Rejected' && $is_employee) {
                    $template_disabled = true;
                    $submission_disabled = false;
                    $review_disabled = true;
                }
                
                // Final check: Admin/President can edit everything
                if ($user_role == 'admin' || $user_role == 'president') {
                    $template_disabled = false;
                    $submission_disabled = false;
                    $review_disabled = false;
                }
                ?>
                <div id="ipcr-edit-section">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-4">
                            <thead class="table-light">
                                <tr>
                                    <th rowspan="2" class="align-middle text-center" style="width: 16%">MAJOR FINAL OUTPUT (MFO)</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 16%">SUCCESS INDICATORS</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 12%">ACTUAL ACCOMPLISHMENTS</th>
                                    <th colspan="4" class="text-center">SELF-RATING</th>
                                    <th colspan="4" class="text-center">SUPERVISOR'S RATING</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 10%">REMARKS / COMMENTS</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 2%"></th>
                                </tr>
                                <tr>
                                    <th class="text-center">Q</th><th class="text-center">E</th><th class="text-center">T</th><th class="text-center">A</th>
                                    <th class="text-center">Q</th><th class="text-center">E</th><th class="text-center">T</th><th class="text-center">A</th>
                                </tr>
                            </thead>
                            <tbody id="ipcr-table-body">
                                <!-- JS will build the table body from JSON content -->
                            </tbody>
                        </table>
                    </div>

                    <?php if($dh_can_review): ?>
                    <div class="mb-3">
                        <label for="dh_comments" class="form-label">Comments & Recommendations for Development</label>
                        <textarea class="form-control" id="dh_comments" name="dh_comments" rows="3"><?php echo htmlspecialchars($content['dh_comments'] ?? ''); ?></textarea>
                    </div>
                    <?php endif; ?>
                </div>

                <?php else: ?>
                <div class="alert alert-warning">
                    This record type (<?php echo htmlspecialchars($record['form_type']); ?>) cannot be edited in this interface or its content is missing.
                </div>
                <?php endif; ?>
                
                <!-- Hidden field for JSON content -->
                <input type="hidden" name="content" id="form-content-json">
                
                <div class="d-flex justify-content-end mt-4">
                    <a href="view_record.php?id=<?php echo $record_id; ?>" class="btn btn-secondary me-2">Cancel</a>
                    
                    <?php if ($record['form_type'] === 'IPCR'): ?>
                        <?php if ($dh_can_review): ?>
                            <button type="submit" name="reject_ipcr" class="btn btn-danger me-2" onclick="return confirm('Are you sure you want to reject this submission?');">
                                <i class="bi bi-x-circle"></i> Reject
                            </button>
                            <button type="submit" name="approve_ipcr" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Approve
                            </button>
                        <?php elseif ($employee_can_edit_submission): ?>
                             <button type="submit" name="resubmit_ipcr" class="btn btn-primary">
                                <i class="bi bi-send"></i> Resubmit for Review
                            </button>
                        <?php elseif ($dh_can_edit_template): ?>
                            <button type="submit" name="update_template" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Template
                            </button>
                        <?php else: // Fallback for admin/president or other edge cases ?>
                             <button type="submit" name="update_record" class="btn btn-primary">Update Record</button>
                        <?php endif; ?>
                    <?php else: // For DPCR and other forms ?>
                        <button type="submit" name="update_record" class="btn btn-primary">Update Record</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="js/auto_scoring.js"></script>

<script>
$(document).ready(function() {
    
    // =================================================================================
    // DPCR FORM LOGIC (Original)
    // =================================================================================
    const addDpcrRowBtn = document.getElementById('add-dpcr-row');
    if (addDpcrRowBtn) {
        addDpcrRowBtn.addEventListener('click', function() {
            const tbody = document.getElementById('dpcr-table-body');
            const newRow = document.createElement('tr');
            
            newRow.innerHTML = `
                <td><input type="text" name="major_output[]" class="form-control form-control-sm"></td>
                <td><input type="text" name="success_indicators[]" class="form-control form-control-sm"></td>
                <td><input type="text" name="budget[]" class="form-control form-control-sm"></td>
                <td><input type="text" name="accountable[]" class="form-control form-control-sm"></td>
                <td><input type="text" name="accomplishments[]" class="form-control form-control-sm"></td>
                <td><input type="number" name="q1[]" class="form-control form-control-sm" min="1" max="5"></td>
                <td><input type="number" name="q2[]" class="form-control form-control-sm" min="1" max="5"></td>
                <td><input type="number" name="q3[]" class="form-control form-control-sm" min="1" max="5"></td>
                <td><input type="number" name="q4[]" class="form-control form-control-sm" min="1" max="5"></td>
                <td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="bi bi-trash"></i></button></td>
            `;
            
            tbody.appendChild(newRow);
        });
    }
    
    // Universal remove button for both DPCR and IPCR
    $(document).on('click', '.remove-row', function() {
        const tbody = $(this).closest('tbody');
        if (tbody.children('tr').length > 1) {
            $(this).closest('tr').remove();
        } else {
            alert('You cannot remove the last row.');
        }
    });

    // =================================================================================
    // IPCR EDITING LOGIC (NEW)
    // =================================================================================
    const isIpcr = <?php echo json_encode($record['form_type'] === 'IPCR'); ?>;
    
    if (isIpcr) {
        const content = <?php echo json_encode($content); ?>;
        const template_disabled = <?php echo json_encode($template_disabled ?? true); ?>;
        const submission_disabled = <?php echo json_encode($submission_disabled ?? true); ?>;
        const review_disabled = <?php echo json_encode($review_disabled ?? true); ?>;

        const renderIpcrTable = () => {
            const tableBody = $('#ipcr-table-body');
            tableBody.empty();

            const buildSection = (title, category, entries) => {
                if (!entries || entries.length === 0) return;

                tableBody.append(`<tr><td colspan="12" class="text-start bg-light fw-bold">${title}</td></tr>`);
                
                entries.forEach(entry => {
                    const row = `
                        <tr class="function-row ${category}-function-row">
                            <td><textarea class="form-control form-control-sm" name="${category}_mfo[]" ${template_disabled ? 'readonly' : ''}>${entry.mfo || ''}</textarea></td>
                            <td><textarea class="form-control form-control-sm" name="${category}_success_indicators[]" ${template_disabled ? 'readonly' : ''}>${entry.success_indicators || ''}</textarea></td>
                            <td><textarea class="form-control form-control-sm" name="${category}_accomplishments[]" ${submission_disabled ? 'readonly' : ''}>${entry.accomplishments || ''}</textarea></td>
                            <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="${category}_q[]" min="1" max="5" value="${entry.q || ''}" ${submission_disabled ? 'readonly' : ''}></td>
                            <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="${category}_e[]" min="1" max="5" value="${entry.e || ''}" ${submission_disabled ? 'readonly' : ''}></td>
                            <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="${category}_t[]" min="1" max="5" value="${entry.t || ''}" ${submission_disabled ? 'readonly' : ''}></td>
                            <td><input type="text" class="form-control form-control-sm average-rating" name="${category}_a[]" value="${entry.a || ''}" readonly></td>
                            <td><input type="number" class="form-control form-control-sm rating-input supervisor-rating" name="${category}_supervisor_q[]" min="1" max="5" value="${entry.supervisor_q || ''}" ${review_disabled ? 'readonly' : ''}></td>
                            <td><input type="number" class="form-control form-control-sm rating-input supervisor-rating" name="${category}_supervisor_e[]" min="1" max="5" value="${entry.supervisor_e || ''}" ${review_disabled ? 'readonly' : ''}></td>
                            <td><input type="number" class="form-control form-control-sm rating-input supervisor-rating" name="${category}_supervisor_t[]" min="1" max="5" value="${entry.supervisor_t || ''}" ${review_disabled ? 'readonly' : ''}></td>
                            <td><input type="text" class="form-control form-control-sm supervisor-average-rating" name="${category}_supervisor_a[]" value="${entry.supervisor_a || ''}" readonly></td>
                            <td><textarea class="form-control form-control-sm" name="${category}_remarks[]" ${template_disabled && review_disabled ? 'readonly' : ''}>${entry.remarks || ''}</textarea></td>
                            <td></td> <!-- Placeholder for remove button if needed -->
                        </tr>
                    `;
                    tableBody.append(row);
                });
            };

            buildSection('I. STRATEGIC FUNCTIONS', 'strategic', content.strategic_functions);
            buildSection('II. CORE FUNCTIONS', 'core', content.core_functions);
            if (content.computation_type === 'Type2') {
                 buildSection('III. SUPPORT FUNCTIONS', 'support', content.support_functions);
            }
        };

        const calculateRowAverage = (row) => {
            const calculate = (prefix) => {
                const q = parseFloat($(row).find(`input[name$="${prefix}_q[]"]`).val()) || 0;
                const e = parseFloat($(row).find(`input[name$="${prefix}_e[]"]`).val()) || 0;
                const t = parseFloat($(row).find(`input[name$="${prefix}_t[]"]`).val()) || 0;
                let count = (q > 0 ? 1:0) + (e > 0 ? 1:0) + (t > 0 ? 1:0);
                const avg = count > 0 ? ((q + e + t) / count).toFixed(2) : '';
                const target_a = (prefix === '') ? 'a' : `supervisor_a`;
                $(row).find(`input[name$="${target_a}[]"]`).val(avg);
            };
            calculate(''); // Self-rating
            calculate('supervisor'); // Supervisor rating
        };

        $('#ipcr-table-body').on('input', '.rating-input', function() {
            calculateRowAverage($(this).closest('tr'));
        });
        
        renderIpcrTable();
    }
    
    // =================================================================================
    // FORM SUBMISSION HANDLING (BOTH DPCR and IPCR)
    // =================================================================================
    $('#edit-form').on('submit', function(e) {
        // --- DPCR Serialization ---
        if ('<?php echo $record['form_type']; ?>' === 'DPCR') {
            const dpcrData = { entries: [] };
            $('#dpcr-table-body tr').each(function() {
                const inputs = $(this).find('input');
                dpcrData.entries.push({
                    major_output: inputs.eq(0).val(),
                    success_indicators: inputs.eq(1).val(),
                    budget: inputs.eq(2).val(),
                    accountable: inputs.eq(3).val(),
                    accomplishments: inputs.eq(4).val(),
                    q1: inputs.eq(5).val(), q2: inputs.eq(6).val(),
                    q3: inputs.eq(7).val(), q4: inputs.eq(8).val()
                });
            });
            $('#form-content-json').val(JSON.stringify(dpcrData));
        }

        // --- IPCR Serialization ---
        if (isIpcr) {
            let updatedContent = JSON.parse(JSON.stringify(<?php echo json_encode($content); ?>));
            
            const updateSection = (category) => {
                if (!updatedContent[`${category}_functions`]) return;
                
                $(`tr.${category}-function-row`).each(function(index) {
                    if(updatedContent[`${category}_functions`][index]) {
                        const entry = updatedContent[`${category}_functions`][index];
                        entry.mfo = $(this).find(`textarea[name="${category}_mfo[]"]`).val();
                        entry.success_indicators = $(this).find(`textarea[name="${category}_success_indicators[]"]`).val();
                        entry.accomplishments = $(this).find(`textarea[name="${category}_accomplishments[]"]`).val();
                        entry.q = $(this).find(`input[name="${category}_q[]"]`).val();
                        entry.e = $(this).find(`input[name="${category}_e[]"]`).val();
                        entry.t = $(this).find(`input[name="${category}_t[]"]`).val();
                        entry.a = $(this).find(`input[name="${category}_a[]"]`).val();
                        entry.supervisor_q = $(this).find(`input[name="${category}_supervisor_q[]"]`).val();
                        entry.supervisor_e = $(this).find(`input[name="${category}_supervisor_e[]"]`).val();
                        entry.supervisor_t = $(this).find(`input[name="${category}_supervisor_t[]"]`).val();
                        entry.supervisor_a = $(this).find(`input[name="${category}_supervisor_a[]"]`).val();
                        entry.remarks = $(this).find(`textarea[name="${category}_remarks[]"]`).val();
                    }
                });
            };
            
            updateSection('strategic');
            updateSection('core');
            if (updatedContent.computation_type === 'Type2') {
                updateSection('support');
            }
            
            if ($('#dh_comments').length > 0) {
                 updatedContent.dh_comments = $('#dh_comments').val();
            }

            $('#form-content-json').val(JSON.stringify(updatedContent));
        }
    });
});
</script>

<?php
// Include footer
include_once('includes/footer.php');
?> 