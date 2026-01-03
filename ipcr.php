<?php
// Set page title
$page_title = "Individual Performance Commitment and Review - EPMS";

// Include header
include_once('includes/header.php');

// Include form workflow functions
include_once('includes/form_workflow.php');

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

// Get department name
$dept_query = "SELECT name FROM departments WHERE id = ?";
$stmt = $conn->prepare($dept_query);
$stmt->bind_param("i", $user_department_id);
$stmt->execute();
$dept_result = $stmt->get_result();
$department_name = ($dept_result->num_rows > 0) ? $dept_result->fetch_assoc()['name'] : 'Unknown Department';

// Get existing IPCR records for current user
$records_query = "SELECT * FROM records WHERE user_id = ? AND form_type = 'IPCR' ORDER BY date_submitted DESC";
$stmt = $conn->prepare($records_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$records_result = $stmt->get_result();

function getComputationTypes() {
    return [
        'Type1' => 'Strategic (45%) and Core (55%)',
        'Type2' => 'Strategic (45%), Core (45%), and Support (10%)'
    ];
}

// --- NEW/MODIFIED: Initial setup for dynamic IPCR data ---
$ipcr_data = []; // Will hold loaded data if editing a draft (not fully implemented in the current file but necessary for logic)
$computation_type = 'Type1'; // Default type
$strategic_entries = [['mfo' => '', 'success_indicators' => '', 'accomplishments' => '']]; // Default one row for Strategic
$core_entries = [['mfo' => '', 'success_indicators' => '', 'accomplishments' => '']]; // Default one row for Core
$support_entries = [];

// Determine initial weights for display
$strategic_weight_display = '(45%)';
$core_weight_display = '(55%)';
$support_display_style = 'none';

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process form data
    $period = $_POST['period'];
    $content = $_POST['content'] ?? ''; // Form content as JSON
    
    // Check if it's a save draft or submit operation
    if (isset($_POST['save_draft'])) {
        // Insert new IPCR record as Draft
        $insert_query = "INSERT INTO records (user_id, form_type, period, content, status) VALUES (?, 'IPCR', ?, ?, 'Draft')";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iss", $user_id, $period, $content);
        
        if ($stmt->execute()) {
            $success_message = "IPCR form saved as draft successfully.";
        } else {
            $error_message = "Error saving draft: " . $conn->error;
        }
    } else if (isset($_POST['submit_ipcr'])) {
        // Submit form for review using the workflow function
        $result = submitForm($conn, $user_id, 'IPCR', $period, $content);
        
        if ($result['success']) {
            // Get department head info for the confirmation message
            $dept_head_query = "SELECT u.name, u.email FROM users u 
                               WHERE u.department_id = ? AND u.role = 'department_head'";
            $dept_stmt = $conn->prepare($dept_head_query);
            $dept_stmt->bind_param("i", $user_department_id);
            $dept_stmt->execute();
            $dept_head_result = $dept_stmt->get_result();
            $dept_head = ($dept_head_result->num_rows > 0) ? $dept_head_result->fetch_assoc() : null;
            $dept_head_name = $dept_head ? $dept_head['name'] : "your department head";
            
            // Create a more visible success message with department head's name
            $success_message = "
                <div class='mb-3'><strong class='fs-5'>IPCR form submitted successfully!</strong></div>
                <div class='d-flex align-items-center mb-2'>
                    <div class='me-3'><i class='bi bi-check-circle-fill text-success fs-3'></i></div>
                    <div>
                        Your form has been sent to <strong>" . htmlspecialchars($dept_head_name) . "</strong> for review. 
                        You will be notified when your submission has been reviewed.
                    </div>
                </div>
                <div class='mt-2 small'>You can track the status of your submission in the <a href='#history' class='alert-link' data-bs-toggle='tab'>IPCR History tab</a>.</div>";
        } else {
            $error_message = $result['message'];
        }
    }
}
?>

<!-- IPCR Content -->
<div class="container-fluid py-4">
    <style>
        /* --- IPCR Form Table Enhancements for Readability and Spacing --- */

        /* Targets ALL input fields inside table cells in the IPCR body */
        #ipcr-table-body td input.form-control,
        #ipcr-table-body td textarea.form-control {
            /* Make the input background white for better contrast */
            background-color: #ffffff;
            /* Explicitly define a visible border that contrasts with the table border */
            border: 1px solid #c9c9c9; 
            /* Slightly increase vertical padding for more space inside the input */
            padding: 0.25rem 0.5rem;
            /* Important: Ensure the input fills the cell height */
            height: 100%;
            /* Use box-sizing to prevent padding/border from making the input overflow */
            box-sizing: border-box; 
            /* Remove default shadow if any */
            box-shadow: none; 
        }

        /* Specific styling for the small rating number boxes (Q, E, T) */
        #ipcr-table-body tr input.rating-input {
            text-align: center; /* Center the numbers for a cleaner look */
            border-radius: 0.15rem; 
            font-weight: 500;
        }

        /* Style for readonly fields (Averages, Supervisor Ratings) */
        #ipcr-table-body tr input[readonly] {
            background-color: #f8f9fa; /* A very light grey for visual distinction */
            cursor: not-allowed;
            color: #495057; /* Slightly darker text for readability */
        }

        /* Increase vertical padding on table cells themselves for better row spacing */
        #ipcr-table-body tr td {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            vertical-align: top; /* Align content to the top of the (now taller) cell */
        }

        /* Style for the remove button cell */
        .remove-row-cell {
            vertical-align: middle !important;
            padding-left: 0.2rem !important;
            padding-right: 0.2rem !important;
            text-align: center;
        }
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
        }
    </style>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Individual Performance Commitment and Review</h1>
        <div>
            <button class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-calendar"></i> 
                <?php echo date('F d, Y'); ?>
            </button>
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
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link active" href="#new-form" data-bs-toggle="tab">Create New IPCR</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#history" data-bs-toggle="tab">IPCR History</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="new-form">
                    <form action="ipcr.php" method="POST" id="ipcr-form">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="period" class="form-label">Evaluation Period</label>
                                <select class="form-select" name="period" id="period" required>
                                    <option value="">Select Period</option>
                                    <option value="Q1 <?php echo date('Y'); ?>">Q1 (January-March) <?php echo date('Y'); ?></option>
                                    <option value="Q2 <?php echo date('Y'); ?>">Q2 (April-June) <?php echo date('Y'); ?></option>
                                    <option value="Q3 <?php echo date('Y'); ?>">Q3 (July-September) <?php echo date('Y'); ?></option>
                                    <option value="Q4 <?php echo date('Y'); ?>">Q4 (October-December) <?php echo date('Y'); ?></option>
                                    <option value="Annual <?php echo date('Y'); ?>">Annual <?php echo date('Y'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="computation_type" class="form-label">Weight Distribution</label>
                                <select class="form-select" name="computation_type" id="computation_type" required>
                                    <?php $computation_types_list = getComputationTypes();
                                    foreach ($computation_types_list as $type => $description) {
                                        $selected = ($computation_type === $type) ? 'selected' : '';
                                        echo "<option value=\"$type\" $selected>$description</option>";
                                    } ?>
                                </select>
                                <div class="form-text">Type 2 is required if Support Functions are included.</div>
                            </div>
                        </div>
                        
                        <h5 class="text-center fw-bold my-4">INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR) FORM</h5>
                        
                        <div class="mb-4">
                            <p>I, <u><?php echo htmlspecialchars($_SESSION['user_name']); ?></u>, of <u><?php echo htmlspecialchars($department_name); ?></u>, 
                            commit to deliver and agree to be rated on the attainment of the following targets in accordance with the indicated measures for the period <span id="period-display"></span>.</p>
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
                                        <th rowspan="2" class="align-middle text-center" style="width: 2%"></th> 
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
                                        <td colspan="12" class="text-start bg-light fw-bold">
                                            I. STRATEGIC FUNCTIONS <span id="strategic_weight" class="float-end"><?php echo $strategic_weight_display; ?></span>
                                        </td>
                                        <td class="bg-light text-center">
                                            <button type="button" class="btn btn-sm btn-success add-row-btn" data-category="strategic" title="Add Row">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php foreach($strategic_entries as $i => $entry): ?>
                                    <tr class="function-row strategic-function-row" data-category="strategic" data-index="<?php echo $i; ?>">
                                        <td><textarea class="form-control form-control-sm" name="strategic_mfo[]"><?php echo htmlspecialchars($entry['mfo'] ?? ''); ?></textarea></td>
                                        <td><textarea class="form-control form-control-sm" name="strategic_success_indicators[]"><?php echo htmlspecialchars($entry['success_indicators'] ?? ''); ?></textarea></td>
                                        <td><textarea class="form-control form-control-sm" name="strategic_accomplishments[]"><?php echo htmlspecialchars($entry['accomplishments'] ?? ''); ?></textarea></td>
                                        <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="strategic_q[]" min="1" max="5" step="1" maxlength="1" data-type="strategic"></td>
                                        <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="strategic_e[]" min="1" max="5" step="1" maxlength="1" data-type="strategic"></td>
                                        <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="strategic_t[]" min="1" max="5" step="1" maxlength="1" data-type="strategic"></td>
                                        <td><input type="text" class="form-control form-control-sm average-rating strategic_a" name="strategic_a[]" readonly></td>
                                        <td><input type="number" class="form-control form-control-sm supervisor-rating" name="strategic_supervisor_q[]" readonly></td>
                                        <td><input type="number" class="form-control form-control-sm supervisor-rating" name="strategic_supervisor_e[]" readonly></td>
                                        <td><input type="number" class="form-control form-control-sm supervisor-rating" name="strategic_supervisor_t[]" readonly></td>
                                        <td><input type="text" class="form-control form-control-sm supervisor-average-rating" name="strategic_supervisor_a[]" readonly></td>
                                        <td><input type="text" class="form-control form-control-sm" name="strategic_remarks[]" readonly></td>
                                        <td class="remove-row-cell">
                                            <button type="button" class="btn btn-danger btn-sm remove-row-btn" title="Remove Row">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <tr>
                                        <td colspan="12" class="text-start bg-light fw-bold">
                                            II. CORE FUNCTIONS <span id="core_weight" class="float-end"><?php echo $core_weight_display; ?></span>
                                        </td>
                                        <td class="bg-light text-center">
                                            <button type="button" class="btn btn-sm btn-success add-row-btn" data-category="core" title="Add Row">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php foreach($core_entries as $i => $entry): ?>
                                    <tr class="function-row core-function-row" data-category="core" data-index="<?php echo $i; ?>">
                                        <td><textarea class="form-control form-control-sm" name="core_mfo[]"><?php echo htmlspecialchars($entry['mfo'] ?? ''); ?></textarea></td>
                                        <td><textarea class="form-control form-control-sm" name="core_success_indicators[]"><?php echo htmlspecialchars($entry['success_indicators'] ?? ''); ?></textarea></td>
                                        <td><textarea class="form-control form-control-sm" name="core_accomplishments[]"><?php echo htmlspecialchars($entry['accomplishments'] ?? ''); ?></textarea></td>
                                        <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="core_q[]" min="1" max="5" step="1" data-type="core"></td>
                                        <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="core_e[]" min="1" max="5" step="1" data-type="core"></td>
                                        <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="core_t[]" min="1" max="5" step="1" data-type="core"></td>
                                        <td><input type="text" class="form-control form-control-sm average-rating core_a" name="core_a[]" readonly></td>
                                        <td><input type="number" class="form-control form-control-sm supervisor-rating" name="core_supervisor_q[]" readonly></td>
                                        <td><input type="number" class="form-control form-control-sm supervisor-rating" name="core_supervisor_e[]" readonly></td>
                                        <td><input type="number" class="form-control form-control-sm supervisor-rating" name="core_supervisor_t[]" readonly></td>
                                        <td><input type="text" class="form-control form-control-sm supervisor-average-rating" name="core_supervisor_a[]" readonly></td>
                                        <td><input type="text" class="form-control form-control-sm" name="core_remarks[]" readonly></td>
                                        <td class="remove-row-cell">
                                            <button type="button" class="btn btn-danger btn-sm remove-row-btn" title="Remove Row">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <tr class="support-functions-header" style="display: <?php echo $support_display_style; ?>;">
                                        <td colspan="12" class="text-start bg-light fw-bold">
                                            III. SUPPORT FUNCTIONS <span id="support_weight" class="float-end">(10%)</span>
                                        </td>
                                        <td class="bg-light text-center">
                                            <button type="button" class="btn btn-sm btn-success add-row-btn" data-category="support" title="Add Row">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tbody id="support_functions_rows" style="display: <?php echo $support_display_style; ?>;">
                                        <?php foreach($support_entries as $i => $entry): ?>
                                        <tr class="function-row support-function-row" data-category="support" data-index="<?php echo $i; ?>">
                                            <td><textarea class="form-control form-control-sm" name="support_mfo[]"><?php echo htmlspecialchars($entry['mfo'] ?? ''); ?></textarea></td>
                                            <td><textarea class="form-control form-control-sm" name="support_success_indicators[]"><?php echo htmlspecialchars($entry['success_indicators'] ?? ''); ?></textarea></td>
                                            <td><textarea class="form-control form-control-sm" name="support_accomplishments[]"><?php echo htmlspecialchars($entry['accomplishments'] ?? ''); ?></textarea></td>
                                            <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="support_q[]" min="1" max="5" step="1" data-type="support"></td>
                                            <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="support_e[]" min="1" max="5" step="1" data-type="support"></td>
                                            <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="support_t[]" min="1" max="5" step="1" data-type="support"></td>
                                            <td><input type="text" class="form-control form-control-sm average-rating support_a" name="support_a[]" readonly></td>
                                            <td><input type="number" class="form-control form-control-sm supervisor-rating" name="support_supervisor_q[]" readonly></td>
                                            <td><input type="number" class="form-control form-control-sm supervisor-rating" name="support_supervisor_e[]" readonly></td>
                                            <td><input type="number" class="form-control form-control-sm supervisor-rating" name="support_supervisor_t[]" readonly></td>
                                            <td><input type="text" class="form-control form-control-sm supervisor-average-rating" name="support_supervisor_a[]" readonly></td>
                                            <td><input type="text" class="form-control form-control-sm" name="support_remarks[]" readonly></td>
                                            <td class="remove-row-cell">
                                                <button type="button" class="btn btn-danger btn-sm remove-row-btn" title="Remove Row">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    
                                    <tr>
                                        <td colspan="8" class="text-end fw-bold align-middle">
                                            Summary of Self-Rating Average (Q/E/T)
                                        </td>
                                        <td colspan="4" class="p-2">
                                            <div class="row g-1">
                                                <div class="col-12"><small class="fw-bold">Strategic Average:</small> <input type="text" class="form-control form-control-sm" id="strategic_average" name="strategic_average" readonly></div>
                                                <div class="col-12"><small class="fw-bold">Core Average:</small> <input type="text" class="form-control form-control-sm" id="core_average" name="core_average" readonly></div>
                                                <div class="col-12 support-average-display" style="display: <?php echo $support_display_style; ?>;"><small class="fw-bold">Support Average:</small> <input type="text" class="form-control form-control-sm" id="support_average" name="support_average" readonly></div>
                                            </div>
                                        </td>
                                        <td colspan="1" class="p-2"></td>
                                    </tr>

                                    <tr>
                                        <td colspan="8" class="text-end fw-bold align-middle">
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
                                        <td colspan="1"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="card mt-3 mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Rating Guide</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h6>Quantity (Q)</h6>
                                        <ul class="small">
                                            <li><strong>5</strong> - Outstanding: Exceptional quality, exceeds expectations</li>
                                            <li><strong>4</strong> - Very Satisfactory: High quality, meets all expectations</li>
                                            <li><strong>3</strong> - Satisfactory: Acceptable quality, meets basic expectations</li>
                                            <li><strong>2</strong> - Unsatisfactory: Poor quality, below expectations</li>
                                            <li><strong>1</strong> - Poor: Very low quality, significantly below expectations</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-4">
                                        <h6>Efficiency (E)</h6>
                                        <ul class="small">
                                            <li><strong>5</strong> - Outstanding: Excellent use of resources</li>
                                            <li><strong>4</strong> - Very Satisfactory: Good resource utilization</li>
                                            <li><strong>3</strong> - Satisfactory: Average resource utilization</li>
                                            <li><strong>2</strong> - Unsatisfactory: Poor resource utilization</li>
                                            <li><strong>1</strong> - Poor: Wasteful use of resources</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-4">
                                        <h6>Timeliness (T)</h6>
                                        <ul class="small">
                                            <li><strong>5</strong> - Outstanding: Much earlier than deadline</li>
                                            <li><strong>4</strong> - Very Satisfactory: Ahead of deadline</li>
                                            <li><strong>3</strong> - Satisfactory: On time</li>
                                            <li><strong>2</strong> - Unsatisfactory: Slightly late</li>
                                            <li><strong>1</strong> - Poor: Significantly late</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="alert alert-info small mt-2 mb-0">
                                    <strong>Note:</strong> The Average (A) rating is automatically calculated as the average of Q, E, and T ratings. 
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-secondary" name="save_draft">
                                <i class="bi bi-save me-1"></i> Save as Draft
                            </button>
                            <button type="submit" class="btn btn-primary" name="submit_ipcr">
                                <i class="bi bi-check-circle me-1"></i> Submit for Review
                            </button>
                        </div>
                        <input type="hidden" name="content" id="form-content">
                    </form>
                </div>

                <div class="tab-pane fade" id="history">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th>Date Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($records_result->num_rows > 0): ?>
                                    <?php while ($record = $records_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['period']); ?></td>
                                        <td>
                                            <?php 
                                            $badge_class = 'bg-secondary';
                                            if ($record['status'] === 'Pending') $badge_class = 'bg-warning';
                                            if ($record['status'] === 'Approved') $badge_class = 'bg-success';
                                            if ($record['status'] === 'Rejected') $badge_class = 'bg-danger';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($record['status']); ?></span>
                                        </td>
                                        <td><?php echo $record['date_submitted'] ? date('M d, Y', strtotime($record['date_submitted'])) : 'N/A'; ?></td>
                                        <td>
                                            <a href="view_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <?php if ($record['status'] === 'Draft' || $record['status'] === 'Rejected'): ?>
                                            <a href="edit_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-warning me-1">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <?php endif; ?>
                                            <a href="print_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-printer"></i> Print
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No IPCR records found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include the auto-scoring.js file -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="js/auto_scoring.js"></script>

<script>
    // --- Dynamic Row Template Function ---
    function getNewRowTemplate(category) {
        const cat_prefix = category.toLowerCase();
        // The rating inputs no longer need a `data-index` because jQuery/Event Delegation handles them.
        return `
        <tr class="function-row ${cat_prefix}-function-row" data-category="${cat_prefix}">
            <td><textarea class="form-control form-control-sm" name="${cat_prefix}_mfo[]"></textarea></td>
            <td><textarea class="form-control form-control-sm" name="${cat_prefix}_success_indicators[]"></textarea></td>
            <td><textarea class="form-control form-control-sm" name="${cat_prefix}_accomplishments[]"></textarea></td>
            <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="${cat_prefix}_q[]" min="1" max="5" step="1" data-type="${cat_prefix}"></td>
            <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="${cat_prefix}_e[]" min="1" max="5" step="1" data-type="${cat_prefix}"></td>
            <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="${cat_prefix}_t[]" min="1" max="5" step="1" data-type="${cat_prefix}"></td>
            <td><input type="text" class="form-control form-control-sm average-rating ${cat_prefix}_a" name="${cat_prefix}_a[]" readonly></td>
            <td><input type="number" class="form-control form-control-sm supervisor-rating" name="${cat_prefix}_supervisor_q[]" readonly></td>
            <td><input type="number" class="form-control form-control-sm supervisor-rating" name="${cat_prefix}_supervisor_e[]" readonly></td>
            <td><input type="number" class="form-control form-control-sm supervisor-rating" name="${cat_prefix}_supervisor_t[]" readonly></td>
            <td><input type="text" class="form-control form-control-sm supervisor-average-rating" name="${cat_prefix}_supervisor_a[]" readonly></td>
            <td><input type="text" class="form-control form-control-sm" name="${cat_prefix}_remarks[]" readonly></td>
            <td class="remove-row-cell">
                <button type="button" class="btn btn-danger btn-sm remove-row-btn" title="Remove Row"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
        `;
    }

    // --- Computation Type Logic (Update Weights and Support Visibility) ---
    function updateComputationTypeDisplay(computationType) {
        if (computationType === 'Type2') {
            // Weights change to 45% / 45% / 10%
            $('#strategic_weight').text('(45%)');
            $('#core_weight').text('(45%)');
            
            // Support section becomes visible
            $('.support-functions-header, #support_functions_rows, .support-average-display').show();
            
            // If there are no entries in the Support section yet, add one empty row
            if ($('#support_functions_rows').children('.support-function-row').length === 0) {
                $('#support_functions_rows').append(getNewRowTemplate('Support'));
            }
        } else { // Type1
            // Weights change back to 45% / 55% / 0%
            $('#strategic_weight').text('(45%)');
            $('#core_weight').text('(55%)');
            
            // Support section becomes invisible
            $('.support-functions-header, #support_functions_rows, .support-average-display').hide();
        }
    }
    
    $(document).ready(function() {
        
        // --- 1. Dynamic Row Handling (Add/Remove) ---
        
        // Add Row Button Handler (uses event delegation on a static container)
        $(document).on('click', '.add-row-btn', function() {
            const category = $(this).data('category');
            const newRowHtml = getNewRowTemplate(category);
            
            // Append row to the correct section
            if (category === 'support') {
                $('#support_functions_rows').append(newRowHtml);
            } else {
                // Find the last row of the category before the summary rows
                const lastRow = $('#ipcr-table-body').find(`tr.${category}-function-row`).last();
                if (lastRow.length) {
                    lastRow.after(newRowHtml);
                } else {
                    // If no rows exist (shouldn't happen with default row, but for safety)
                    $(this).closest('tr').after(newRowHtml);
                }
            }
            
            // Since new inputs were added, the overall calculation needs to be updated
            updateFinalRating(); 
        });

        // Remove Row Button Handler
        $(document).on('click', '.remove-row-btn', function() {
            const rowToRemove = $(this).closest('tr.function-row');
            
            // Ensure we keep at least one row per category if it's the required type
            const category = rowToRemove.data('category');
            const computationType = $('#computation_type').val();
            
            // Check if removing the last required row
            if ((category === 'strategic' || category === 'core') && $(`tr.${category}-function-row`).length <= 1) {
                alert('You must have at least one entry for Strategic and Core functions.');
                return;
            }
            if (category === 'support' && computationType === 'Type2' && $(`tr.${category}-function-row`).length <= 1) {
                alert('You must have at least one entry for Support functions when using Type 2 computation.');
                return;
            }
            
            rowToRemove.remove();
            
            // Recalculate after removal
            updateFinalRating();
        });

        // --- 2. Conditional Computation Type Display ---
        
        // Initial setup for the form based on default value
        updateComputationTypeDisplay($('#computation_type').val());
        
        // Event listener for computation type change
        $('#computation_type').on('change', function() {
            const selectedType = $(this).val();
            updateComputationTypeDisplay(selectedType);
            // Also trigger rating recalculation in auto_scoring.js
            updateFinalRating();
        });
        
        // --- 3. Update Period Display & Form Submission (Existing Logic) ---

        // Update period display when period is selected
        const periodSelect = document.getElementById('period');
        const periodDisplay = document.getElementById('period-display');
        periodSelect.addEventListener('change', function() {
            periodDisplay.textContent = this.value;
        });

        // Prepare form data as JSON before submission
        document.getElementById('ipcr-form').addEventListener('submit', function(e) {
            
            const computationType = document.getElementById('computation_type').value;
            
            const formData = {
                period: document.getElementById('period').value,
                computation_type: computationType, // NEW: Include computation type
                strategic_functions: [],
                core_functions: [],
                support_functions: [],
                final_rating: document.getElementById('final_rating').value,
                strategic_average: document.getElementById('strategic_average').value,
                core_average: document.getElementById('core_average').value,
                support_average: document.getElementById('support_average')?.value || '', // Optional for Type1
                rating_interpretation: document.getElementById('rating_interpretation').textContent
            };

            // Helper to get array of values by name from a set of rows
            function getRowData(rows, category) {
                let data = [];
                rows.each(function(index, row) {
                    // Only process rows that have an MFO entered
                    const mfo = $(row).find(`textarea[name="${category}_mfo[]"]`).val().trim();
                    if (mfo !== "") {
                        data.push({
                            mfo: mfo,
                            success_indicators: $(row).find(`textarea[name="${category}_success_indicators[]"]`).val(),
                            accomplishments: $(row).find(`textarea[name="${category}_accomplishments[]"]`).val(),
                            q: $(row).find(`input[name="${category}_q[]"]`).val(),
                            e: $(row).find(`input[name="${category}_e[]"]`).val(),
                            t: $(row).find(`input[name="${category}_t[]"]`).val(),
                            a: $(row).find(`input[name="${category}_a[]"]`).val(),
                            supervisor_q: $(row).find(`input[name="${category}_supervisor_q[]"]`).val(),
                            supervisor_e: $(row).find(`input[name="${category}_supervisor_e[]"]`).val(),
                            supervisor_t: $(row).find(`input[name="${category}_supervisor_t[]"]`).val(),
                            supervisor_a: $(row).find(`input[name="${category}_supervisor_a[]"]`).val(),
                            remarks: $(row).find(`input[name="${category}_remarks[]"]`).val()
                        });
                    }
                });
                return data;
            }

            // Collect strategic functions data (uses jQuery selector on all relevant rows)
            formData.strategic_functions = getRowData($('.strategic-function-row'), 'strategic');

            // Collect core functions data
            formData.core_functions = getRowData($('.core-function-row'), 'core');

            // Collect support functions data (Only if Type2 is selected)
            if (computationType === 'Type2') {
                formData.support_functions = getRowData($('.support-function-row'), 'support');
            }
            
            // Check for empty rows before submission (basic validation to avoid saving empty entries)
            if (formData.strategic_functions.length === 0 || formData.core_functions.length === 0) {
                 e.preventDefault();
                 alert("Strategic and Core functions must have at least one entry with a Major Final Output.");
                 return;
            }
            if (computationType === 'Type2' && formData.support_functions.length === 0) {
                 e.preventDefault();
                 alert("Support functions must have at least one entry with a Major Final Output when using Type 2 computation.");
                 return;
            }

            document.getElementById('form-content').value = JSON.stringify(formData);
        });
        
        // Initial call to attach listeners to all existing inputs (from initial PHP load)
        // Note: auto_scoring.js uses event listeners attached directly to elements, which is now less reliable with dynamic rows.
        // We rely on the initial call in auto_scoring.js but for future robustness, developers should switch to jQuery delegation.
        // For now, the existing functionality in auto_scoring.js's DOMContentLoaded should cover the initial rows.
        
        // Add event delegation for rating inputs to ensure new rows are calculated
        $(document).on('input', '.rating-input', function() {
            // Re-use the updateFinalRating from auto_scoring.js
            // This relies on the global functions in auto_scoring.js being available
            // Since auto_scoring.js's `attachRatingListeners` is too specific, we do a quick local calculation and then call the global `updateFinalRating`.

            const row = $(this).closest('tr');
            const type = $(this).data('type');
            
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
            
            // Recalculate all averages and final rating
            updateFinalRating(); // Global function from auto_scoring.js
        });
    });
</script>

<?php
// Close database connection
$conn->close();

// Include footer
include_once('includes/footer.php');
?> 