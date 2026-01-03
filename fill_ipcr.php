<?php
// Set page title
$page_title = "Fill IPCR Self-Rating - EPMS";

// Include header
include_once('includes/header.php');

// Include form workflow functions
include_once('includes/form_workflow.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if record ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirect to history if no ID is provided
    header("Location: ipcr.php#history");
    exit();
}

$record_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_department_id = $_SESSION['user_department_id'];
$is_employee = ($user_role === 'regular_employee');
$is_dept_head = ($user_role === 'department_head'); // DH might need to access for final review/editing

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "epms_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Fetch Record Data ---
$record_query = "SELECT r.*, u.name as employee_name, d.name as department_name
                 FROM records r
                 JOIN users u ON r.user_id = u.id
                 -- Assuming 'department_id' is only in the 'users' table, not 'records'.
                 JOIN departments d ON u.department_id = d.id 
                 WHERE r.id = ? AND r.form_type = 'IPCR'";
                 
$stmt = $conn->prepare($record_query);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$record_result = $stmt->get_result();

if ($record_result->num_rows === 0) {
    echo "<div class='alert alert-danger'>Error: IPCR record not found.</div>";
    include_once('includes/footer.php');
    exit();
}

$record = $record_result->fetch_assoc();
$ipcr_data = json_decode($record['content'], true);
$stmt->close();

// Authorization check: Only the assigned employee or DH can edit
if ($record['user_id'] != $user_id && !$is_dept_head) {
    echo "<div class='alert alert-danger'>Access Denied: You are not authorized to fill this IPCR.</div>";
    include_once('includes/footer.php');
    exit();
}

// State check: Employee can only fill out if status is 'Plan Submitted' or 'Draft'
$is_editable = false;
if ($is_employee && ($record['status'] === 'Plan Submitted' || $record['status'] === 'Draft')) {
    $is_editable = true;
} else if ($is_dept_head && ($record['status'] === 'Pending' || $record['status'] === 'Rejected')) {
    // DH can access to view or edit supervisor ratings
    $is_editable = true; // For DH, this means supervisor fields are editable
}

// Extract data from JSON
$computation_type = $ipcr_data['computation_type'] ?? 'Type1';
$strategic_entries = $ipcr_data['strategic_functions'] ?? [];
$core_entries = $ipcr_data['core_functions'] ?? [];
$support_entries = $ipcr_data['support_functions'] ?? [];
$dh_comments = $ipcr_data['dh_comments'] ?? '';

// Determine initial weights for display
$strategic_weight_display = ($computation_type === 'Type2') ? '(45%)' : '(45%)';
$core_weight_display = ($computation_type === 'Type2') ? '(45%)' : '(55%)';
$support_display_style = ($computation_type === 'Type2') ? 'table-row' : 'none';

// --- Handle Form Submission (Employee Action) ---
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_editable) {
    
    // The employee's submission only updates their specific fields
    // Extract new EE data from POST and merge into $ipcr_data
    
    $new_content = $ipcr_data;
    $new_content['dh_comments'] = $_POST['dh_comments'] ?? $dh_comments; // DH comments should be preserved
    
    // Helper function to update category data
    function updateCategoryData($current_data, $post_data, $prefix) {
        $updated_data = [];
        foreach ($current_data as $i => $entry) {
            // Only update fields that the Employee is responsible for: accomplishments and self-ratings
            $entry['accomplishments'] = $post_data["{$prefix}_accomplishments"][$i] ?? $entry['accomplishments'];
            $entry['q'] = $post_data["{$prefix}_q"][$i] ?? $entry['q'];
            $entry['e'] = $post_data["{$prefix}_e"][$i] ?? $entry['e'];
            $entry['t'] = $post_data["{$prefix}_t"][$i] ?? $entry['t'];
            $entry['a'] = $post_data["{$prefix}_a"][$i] ?? $entry['a']; // Calculated average
            
            // Only DH can update their fields, but we must save them back (even if untouched in this form)
            if (isset($post_data["{$prefix}_supervisor_q"])) {
                $entry['supervisor_q'] = $post_data["{$prefix}_supervisor_q"][$i] ?? $entry['supervisor_q'];
                $entry['supervisor_e'] = $post_data["{$prefix}_supervisor_e"][$i] ?? $entry['supervisor_e'];
                $entry['supervisor_t'] = $post_data["{$prefix}_supervisor_t"][$i] ?? $entry['supervisor_t'];
                $entry['supervisor_a'] = $post_data["{$prefix}_supervisor_a"][$i] ?? $entry['supervisor_a'];
                $entry['remarks'] = $post_data["{$prefix}_remarks"][$i] ?? $entry['remarks'];
            }
            
            $updated_data[] = $entry;
        }
        return $updated_data;
    }

    $new_content['strategic_functions'] = updateCategoryData($strategic_entries, $_POST, 'strategic');
    $new_content['core_functions'] = updateCategoryData($core_entries, $_POST, 'core');
    $new_content['support_functions'] = updateCategoryData($support_entries, $_POST, 'support');
    
    $final_rating = $_POST['final_rating'] ?? null;

    // Determine Status
    if (isset($_POST['save_draft'])) {
        $status = 'Draft';
        $message = "IPCR Self-Rating saved as draft successfully.";
    } elseif (isset($_POST['submit_review'])) {
        $status = 'Pending'; // Pending review by DH
        $message = "IPCR Self-Rating successfully submitted for Department Head review.";
    } else {
         $status = $record['status']; // Should not happen
    }
    
    $json_content = json_encode($new_content);

    // Update existing record
    $update_query = "UPDATE records SET content = ?, status = ?, final_rating = ?, date_updated = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssi", $json_content, $status, $final_rating, $record_id);
    
    if ($stmt->execute()) {
        $success_message = $message;
        // Reload the record data to reflect changes
        $record['status'] = $status;
        $ipcr_data = $new_content;
    } else {
        $error_message = "Error updating record: " . $conn->error;
    }
    $stmt->close();
}
?>

<div class="container-fluid py-4">
    <style>
        /* Shared CSS */
        #ipcr-table-body td input.form-control,
        #ipcr-table-body td textarea.form-control {
            background-color: #ffffff;
            border: 1px solid #c9c9c9; 
            padding: 0.25rem 0.5rem;
            height: 100%;
            box-sizing: border-box; 
            box-shadow: none; 
        }

        #ipcr-table-body tr input.rating-input {
            text-align: center;
            border-radius: 0.15rem; 
            font-weight: 500;
        }

        /* Readonly styles for plan-related fields */
        #ipcr-table-body tr input[readonly], 
        #ipcr-table-body tr textarea[readonly] { 
            background-color: #e9ecef !important; /* Lighter gray for readonly */
            cursor: default;
            color: #495057;
            border-color: #ced4da !important;
        }

        /* Highlight editable fields for the Employee */
        .editable-ee-field {
            background-color: #fff3cd !important; /* Light yellow background */
            border-color: #ffc107 !important;
        }
        
        #ipcr-table-body tr td {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            vertical-align: top;
        }
    </style>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Individual Performance Commitment and Review</h1>
        <div>
            <span class="badge bg-primary me-2">Employee Fill-Out</span>
            <span class="badge bg-secondary"><?php echo htmlspecialchars($record['status']); ?></span>
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
    
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">IPCR for: <?php echo htmlspecialchars($record['employee_name']); ?> (<?php echo htmlspecialchars($record['department_name']); ?>)</h5>
            <p class="mb-0 text-muted small">Period: <?php echo htmlspecialchars($record['period']); ?></p>
        </div>
        <div class="card-body">
            
            <form action="fill_ipcr.php?id=<?php echo $record_id; ?>" method="POST" id="ipcr-fill-form">
                <input type="hidden" name="computation_type" id="computation_type" value="<?php echo htmlspecialchars($computation_type); ?>">

                <h5 class="text-center fw-bold my-4">INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR) FORM</h5>
                
                <div class="mb-4">
                    <p>I, <u><?php echo htmlspecialchars($record['employee_name']); ?></u>, of <u><?php echo htmlspecialchars($record['department_name']); ?></u>, 
                    commit to deliver and agree to be rated on the attainment of the following targets in accordance with the indicated measures for the period <u><?php echo htmlspecialchars($record['period']); ?></u>.</p>
                    <p class="text-danger fw-bold">Instruction: Fill in the "Actual Accomplishments" and "Self-Rating (Q, E, T)" columns only.</p>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered mb-4">
                        <thead class="table-light">
                            <tr>
                                <th rowspan="2" class="align-middle text-center" style="width: 16%">MAJOR FINAL OUTPUT (MFO)</th>
                                <th rowspan="2" class="align-middle text-center" style="width: 16%">SUCCESS INDICATORS (Targets + Measures)</th>
                                <th rowspan="2" class="align-middle text-center" style="width: 12%">ACTUAL ACCOMPLISHMENTS</th>
                                
                                <th colspan="4" class="text-center" style="width: 18%">SELF-RATING</th>
                                <th colspan="4" class="text-center" style="width: 18%">SUPERVISOR'S RATING</th>
                                
                                <th rowspan="2" class="align-middle text-center" style="width: 10%">REMARKS</th>
                            </tr>
                            <tr>
                                <th class="text-center" style="width: 4.5%">Q</th>
                                <th class="text-center" style="width: 4.5%">E</th>
                                <th class="text-center" style="width: 4.5%">T</th>
                                <th class="text-center" style="width: 4.5%">A</th>
                                <th class="text-center" style="width: 4.5%">Q</th>
                                <th class="text-center" style="width: 4.5%">E</th>
                                <th class="text-center" style="width: 4.5%">T</th>
                                <th class="text-center" style="width: 4.5%">A</th>
                            </tr>
                        </thead>
                        <tbody id="ipcr-table-body">
                            
                            <tr>
                                <td colspan="11" class="text-start bg-light fw-bold">
                                    I. STRATEGIC FUNCTIONS <span id="strategic_weight" class="float-end"><?php echo $strategic_weight_display; ?></span>
                                </td>
                            </tr>
                            <?php foreach($strategic_entries as $i => $entry): ?>
                            <tr class="function-row strategic-function-row" data-category="strategic">
                                <td><textarea class="form-control form-control-sm" name="strategic_mfo[]" readonly><?php echo htmlspecialchars($entry['mfo'] ?? ''); ?></textarea></td>
                                <td><textarea class="form-control form-control-sm" name="strategic_success_indicators[]" readonly><?php echo htmlspecialchars($entry['success_indicators'] ?? ''); ?></textarea></td>
                                
                                <td><textarea class="form-control form-control-sm editable-ee-field" name="strategic_accomplishments[]" <?php echo $is_editable ? '' : 'readonly'; ?>><?php echo htmlspecialchars($entry['accomplishments'] ?? ''); ?></textarea></td>
                                
                                <td><input type="number" class="form-control form-control-sm rating-input self-rating editable-ee-field" name="strategic_q[]" min="1" max="5" step="1" data-type="strategic" value="<?php echo htmlspecialchars($entry['q'] ?? ''); ?>" <?php echo $is_editable ? '' : 'readonly'; ?>></td>
                                <td><input type="number" class="form-control form-control-sm rating-input self-rating editable-ee-field" name="strategic_e[]" min="1" max="5" step="1" data-type="strategic" value="<?php echo htmlspecialchars($entry['e'] ?? ''); ?>" <?php echo $is_editable ? '' : 'readonly'; ?>></td>
                                <td><input type="number" class="form-control form-control-sm rating-input self-rating editable-ee-field" name="strategic_t[]" min="1" max="5" step="1" data-type="strategic" value="<?php echo htmlspecialchars($entry['t'] ?? ''); ?>" <?php echo $is_editable ? '' : 'readonly'; ?>></td>
                                <td><input type="text" class="form-control form-control-sm average-rating strategic_a" name="strategic_a[]" value="<?php echo htmlspecialchars($entry['a'] ?? ''); ?>" readonly></td>
                                
                                <td><input type="number" class="form-control form-control-sm supervisor-rating" name="strategic_supervisor_q[]" readonly value="<?php echo htmlspecialchars($entry['supervisor_q'] ?? ''); ?>"></td>
                                <td><input type="number" class="form-control form-control-sm supervisor-rating" name="strategic_supervisor_e[]" readonly value="<?php echo htmlspecialchars($entry['supervisor_e'] ?? ''); ?>"></td>
                                <td><input type="number" class="form-control form-control-sm supervisor-rating" name="strategic_supervisor_t[]" readonly value="<?php echo htmlspecialchars($entry['supervisor_t'] ?? ''); ?>"></td>
                                <td><input type="text" class="form-control form-control-sm supervisor-average-rating" name="strategic_supervisor_a[]" readonly value="<?php echo htmlspecialchars($entry['supervisor_a'] ?? ''); ?>"></td>
                                <td><textarea type="text" class="form-control form-control-sm" name="strategic_remarks[]" readonly value="<?php echo htmlspecialchars($entry['remarks'] ?? ''); ?>"></textarea></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <tr>
                                <td colspan="11" class="text-start bg-light fw-bold">
                                    II. CORE FUNCTIONS <span id="core_weight" class="float-end"><?php echo $core_weight_display; ?></span>
                                </td>
                            </tr>
                            <?php foreach($core_entries as $i => $entry): ?>
                            <tr class="function-row core-function-row" data-category="core">
                                <td><textarea class="form-control form-control-sm" name="core_mfo[]" readonly><?php echo htmlspecialchars($entry['mfo'] ?? ''); ?></textarea></td>
                                <td><textarea class="form-control form-control-sm" name="core_success_indicators[]" readonly><?php echo htmlspecialchars($entry['success_indicators'] ?? ''); ?></textarea></td>
                                
                                <td><textarea class="form-control form-control-sm editable-ee-field" name="core_accomplishments[]" <?php echo $is_editable ? '' : 'readonly'; ?>><?php echo htmlspecialchars($entry['accomplishments'] ?? ''); ?></textarea></td>
                                
                                <td><input type="number" class="form-control form-control-sm rating-input self-rating editable-ee-field" name="core_q[]" min="1" max="5" step="1" data-type="core" value="<?php echo htmlspecialchars($entry['q'] ?? ''); ?>" <?php echo $is_editable ? '' : 'readonly'; ?>></td>
                                <td><input type="number" class="form-control form-control-sm rating-input self-rating editable-ee-field" name="core_e[]" min="1" max="5" step="1" data-type="core" value="<?php echo htmlspecialchars($entry['e'] ?? ''); ?>" <?php echo $is_editable ? '' : 'readonly'; ?>></td>
                                <td><input type="number" class="form-control form-control-sm rating-input self-rating editable-ee-field" name="core_t[]" min="1" max="5" step="1" data-type="core" value="<?php echo htmlspecialchars($entry['t'] ?? ''); ?>" <?php echo $is_editable ? '' : 'readonly'; ?>></td>
                                <td><input type="text" class="form-control form-control-sm average-rating core_a" name="core_a[]" value="<?php echo htmlspecialchars($entry['a'] ?? ''); ?>" readonly></td>
                                
                                <td><input type="number" class="form-control form-control-sm supervisor-rating" name="core_supervisor_q[]" readonly value="<?php echo htmlspecialchars($entry['supervisor_q'] ?? ''); ?>"></td>
                                <td><input type="number" class="form-control form-control-sm supervisor-rating" name="core_supervisor_e[]" readonly value="<?php echo htmlspecialchars($entry['supervisor_e'] ?? ''); ?>"></td>
                                <td><input type="number" class="form-control form-control-sm supervisor-rating" name="core_supervisor_t[]" readonly value="<?php echo htmlspecialchars($entry['supervisor_t'] ?? ''); ?>"></td>
                                <td><input type="text" class="form-control form-control-sm supervisor-average-rating" name="core_supervisor_a[]" readonly value="<?php echo htmlspecialchars($entry['supervisor_a'] ?? ''); ?>"></td>
                                <td><textarea type="text" class="form-control form-control-sm" name="core_remarks[]" readonly value="<?php echo htmlspecialchars($entry['remarks'] ?? ''); ?>"></textarea></td>
                            </tr>
                            <?php endforeach; ?>

                            <tr class="support-functions-header" style="display: <?php echo $support_display_style; ?>;">
                                <td colspan="11" class="text-start bg-light fw-bold">
                                    III. SUPPORT FUNCTIONS <span id="support_weight" class="float-end">(10%)</span>
                                </td>
                            </tr>
                            <?php foreach($support_entries as $i => $entry): ?>
                            <tr class="function-row support-function-row" data-category="support" style="display: <?php echo $support_display_style; ?>;">
                                <td><textarea class="form-control form-control-sm" name="support_mfo[]" readonly><?php echo htmlspecialchars($entry['mfo'] ?? ''); ?></textarea></td>
                                <td><textarea class="form-control form-control-sm" name="support_success_indicators[]" readonly><?php echo htmlspecialchars($entry['success_indicators'] ?? ''); ?></textarea></td>
                                
                                <td><textarea class="form-control form-control-sm editable-ee-field" name="support_accomplishments[]" <?php echo $is_editable ? '' : 'readonly'; ?>><?php echo htmlspecialchars($entry['accomplishments'] ?? ''); ?></textarea></td>
                                
                                <td><input type="number" class="form-control form-control-sm rating-input self-rating editable-ee-field" name="support_q[]" min="1" max="5" step="1" data-type="support" value="<?php echo htmlspecialchars($entry['q'] ?? ''); ?>" <?php echo $is_editable ? '' : 'readonly'; ?>></td>
                                <td><input type="number" class="form-control form-control-sm rating-input self-rating editable-ee-field" name="support_e[]" min="1" max="5" step="1" data-type="support" value="<?php echo htmlspecialchars($entry['e'] ?? ''); ?>" <?php echo $is_editable ? '' : 'readonly'; ?>></td>
                                <td><input type="number" class="form-control form-control-sm rating-input self-rating editable-ee-field" name="support_t[]" min="1" max="5" step="1" data-type="support" value="<?php echo htmlspecialchars($entry['t'] ?? ''); ?>" <?php echo $is_editable ? '' : 'readonly'; ?>></td>
                                <td><input type="text" class="form-control form-control-sm average-rating support_a" name="support_a[]" value="<?php echo htmlspecialchars($entry['a'] ?? ''); ?>" readonly></td>
                                
                                <td><input type="number" class="form-control form-control-sm supervisor-rating" name="support_supervisor_q[]" readonly value="<?php echo htmlspecialchars($entry['supervisor_q'] ?? ''); ?>"></td>
                                <td><input type="number" class="form-control form-control-sm supervisor-rating" name="support_supervisor_e[]" readonly value="<?php echo htmlspecialchars($entry['supervisor_e'] ?? ''); ?>"></td>
                                <td><input type="number" class="form-control form-control-sm supervisor-rating" name="support_supervisor_t[]" readonly value="<?php echo htmlspecialchars($entry['supervisor_t'] ?? ''); ?>"></td>
                                <td><input type="text" class="form-control form-control-sm supervisor-average-rating" name="support_supervisor_a[]" readonly value="<?php echo htmlspecialchars($entry['supervisor_a'] ?? ''); ?>"></td>
                                <td><textarea type="text" class="form-control form-control-sm" name="support_remarks[]" readonly value="<?php echo htmlspecialchars($entry['remarks'] ?? ''); ?>"></textarea></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <tr>
                                <td colspan="7" class="text-end fw-bold align-middle">
                                    Summary of Self-Rating Average (Q/E/T)
                                </td>
                                <td colspan="4" class="p-2">
                                    <div class="row g-1">
                                        <div class="col-12"><small class="fw-bold">Strategic Average:</small> <input type="text" class="form-control form-control-sm" id="strategic_average" name="strategic_average" readonly></div>
                                        <div class="col-12"><small class="fw-bold">Core Average:</small> <input type="text" class="form-control form-control-sm" id="core_average" name="core_average" readonly></div>
                                        <div class="col-12 support-average-display" style="display: <?php echo $support_display_style; ?>;"><small class="fw-bold">Support Average:</small> <input type="text" class="form-control form-control-sm" id="support_average" name="support_average" readonly></div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="11" class="text-start bg-light fw-bold">
                                    Comments and Recommendations for Development Purposes
                                </td>
                            </tr>
                            <tr>
                                <td colspan="11">
                                    <textarea class="form-control" name="dh_comments" rows="3" readonly><?php echo htmlspecialchars($dh_comments); ?></textarea>
                                </td>
                            </tr>

                            <tr>
                                <td colspan="7" class="text-end fw-bold align-middle">
                                    FINAL RATING
                                </td>
                                <td colspan="4" class="p-2">
                                    <div class="row g-1">
                                        <div class="col-md-6 fw-bold">Final Rating:</div>
                                        <div class="col-md-6">
                                            <input type="text" class="form-control form-control-sm" id="final_rating" name="final_rating" readonly>
                                        </div>
                                    </div>
                                    <div class="row mt-1">
                                        <div class="col-md-12 text-center" id="rating_interpretation"></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($is_editable): ?>
                <div class="d-flex justify-content-between mt-4">
                    <button type="submit" class="btn btn-secondary" name="save_draft">
                        <i class="bi bi-save me-1"></i> Save Draft
                    </button>
                    <button type="submit" class="btn btn-primary" name="submit_review">
                        <i class="bi bi-send me-1"></i> Submit Self-Rating for Review
                    </button>
                </div>
                <?php else: ?>
                    <div class="alert alert-info text-center mt-4">
                        This IPCR is currently in **<?php echo htmlspecialchars($record['status']); ?>** status and cannot be edited by the employee.
                    </div>
                <?php endif; ?>

            </form>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="js/auto_scoring.js"></script>

<script>
    
    // --- JavaScript for dynamic calculation (EE Self-Rating) ---
    
    // Helper function to calculate the average of three values (from auto_scoring.js)
    // We re-declare it here only for clarity/safety, but it's globally available.
    // function calculateAverageRating(quality, efficiency, timeliness) { ... }
    // function updateFinalRating() { ... } // Also from auto_scoring.js

    $(document).ready(function() {
        // --- 1. Attach Listeners for Self-Rating Calculation ---
        // This attaches listeners to the fields the Employee can edit: Self-Rating Q, E, T inputs.
        
        // This specific listener is an adaptation of the one in ipcr.php for the DH-rating,
        // but now for the EE's self-rating.
        $(document).on('input', 'input.self-rating', function() {
            const row = $(this).closest('tr');
            const type = $(this).data('type');
            
            // Note: The self-rating fields use names like `strategic_q[]`
            const q = row.find(`input[name="${type}_q[]"]`).val();
            const e = row.find(`input[name="${type}_e[]"]`).val();
            const t = row.find(`input[name="${type}_t[]"]`).val();
            const averageField = row.find(`input[name="${type}_a[]"]`);
            
            if (q && e && t) {
                // calculateAverageRating is a global function from auto_scoring.js
                const average = calculateAverageRating(q, e, t);
                averageField.val(average);
            } else {
                 averageField.val('');
            }
            
            // Recalculate all category averages and final rating (Self-Rating based)
            // This global function relies on the calculated averages (strategic_a, core_a, etc.)
            updateFinalRating();
        });
        
        // --- 2. Initial Calculation on Load ---
        // Run initial calculation to populate the summary fields if data exists
        updateFinalRating();
        
        // --- 3. Disable DH fields for non-DH user/state ---
        <?php if (!$is_editable): ?>
            // Disable the form completely if not editable
            $('#ipcr-fill-form :input').prop('disabled', true);
            // Re-enable hidden fields if necessary (like computation_type)
            $('#computation_type').prop('disabled', false); 
        <?php endif; ?>
    });
</script>

<?php
// Close database connection
$conn->close();

// Include footer
include_once('includes/footer.php');
?>