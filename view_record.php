<?php
// Set page title
$page_title = "View Record - EPMS";

// Include header
include_once('includes/header.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if record ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: records.php");
    exit();
}

$record_id = intval($_GET['id']);

// Database connection
require_once 'includes/db_connect.php';

// Get user info
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$department_id = $_SESSION['user_department_id'] ?? null;

// Get record data
$record_query = "SELECT r.*, u.name as employee_name, u.department_id, 
                 d.name as department_name, rev.name as reviewer_name
          FROM records r
                 JOIN users u ON r.user_id = u.id 
                 LEFT JOIN departments d ON u.department_id = d.id
                 LEFT JOIN users rev ON r.reviewed_by = rev.id
          WHERE r.id = ?";
$stmt = $conn->prepare($record_query);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$record_result = $stmt->get_result();

if ($record_result->num_rows === 0) {
    $_SESSION['error_message'] = "Record not found!";
    header("Location: records.php");
    exit();
}

$record = $record_result->fetch_assoc();

// Check permission to view this record
$has_permission = false;

// The record owner can always view
if ($record['user_id'] == $user_id) {
    $has_permission = true;
}
// Department head can view records from their department
else if ($user_role == 'department_head' && $record['department_id'] == $department_id) {
    $has_permission = true;
}
// Admin and president can view all records
else if ($user_role == 'admin' || $user_role == 'president') {
    $has_permission = true;
}

if (!$has_permission) {
    $_SESSION['error_message'] = "You don't have permission to view this record!";
    header("Location: records.php");
    exit();
}

// Check if form is being submitted for approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_action'])) {
    // Verify user has permission to approve/reject
    $can_review = ($user_role == 'department_head' && $record['department_id'] == $department_id && $record['user_id'] != $user_id) || 
                  ($user_role == 'president') ||
                  ($user_role == 'admin');
    
    if (!$can_review) {
        $_SESSION['error_message'] = "You don't have permission to review this record!";
        header("Location: view_record.php?id=" . $record_id);
        exit();
    }
    
    // Update record status
    $new_status = ($_POST['review_action'] === 'approve') ? 'Approved' : 'Rejected';
    $comments = trim($_POST['comments'] ?? '');
    
    $update_query = "UPDATE records SET status = ?, reviewed_by = ?, date_reviewed = NOW(), comments = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sisi", $new_status, $user_id, $comments, $record_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Record has been " . strtolower($new_status) . "!";
    } else {
        $_SESSION['error_message'] = "Error updating record: " . $conn->error;
    }
    
    header("Location: records.php");
    exit();
}

// Get entries based on form type
$entries = [];

if ($record['form_type'] === 'DPCR') {
    $entries_query = "SELECT * FROM dpcr_entries WHERE record_id = ? ORDER BY category, id";
    $stmt = $conn->prepare($entries_query);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $entries_result = $stmt->get_result();
    
    $strategic_entries = [];
    $core_entries = [];
    
    while ($entry = $entries_result->fetch_assoc()) {
        if ($entry['category'] === 'Strategic') {
            $strategic_entries[] = $entry;
        } else if ($entry['category'] === 'Core') {
            $core_entries[] = $entry;
        }
    }
    
    $entries = [
        'strategic' => $strategic_entries,
        'core' => $core_entries
    ];
} else if ($record['form_type'] === 'IPCR') {
    // First check if there are entries in the ipcr_entries table
    $entries_query = "SELECT * FROM ipcr_entries WHERE record_id = ? ORDER BY category, id";
    $stmt = $conn->prepare($entries_query);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $entries_result = $stmt->get_result();
    
    $strategic_entries = [];
    $core_entries = [];
    $support_entries = [];
    
    if ($entries_result->num_rows > 0) {
        // If we have structured entries, use them
        while ($entry = $entries_result->fetch_assoc()) {
            if ($entry['category'] === 'Strategic') {
                $strategic_entries[] = $entry;
            } else if ($entry['category'] === 'Core') {
                $core_entries[] = $entry;
            } else if ($entry['category'] === 'Support') {
                $support_entries[] = $entry;
            }
        }
    } else if (!empty($record['content'])) {
        // If we have JSON content, parse it
        $content = json_decode($record['content'], true);

        if (isset($content['strategic_functions']) && is_array($content['strategic_functions'])) {
            foreach ($content['strategic_functions'] as $func) {
                $strategic_entries[] = [
                    'major_output' => $func['mfo'] ?? '',
                    'success_indicators' => $func['success_indicators'] ?? '',
                    'actual_accomplishments' => $func['accomplishments'] ?? '',
                    'q_rating' => $func['q'] ?? '',
                    'e_rating' => $func['e'] ?? '',
                    't_rating' => $func['t'] ?? '',
                    'final_rating' => $func['a'] ?? '',
                    'remarks' => $func['remarks'] ?? ''
                ];
            }
        }
        
        if (isset($content['core_functions']) && is_array($content['core_functions'])) {
            foreach ($content['core_functions'] as $func) {
                $core_entries[] = [
                    'major_output' => $func['mfo'] ?? '',
                    'success_indicators' => $func['success_indicators'] ?? '',
                    'actual_accomplishments' => $func['accomplishments'] ?? '',
                    'q_rating' => $func['q'] ?? '',
                    'e_rating' => $func['e'] ?? '',
                    't_rating' => $func['t'] ?? '',
                    'final_rating' => $func['a'] ?? '',
                    'remarks' => $func['remarks'] ?? ''
                ];
            }
        }
        
        if (isset($content['support_functions']) && is_array($content['support_functions'])) {
            foreach ($content['support_functions'] as $func) {
                $support_entries[] = [
                    'major_output' => $func['mfo'] ?? '',
                    'success_indicators' => $func['success_indicators'] ?? '',
                    'actual_accomplishments' => $func['accomplishments'] ?? '',
                    'q_rating' => $func['q'] ?? '',
                    'e_rating' => $func['e'] ?? '',
                    't_rating' => $func['t'] ?? '',
                    'final_rating' => $func['a'] ?? '',
                    'remarks' => $func['remarks'] ?? ''
                ];
            }
        }
    }
    
    $entries = [
        'strategic' => $strategic_entries,
        'core' => $core_entries,
        'support' => $support_entries
    ];
} else if ($record['form_type'] === 'IDP') {
    // First check if there are entries in the idp_entries table
    $entries_query = "SELECT * FROM idp_entries WHERE record_id = ? ORDER BY id";
    $stmt = $conn->prepare($entries_query);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $entries_result = $stmt->get_result();
    
    $idp_entries = [];
    
    if ($entries_result->num_rows > 0) {
        // If we have structured entries, use them
        while ($entry = $entries_result->fetch_assoc()) {
            $idp_entries[] = $entry;
        }
    } else if (!empty($record['content'])) {
        // If we have JSON content, parse it
        $content = json_decode($record['content'], true);
        
        // Process Professional Development entries
        if (isset($content['professional_development']) && is_array($content['professional_development'])) {
            foreach ($content['professional_development'] as $item) {
                $idp_entries[] = [
                    'development_needs' => 'Professional', // Category marker
                    'goals' => $item['goals'] ?? '',
                    'competencies' => $item['competencies'] ?? '',
                    'actions' => $item['actions'] ?? '',
                    'timeline_display' => $item['timeline'] ?? '',
                    'status' => $item['status'] ?? 'Not Started'
                ];
            }
        }
        
        // Process Personal Development entries
        if (isset($content['personal_development']) && is_array($content['personal_development'])) {
            foreach ($content['personal_development'] as $item) {
                $idp_entries[] = [
                    'development_needs' => 'Personal', // Category marker
                    'goals' => $item['goals'] ?? '',
                    'competencies' => $item['competencies'] ?? '',
                    'actions' => $item['actions'] ?? '',
                    'timeline_display' => $item['timeline'] ?? '',
                    'status' => $item['status'] ?? 'Not Started'
                ];
            }
        }
        
        // Process Career Advancement entries
        if (isset($content['career_advancement']) && is_array($content['career_advancement'])) {
            foreach ($content['career_advancement'] as $item) {
                $idp_entries[] = [
                    'development_needs' => 'Career', // Category marker
                    'goals' => $item['goals'] ?? '',
                    'competencies' => $item['competencies'] ?? '',
                    'actions' => $item['actions'] ?? '',
                    'timeline_display' => $item['timeline'] ?? '',
                    'status' => $item['status'] ?? 'Not Started'
                ];
            }
        }
    }
    
    $entries = [
        'idp' => $idp_entries
    ];
}

// Check if user can review this record
$can_review = ($user_role == 'department_head' && $record['department_id'] == $department_id && $record['user_id'] != $user_id) || 
              ($user_role == 'president') ||
              ($user_role == 'admin');

// Check if success/error messages exist
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Clear session messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!-- View Record Content -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <?php echo $record['form_type']; ?> - 
            <?php echo htmlspecialchars($record['employee_name']); ?>
        </h1>
        <div>
            <a href="records.php" class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to Records
            </a>
            <?php if ($record['document_status'] === 'Approved'): ?>
            <a href="print_record.php?id=<?php echo $record_id; ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-printer"></i> Print
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
    
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Record Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="fw-bold">Employee</div>
                    <div><?php echo htmlspecialchars($record['employee_name']); ?></div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="fw-bold">Department</div>
                    <div><?php echo htmlspecialchars($record['department_name'] ?? 'Not Assigned'); ?></div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="fw-bold">Period</div>
                    <div><?php echo htmlspecialchars($record['period']); ?></div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="fw-bold">Status</div>
                    <div>
                        <?php 
                        $status_class = "";
                        switch ($record['document_status']) {
                            case 'Draft':
                                $status_class = "secondary";
                                break;
                            case 'Approved':
                                $status_class = "success";
                                break;
                            case 'Pending':
                                $status_class = "warning";
                                break;
                            case 'Rejected':
                                $status_class = "danger";
                                break;
                        }
                        ?>
                        <span class="badge bg-<?php echo $status_class; ?>">
                            <?php echo $record['document_status']; ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($record['date_submitted']): ?>
                <div class="col-md-3 mb-3">
                    <div class="fw-bold">Date Submitted</div>
                    <div><?php echo date('F d, Y', strtotime($record['date_submitted'])); ?></div>
            </div>
                <?php endif; ?>
                
                <?php if ($record['reviewed_by']): ?>
                <div class="col-md-3 mb-3">
                    <div class="fw-bold">Reviewed By</div>
                    <div><?php echo htmlspecialchars($record['reviewer_name']); ?></div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="fw-bold">Date Reviewed</div>
                    <div><?php echo date('F d, Y', strtotime($record['date_reviewed'])); ?></div>
                </div>
                <?php endif; ?>
                
                <?php /*if (!empty($record['comments'])): ?>
                <div class="col-md-12 mb-3">
                    <div class="fw-bold">Comments</div>
                    <div><?php echo nl2br(htmlspecialchars($record['comments'])); ?></div>
                </div>
                <?php endif; */?>
            </div>
        </div>
    </div>
            
    <!-- Review Status and Feedback Section -->
    <?php if($record['document_status'] !== 'Draft'): ?>
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Review Status</h5>
            <?php
                $status_badge_class = '';
                switch($record['document_status']) {
                    case 'Pending':
                        $status_badge_class = 'warning';
                        break;
                    case 'Approved':
                        $status_badge_class = 'success';
                        break;
                    case 'Rejected':
                        $status_badge_class = 'danger';
                        break;
                }
            ?>
            <span class="badge bg-<?php echo $status_badge_class; ?> fs-6"><?php echo $record['document_status']; ?></span>
        </div>
        <div class="card-body">
            <?php if($record['document_status'] === 'Pending'): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-hourglass-split me-2"></i>
                    <span>This form is currently pending review by your department head.</span>
                </div>
            <?php elseif($record['document_status'] === 'Approved' || $record['document_status'] === 'Rejected'): ?>
                <div class="d-flex mb-3">
                    <div class="me-3">
                        <i class="bi bi-person-circle fs-1 text-muted"></i>
                    </div>
                    <div>
                        <p class="mb-0">
                            <strong>Reviewed by:</strong> 
                            <?php echo htmlspecialchars($record['reviewer_name'] ?? 'Department Head'); ?>
                        </p>
                        <p class="mb-0 text-muted">
                            <small>
                                <i class="bi bi-clock me-1"></i>
                                <?php echo isset($record['reviewed_at']) ? date('F d, Y \a\t h:i A', strtotime($record['reviewed_at'])) : 'Date not recorded'; ?>
                            </small>
                        </p>
                    </div>
                </div>
                
                <!-- Feedback Section -->
                <?php if (!empty($record['feedback'])): ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Review Feedback</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="fw-bold">Feedback from <?php echo htmlspecialchars($record['reviewer_name'] ?? 'Reviewer'); ?>:</label>
                            <div class="border rounded p-3">
                                <?php echo nl2br(htmlspecialchars($record['feedback'])); ?>
                            </div>
                        </div>
                        
                        <?php 
                        // Only show remarks to department heads, admins, and presidents
                        if (($user_role == 'department_head' || $user_role == 'admin' || $user_role == 'president') && !empty($record['remarks'])): 
                        ?>
                        <div class="mb-0">
                            <label class="fw-bold">
                                Remarks <span class="badge bg-info">Department Head Only</span>
                            </label>
                            <div class="border rounded p-3 bg-light">
                                <?php echo nl2br(htmlspecialchars($record['remarks'])); ?>
                            </div>
                            <div class="form-text text-danger">
                                <i class="bi bi-lock-fill me-1"></i> These remarks are only visible to department heads and administrators.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if($record['document_status'] === 'Rejected'): ?>
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <span>Please review the feedback above and consider submitting a revised version.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
            
    <!-- DPCR Form Content -->
            <?php if ($record['form_type'] === 'DPCR'): ?>
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Department Performance Commitment and Review (DPCR)</h5>
        </div>
        <div class="card-body">
            <!-- Strategic Functions (45%) -->
            <div class="mb-4">
                <h5 class="mb-3">Strategic Functions (45%)</h5>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="30%">Major Final Output</th>
                                <th width="30%">Success Indicators</th>
                                <th width="15%">Budget</th>
                                <th width="25%">Accountable Units</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries['strategic'])): ?>
                            <tr>
                                <td colspan="4" class="text-center">No strategic outputs defined</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($entries['strategic'] as $entry): ?>
                                <tr>
                                    <td><?php echo nl2br(htmlspecialchars($entry['major_output'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['success_indicators'])); ?></td>
                                    <td><?php echo htmlspecialchars($entry['budget'] ? number_format($entry['budget'], 2) : 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($entry['accountable']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Core Functions (55%) -->
            <div class="mb-4">
                <h5 class="mb-3">Core Functions (55%)</h5>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="30%">Major Final Output</th>
                                <th width="30%">Success Indicators</th>
                                <th width="15%">Budget</th>
                                <th width="25%">Accountable Units</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries['core'])): ?>
                            <tr>
                                <td colspan="4" class="text-center">No core outputs defined</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($entries['core'] as $entry): ?>
                                <tr>
                                    <td><?php echo nl2br(htmlspecialchars($entry['major_output'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['success_indicators'])); ?></td>
                                    <td><?php echo htmlspecialchars($entry['budget'] ? number_format($entry['budget'], 2) : 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($entry['accountable']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
                <?php endif; ?>
                
    <!-- IPCR Form Content -->
    <?php if ($record['form_type'] === 'IPCR'): ?>
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Individual Performance Commitment and Review (IPCR)</h5>
        </div>
        <div class="card-body">
            <!-- Strategic Functions (45%) -->
            <div class="mb-4">
                <h5 class="mb-3">Strategic Functions (45%)</h5>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="20%">Major Final Output</th>
                                <th width="20%">Success Indicators</th>
                                <th width="25%">Actual Accomplishments</th>
                                <th width="25%" class="text-center">Rating</th>
                                <th width="10%">Remarks</th>
                            </tr>
                            <tr class="text-center">
                                <th colspan="3"></th>
                                <th>
                                    <div class="row">
                                        <div class="col-4">Q</div>
                                        <div class="col-4">E</div>
                                        <div class="col-4">T</div>
                                    </div>
                                </th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries['strategic'])): ?>
                            <tr>
                                <td colspan="5" class="text-center">No strategic outputs defined</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($entries['strategic'] as $entry): ?>
                                <tr>
                                    <td><?php echo nl2br(htmlspecialchars($entry['major_output'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['success_indicators'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['actual_accomplishments'] ?? 'Not yet accomplished')); ?></td>
                                    <td>
                                        <div class="row">
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm strategic-supervisor-q" 
                                                       name="strategic_supervisor_q[<?php echo $index; ?>]" 
                                                       min="1" max="5" value="<?php echo $entry['q_rating'] ?? ''; ?>" 
                                                       data-index="<?php echo $index; ?>" disabled>
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm strategic-supervisor-e" 
                                                       name="strategic_supervisor-e[<?php echo $index; ?>]" 
                                                       min="1" max="5" value="<?php echo $entry['e_rating'] ?? ''; ?>"
                                                       data-index="<?php echo $index; ?>" disabled>
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm strategic-supervisor-t" 
                                                       name="strategic_supervisor-t[<?php echo $index; ?>]" 
                                                       min="1" max="5" value="<?php echo $entry['t_rating'] ?? ''; ?>"
                                                       data-index="<?php echo $index; ?>" disabled>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" 
                                               name="strategic_remarks[<?php echo $index; ?>]" 
                                               value="<?php echo htmlspecialchars($entry['remarks'] ?? ''); ?>" disabled>
                                        <input type="hidden" name="strategic_id[<?php echo $index; ?>]" 
                                               value="<?php echo $entry['id'] ?? ''; ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Core Functions (45% or 55% depending on configuration) -->
            <div class="mb-4">
                <h5 class="mb-3">Core Functions (45%)</h5>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="20%">Major Final Output</th>
                                <th width="20%">Success Indicators</th>
                                <th width="25%">Actual Accomplishments</th>
                                <th width="25%" class="text-center">Rating</th>
                                <th width="10%">Remarks</th>
                            </tr>
                            <tr class="text-center">
                                <th colspan="3"></th>
                                <th>
                                    <div class="row">
                                        <div class="col-4">Q</div>
                                        <div class="col-4">E</div>
                                        <div class="col-4">T</div>
                                    </div>
                                </th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries['core'])): ?>
                            <tr>
                                <td colspan="5" class="text-center">No core outputs defined</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($entries['core'] as $entry): ?>
                                <tr>
                                    <td><?php echo nl2br(htmlspecialchars($entry['major_output'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['success_indicators'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['actual_accomplishments'] ?? 'Not yet accomplished')); ?></td>
                                    <td>
                                        <div class="row">
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm core-supervisor-q" 
                                                       name="core-supervisor-q[<?php echo $index; ?>]" 
                                                       min="1" max="5" value="<?php echo $entry['q_rating'] ?? ''; ?>"
                                                       data-index="<?php echo $index; ?>" disabled>
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm core-supervisor-e" 
                                                       name="core-supervisor-e[<?php echo $index; ?>]" 
                                                       min="1" max="5" value="<?php echo $entry['e_rating'] ?? ''; ?>"
                                                       data-index="<?php echo $index; ?>" disabled>
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm core-supervisor-t" 
                                                       name="core-supervisor-t[<?php echo $index; ?>]" 
                                                       min="1" max="5" value="<?php echo $entry['t_rating'] ?? ''; ?>"
                                                       data-index="<?php echo $index; ?>" disabled>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" 
                                               name="core_remarks[<?php echo $index; ?>]" 
                                               value="<?php echo htmlspecialchars($entry['remarks'] ?? ''); ?>" disabled>
                                        <input type="hidden" name="core_id[<?php echo $index; ?>]" 
                                               value="<?php echo $entry['id'] ?? ''; ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                </div>
                
            <!-- Support Functions (10% if used) -->
            <?php if (!empty($entries['support'])): ?>
            <div class="mb-4">
                <h5 class="mb-3">Support Functions (10%)</h5>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="20%">Major Final Output</th>
                                <th width="20%">Success Indicators</th>
                                <th width="25%">Actual Accomplishments</th>
                                <th width="25%" class="text-center">Rating</th>
                                <th width="10%">Remarks</th>
                            </tr>
                            <tr class="text-center">
                                <th colspan="3"></th>
                                <th>
                                    <div class="row">
                                        <div class="col-4">Q</div>
                                        <div class="col-4">E</div>
                                        <div class="col-4">T</div>
                                    </div>
                                </th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries['support'] as $entry): ?>
                            <tr>
                                <td><?php echo nl2br(htmlspecialchars($entry['major_output'])); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($entry['success_indicators'])); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($entry['actual_accomplishments'] ?? 'Not yet accomplished')); ?></td>
                                <td>
                                    <div class="row">
                                        <div class="col-4">
                                            <input type="number" class="form-control form-control-sm support-supervisor-q" 
                                                   name="support-supervisor-q[<?php echo $index; ?>]" 
                                                   min="1" max="5" value="<?php echo $entry['q_rating'] ?? ''; ?>"
                                                   data-index="<?php echo $index; ?>" disabled>
                                        </div>
                                        <div class="col-4">
                                            <input type="number" class="form-control form-control-sm support-supervisor-e" 
                                                   name="support-supervisor-e[<?php echo $index; ?>]" 
                                                   min="1" max="5" value="<?php echo $entry['e_rating'] ?? ''; ?>"
                                                   data-index="<?php echo $index; ?>" disabled>
                                        </div>
                                        <div class="col-4">
                                            <input type="number" class="form-control form-control-sm support-supervisor-t" 
                                                   name="support-supervisor-t[<?php echo $index; ?>]" 
                                                   min="1" max="5" value="<?php echo $entry['t_rating'] ?? ''; ?>"
                                                   data-index="<?php echo $index; ?>" disabled>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" 
                                           name="support_remarks[<?php echo $index; ?>]" 
                                           value="<?php echo htmlspecialchars($entry['remarks'] ?? ''); ?>" disabled>
                                    <input type="hidden" name="support_id[<?php echo $index; ?>]" 
                                           value="<?php echo $entry['id'] ?? ''; ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- IDP Form Content -->
    <?php if ($record['form_type'] === 'IDP'): ?>
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Individual Development Plan (IDP)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th width="20%">Development Goals</th>
                            <th width="20%">Required Competencies</th>
                            <th width="20%">Action Plan / Activities</th>
                            <th width="15%">Timeline</th>
                            <th width="10%">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($entries['idp'])): ?>
                        <tr>
                            <td colspan="5" class="text-center">No development plans defined</td>
                        </tr>
                        <?php else: ?>
                            <?php 
                            // Get all Professional Development entries first
                            $prof_entries = array_filter($entries['idp'], function($entry) {
                                return isset($entry['development_needs']) && $entry['development_needs'] === 'Professional'; 
                            });
                            
                            if (!empty($prof_entries)): 
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center bg-light fw-bold">PROFESSIONAL DEVELOPMENT</td>
                                </tr>
                            <?php foreach ($prof_entries as $entry): ?>
                            <tr>
                                <td><?php echo nl2br(htmlspecialchars($entry['goals'])); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($entry['competencies'] ?? '')); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($entry['actions'] ?? '')); ?></td>
                                <td>
                                    <?php if (isset($entry['timeline_start']) && isset($entry['timeline_end']) && $entry['timeline_start'] && $entry['timeline_end']): ?>
                                        <?php echo date('M d, Y', strtotime($entry['timeline_start'])); ?> to 
                                        <?php echo date('M d, Y', strtotime($entry['timeline_end'])); ?>
                                    <?php elseif (isset($entry['timeline_display'])): ?>
                                        <?php echo htmlspecialchars($entry['timeline_display']); ?>
                                    <?php else: ?>
                                        Not specified
                                    <?php endif; ?>
                                </td>
                                        <td>
                                            <?php 
                                    $status_class = "";
                                    switch ($entry['status']) {
                                        case 'Not Started':
                                            $status_class = "secondary";
                                                    break;
                                                case 'In Progress':
                                            $status_class = "warning";
                                                    break;
                                        case 'Completed':
                                            $status_class = "success";
                                                    break;
                                            }
                                            ?>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo $entry['status']; ?>
                                    </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php 
                            // Get all Personal Development entries
                            $personal_entries = array_filter($entries['idp'], function($entry) {
                                return isset($entry['development_needs']) && $entry['development_needs'] === 'Personal'; 
                            });
                            
                            if (!empty($personal_entries)): 
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center bg-light fw-bold">PERSONAL DEVELOPMENT</td>
                                </tr>
                            <?php foreach ($personal_entries as $entry): ?>
                            <tr>
                                <td><?php echo nl2br(htmlspecialchars($entry['goals'])); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($entry['competencies'] ?? '')); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($entry['actions'] ?? '')); ?></td>
                                <td>
                                    <?php if (isset($entry['timeline_start']) && isset($entry['timeline_end']) && $entry['timeline_start'] && $entry['timeline_end']): ?>
                                        <?php echo date('M d, Y', strtotime($entry['timeline_start'])); ?> to 
                                        <?php echo date('M d, Y', strtotime($entry['timeline_end'])); ?>
                                    <?php elseif (isset($entry['timeline_display'])): ?>
                                        <?php echo htmlspecialchars($entry['timeline_display']); ?>
                                    <?php else: ?>
                                        Not specified
                                    <?php endif; ?>
                                </td>
                                        <td>
                                            <?php 
                                    $status_class = "";
                                    switch ($entry['status']) {
                                        case 'Not Started':
                                            $status_class = "secondary";
                                                    break;
                                                case 'In Progress':
                                            $status_class = "warning";
                                                    break;
                                        case 'Completed':
                                            $status_class = "success";
                                                    break;
                                            }
                                            ?>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo $entry['status']; ?>
                                    </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php 
                            // Get all Career Development entries
                            $career_entries = array_filter($entries['idp'], function($entry) {
                                return isset($entry['development_needs']) && $entry['development_needs'] === 'Career'; 
                            });
                            
                            if (!empty($career_entries)): 
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center bg-light fw-bold">CAREER ADVANCEMENT</td>
                                </tr>
                            <?php foreach ($career_entries as $entry): ?>
                            <tr>
                                <td><?php echo nl2br(htmlspecialchars($entry['goals'])); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($entry['competencies'] ?? '')); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($entry['actions'] ?? '')); ?></td>
                                <td>
                                    <?php if (isset($entry['timeline_start']) && isset($entry['timeline_end']) && $entry['timeline_start'] && $entry['timeline_end']): ?>
                                        <?php echo date('M d, Y', strtotime($entry['timeline_start'])); ?> to 
                                        <?php echo date('M d, Y', strtotime($entry['timeline_end'])); ?>
                                    <?php elseif (isset($entry['timeline_display'])): ?>
                                        <?php echo htmlspecialchars($entry['timeline_display']); ?>
                                    <?php else: ?>
                                        Not specified
                                    <?php endif; ?>
                                </td>
                                        <td>
                                            <?php 
                                    $status_class = "";
                                    switch ($entry['status']) {
                                        case 'Not Started':
                                            $status_class = "secondary";
                                                    break;
                                                case 'In Progress':
                                            $status_class = "warning";
                                                    break;
                                        case 'Completed':
                                            $status_class = "success";
                                                    break;
                                            }
                                            ?>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo $entry['status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php 
                            // If there are entries that don't fit into the above categories, show them here
                            $other_entries = array_filter($entries['idp'], function($entry) {
                                return !isset($entry['development_needs']) || 
                                       ($entry['development_needs'] !== 'Professional' && 
                                        $entry['development_needs'] !== 'Personal' && 
                                        $entry['development_needs'] !== 'Career'); 
                            });
                            
                            foreach ($other_entries as $entry): 
                            ?>
                            <tr>
                                <td><?php echo nl2br(htmlspecialchars($entry['goals'] ?? '')); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($entry['competencies'] ?? '')); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($entry['actions'] ?? '')); ?></td>
                                <td>
                                    <?php if (isset($entry['timeline_start']) && isset($entry['timeline_end']) && $entry['timeline_start'] && $entry['timeline_end']): ?>
                                        <?php echo date('M d, Y', strtotime($entry['timeline_start'])); ?> to 
                                        <?php echo date('M d, Y', strtotime($entry['timeline_end'])); ?>
                                    <?php elseif (isset($entry['timeline_display'])): ?>
                                        <?php echo htmlspecialchars($entry['timeline_display']); ?>
                                    <?php else: ?>
                                        Not specified
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $status_class = "";
                                    switch ($entry['status']) {
                                        case 'Not Started':
                                            $status_class = "secondary";
                                            break;
                                        case 'In Progress':
                                            $status_class = "warning";
                                            break;
                                        case 'Completed':
                                            $status_class = "success";
                                            break;
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo $entry['status']; ?>
                                    </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
        </div>
                </div>
                <?php endif; ?>
                
    <!-- Review Form for Approvers -->
    <?php if ($can_review && $record['document_status'] === 'Pending' && $record['form_type'] === 'IPCR'): ?>
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Supervisor Rating</h5>
        </div>
        <div class="card-body">
            <form method="post" action="update_ratings.php">
                <input type="hidden" name="record_id" value="<?php echo $record_id; ?>">
                
                <!-- Strategic Functions Ratings -->
                <h5 class="mb-3">Strategic Functions (45%)</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="20%">Major Final Output</th>
                                <th width="20%">Success Indicators</th>
                                <th width="20%">Accomplishments</th>
                                <th width="25%" class="text-center">Supervisor Rating</th>
                                <th width="15%">Remarks</th>
                            </tr>
                            <tr class="text-center">
                                <th colspan="3"></th>
                                <th>
                                    <div class="row">
                                        <div class="col-4">Q</div>
                                        <div class="col-4">E</div>
                                        <div class="col-4">T</div>
                                    </div>
                                </th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries['strategic'])): ?>
                            <tr>
                                <td colspan="5" class="text-center">No strategic outputs defined</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($entries['strategic'] as $index => $entry): ?>
                                <tr>
                                    <td><?php echo nl2br(htmlspecialchars($entry['major_output'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['success_indicators'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['actual_accomplishments'] ?? 'Not yet accomplished')); ?></td>
                                    <td>
                                        <div class="row">
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm strategic-supervisor-q" 
                                                       name="strategic_supervisor-q[<?php echo $index; ?>]" 
                                                       min="1" max="5" value="<?php echo $entry['supervisor_q_rating'] ?? ''; ?>" 
                                                       data-index="<?php echo $index; ?>">
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm strategic-supervisor-e" 
                                                       name="strategic-supervisor-e[<?php echo $index; ?>]" 
                                                       min="1" max="5" value="<?php echo $entry['supervisor_e_rating'] ?? ''; ?>"
                                                       data-index="<?php echo $index; ?>">
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm strategic-supervisor-t" 
                                                       name="strategic-supervisor-t[<?php echo $index; ?>]" 
                                                       min="1" max="5" value="<?php echo $entry['supervisor_t_rating'] ?? ''; ?>"
                                                       data-index="<?php echo $index; ?>">
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" 
                                               name="strategic_remarks[<?php echo $index; ?>]" 
                                               value="<?php echo htmlspecialchars($entry['remarks'] ?? ''); ?>">
                                        <input type="hidden" name="strategic_id[<?php echo $index; ?>]" 
                                               value="<?php echo $entry['id'] ?? ''; ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Core Functions Ratings -->
                <h5 class="mb-3">Core Functions (55%)</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="20%">Major Final Output</th>
                                <th width="20%">Success Indicators</th>
                                <th width="20%">Accomplishments</th>
                                <th width="25%" class="text-center">Supervisor Rating</th>
                                <th width="15%">Remarks</th>
                            </tr>
                            <tr class="text-center">
                                <th colspan="3"></th>
                                <th>
                                    <div class="row">
                                        <div class="col-4">Q</div>
                                        <div class="col-4">E</div>
                                        <div class="col-4">T</div>
                                    </div>
                                </th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries['core'])): ?>
                            <tr>
                                <td colspan="5" class="text-center">No core outputs defined</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($entries['core'] as $index => $entry): ?>
                                <tr>
                                    <td><?php echo nl2br(htmlspecialchars($entry['major_output'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['success_indicators'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['actual_accomplishments'] ?? 'Not yet accomplished')); ?></td>
                                    <td>
                                        <div class="row">
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm core-supervisor-q" 
                                                       name="core-supervisor-q[<?php echo $index; ?>]" 
                                                       min="1" max="5" value="<?php echo $entry['supervisor_q_rating'] ?? ''; ?>"
                                                       data-index="<?php echo $index; ?>">
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm core-supervisor-e" 
                                                       name="core-supervisor-e[<?php echo $index; ?>]" 
                                                       min="1" max="5" value="<?php echo $entry['supervisor_e_rating'] ?? ''; ?>"
                                                       data-index="<?php echo $index; ?>">
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm core-supervisor-t" 
                                                       name="core-supervisor-t[<?php echo $index; ?>]" 
                                                       min="1" max="5" value="<?php echo $entry['supervisor_t_rating'] ?? ''; ?>"
                                                       data-index="<?php echo $index; ?>">
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" 
                                               name="core_remarks[<?php echo $index; ?>]" 
                                               value="<?php echo htmlspecialchars($entry['remarks'] ?? ''); ?>">
                                        <input type="hidden" name="core_id[<?php echo $index; ?>]" 
                                               value="<?php echo $entry['id'] ?? ''; ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Final Rating Summary -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Final Rating</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Strategic Functions Average (45%)</label>
                                    <input type="number" class="form-control" id="strategic_average" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Core Functions Average (55%)</label>
                                    <input type="number" class="form-control" id="core_average" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Final IPCR Rating</label>
                                    <input type="number" class="form-control" id="final_rating" name="final_rating" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="comments" class="form-label">Comments/Feedback</label>
                    <textarea class="form-control" id="comments" name="comments" rows="3"><?php echo htmlspecialchars($record['comments'] ?? ''); ?></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" name="action" value="save_ratings" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Ratings
                    </button>
                    <button type="submit" name="action" value="approve" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Save & Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Calculate average for strategic functions
            const calculateStrategicAverage = function(index) {
                const q = document.querySelector(`.strategic-supervisor-q[data-index="${index}"]`);
                const e = document.querySelector(`.strategic-supervisor-e[data-index="${index}"]`);
                const t = document.querySelector(`.strategic-supervisor-t[data-index="${index}"]`);
                
                if (!q || !e || !t) return;
                
                const avgField = document.querySelector(`input[name="strategic_supervisor_a[${index}]"]`);
                
                if (q.value && e.value && t.value) {
                    const avg = ((parseFloat(q.value) + parseFloat(e.value) + parseFloat(t.value)) / 3).toFixed(2);
                    avgField.value = avg;
                } else {
                    avgField.value = '';
                }
                
                calculateFinalRating();
            };
            
            // Calculate average for core functions
            const calculateCoreAverage = function(index) {
                const q = document.querySelector(`.core-supervisor-q[data-index="${index}"]`);
                const e = document.querySelector(`.core-supervisor-e[data-index="${index}"]`);
                const t = document.querySelector(`.core-supervisor-t[data-index="${index}"]`);
                
                if (!q || !e || !t) return;
                
                const avgField = document.querySelector(`input[name="core_supervisor_a[${index}]"]`);
                
                if (q.value && e.value && t.value) {
                    const avg = ((parseFloat(q.value) + parseFloat(e.value) + parseFloat(t.value)) / 3).toFixed(2);
                    avgField.value = avg;
                } else {
                    avgField.value = '';
                }
                
                calculateFinalRating();
            };
            
            // Calculate final rating
            const calculateFinalRating = function() {
                const strategicFields = document.querySelectorAll('.strategic-supervisor-a');
                const coreFields = document.querySelectorAll('.core-supervisor-a');
                
                let strategicTotal = 0;
                let strategicCount = 0;
                
                strategicFields.forEach(field => {
                    if (field.value) {
                        strategicTotal += parseFloat(field.value);
                        strategicCount++;
                    }
                });
                
                let coreTotal = 0;
                let coreCount = 0;
                
                coreFields.forEach(field => {
                    if (field.value) {
                        coreTotal += parseFloat(field.value);
                        coreCount++;
                    }
                });
                
                const strategicAverage = strategicCount > 0 ? strategicTotal / strategicCount : 0;
                const coreAverage = coreCount > 0 ? coreTotal / coreCount : 0;
                
                document.getElementById('strategic_average').value = strategicAverage.toFixed(2);
                document.getElementById('core_average').value = coreAverage.toFixed(2);
                
                // Apply weights: 45% for strategic, 55% for core
                const finalRating = (strategicAverage * 0.45) + (coreAverage * 0.55);
                document.getElementById('final_rating').value = finalRating.toFixed(2);
            };
            
            // Add event listeners to all rating inputs
            document.querySelectorAll('.strategic-supervisor-q, .strategic-supervisor-e, .strategic-supervisor-t').forEach(input => {
                input.addEventListener('input', function() {
                    calculateStrategicAverage(this.dataset.index);
                });
            });
            
            document.querySelectorAll('.core-supervisor-q, .core-supervisor-e, .core-supervisor-t').forEach(input => {
                input.addEventListener('input', function() {
                    calculateCoreAverage(this.dataset.index);
                });
            });
            
            // Initialize calculations
            document.querySelectorAll('.strategic-supervisor-q').forEach(input => {
                calculateStrategicAverage(input.dataset.index);
            });
            
            document.querySelectorAll('.core-supervisor-q').forEach(input => {
                calculateCoreAverage(input.dataset.index);
            });
            
            calculateFinalRating();
        });
    </script>
    <?php endif; ?>
    
    <!-- Regular Review Form for Approvers -->
    <?php if ($can_review && $record['document_status'] === 'Pending' && $record['form_type'] !== 'IPCR'): ?>
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Review Record</h5>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="mb-3">
                    <label for="comments" class="form-label">Comments/Feedback</label>
                    <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" name="review_action" value="reject" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this record?')">
                        <i class="bi bi-x-circle"></i> Reject
                    </button>
                    <button type="submit" name="review_action" value="approve" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include_once('includes/footer.php');
?> 