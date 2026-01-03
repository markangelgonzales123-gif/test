<?php
// Set page title
$page_title = "Edit IDP - EPMS";

// Include header
include_once('includes/header.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if record ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: idp.php");
    exit();
}

$record_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "epms_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the record exists and belongs to the user
$stmt = $conn->prepare("SELECT * FROM records WHERE id = ? AND user_id = ? AND form_type = 'IDP'");
$stmt->bind_param("ii", $record_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: idp.php");
    exit();
}

$record = $result->fetch_assoc();

// Check if the record is still in draft status
if ($record['status'] !== 'Draft') {
    $_SESSION['error_message'] = "You can only edit IDP records that are in draft status.";
    header("Location: idp.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_entry'])) {
        // Add new IDP entry
        $development_needs = $_POST['development_needs'];
        $development_interventions = $_POST['development_interventions'];
        $target_competency_level = $_POST['target_competency_level'];
        $success_indicators = $_POST['success_indicators'];
        $timeline_start = $_POST['timeline_start'];
        $timeline_end = $_POST['timeline_end'];
        $resources_needed = $_POST['resources_needed'];
        
        $stmt = $conn->prepare("INSERT INTO idp_entries (record_id, development_needs, development_interventions, 
                              target_competency_level, success_indicators, timeline_start, timeline_end, resources_needed) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $record_id, $development_needs, $development_interventions, 
                       $target_competency_level, $success_indicators, $timeline_start, $timeline_end, $resources_needed);
        
        if ($stmt->execute()) {
            $message = "Development need added successfully.";
            $alert_class = "alert-success";
        } else {
            $message = "Error adding development need: " . $conn->error;
            $alert_class = "alert-danger";
        }
    } elseif (isset($_POST['edit_entry'])) {
        // Edit existing IDP entry
        $entry_id = $_POST['entry_id'];
        $development_needs = $_POST['development_needs'];
        $development_interventions = $_POST['development_interventions'];
        $target_competency_level = $_POST['target_competency_level'];
        $success_indicators = $_POST['success_indicators'];
        $timeline_start = $_POST['timeline_start'];
        $timeline_end = $_POST['timeline_end'];
        $resources_needed = $_POST['resources_needed'];
        
        $stmt = $conn->prepare("UPDATE idp_entries SET development_needs = ?, development_interventions = ?, 
                              target_competency_level = ?, success_indicators = ?, timeline_start = ?, 
                              timeline_end = ?, resources_needed = ? 
                              WHERE id = ? AND record_id = ?");
        $stmt->bind_param("sssssssii", $development_needs, $development_interventions, $target_competency_level, 
                       $success_indicators, $timeline_start, $timeline_end, $resources_needed, $entry_id, $record_id);
        
        if ($stmt->execute()) {
            $message = "Development need updated successfully.";
            $alert_class = "alert-success";
        } else {
            $message = "Error updating development need: " . $conn->error;
            $alert_class = "alert-danger";
        }
    } elseif (isset($_POST['delete_entry'])) {
        // Delete IDP entry
        $entry_id = $_POST['entry_id'];
        
        $stmt = $conn->prepare("DELETE FROM idp_entries WHERE id = ? AND record_id = ?");
        $stmt->bind_param("ii", $entry_id, $record_id);
        
        if ($stmt->execute()) {
            $message = "Development need deleted successfully.";
            $alert_class = "alert-success";
        } else {
            $message = "Error deleting development need: " . $conn->error;
            $alert_class = "alert-danger";
        }
    }
}

// Get existing IDP entries
$stmt = $conn->prepare("SELECT * FROM idp_entries WHERE record_id = ? ORDER BY id ASC");
$stmt->bind_param("i", $record_id);
$stmt->execute();
$entries_result = $stmt->get_result();
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Individual Development Plan</h1>
        <div>
            <a href="idp.php" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to IDP List
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEntryModal">
                <i class="bi bi-plus-circle"></i> Add Development Need
            </button>
        </div>
    </div>
    
    <?php if (isset($message)): ?>
        <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">IDP Details</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <p class="mb-1 text-muted small">Period</p>
                    <p class="mb-0 fw-bold"><?php echo htmlspecialchars($record['period']); ?></p>
                </div>
                <div class="col-md-4">
                    <p class="mb-1 text-muted small">Status</p>
                    <p class="mb-0">
                        <span class="badge bg-secondary">Draft</span>
                    </p>
                </div>
                <div class="col-md-4">
                    <p class="mb-1 text-muted small">Created</p>
                    <p class="mb-0"><?php echo date('M d, Y', strtotime($record['created_at'])); ?></p>
                </div>
            </div>
            
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                Add your development needs, interventions, and success indicators. Once you've completed your IDP, go back to the IDP list and submit it for approval.
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Development Needs</h5>
            <div class="small text-muted">
                <i class="bi bi-info-circle"></i> 
                Add at least one development need to submit your IDP
            </div>
        </div>
        <div class="card-body">
            <?php if ($entries_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 20%">Development Needs</th>
                                <th style="width: 20%">Development Interventions</th>
                                <th style="width: 15%">Success Indicators</th>
                                <th style="width: 15%">Timeline</th>
                                <th style="width: 15%">Resources Needed</th>
                                <th style="width: 15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($entry = $entries_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <p class="mb-1 fw-semibold"><?php echo htmlspecialchars($entry['development_needs']); ?></p>
                                        <p class="small text-muted mb-0">Target Competency Level: <?php echo $entry['target_competency_level']; ?></p>
                                    </td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['development_interventions'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['success_indicators'])); ?></td>
                                    <td>
                                        <?php 
                                        if ($entry['timeline_start'] && $entry['timeline_end']) {
                                            echo date('M d, Y', strtotime($entry['timeline_start'])); 
                                            echo ' to '; 
                                            echo date('M d, Y', strtotime($entry['timeline_end']));
                                        } else {
                                            echo '<span class="text-muted">Not specified</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['resources_needed'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary mb-1" data-bs-toggle="modal" data-bs-target="#editEntryModal<?php echo $entry['id']; ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteEntryModal<?php echo $entry['id']; ?>">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Edit Entry Modal -->
                                <div class="modal fade" id="editEntryModal<?php echo $entry['id']; ?>" tabindex="-1" aria-labelledby="editEntryModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editEntryModalLabel">Edit Development Need</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="post" action="edit_idp.php?id=<?php echo $record_id; ?>">
                                                <div class="modal-body">
                                                    <input type="hidden" name="entry_id" value="<?php echo $entry['id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label for="development_needs" class="form-label">Development Needs</label>
                                                        <input type="text" class="form-control" id="development_needs" name="development_needs" value="<?php echo htmlspecialchars($entry['development_needs']); ?>" required>
                                                        <div class="form-text">Specify the competency or skill you want to develop</div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="target_competency_level" class="form-label">Target Competency Level (1-5)</label>
                                                        <input type="number" class="form-control" id="target_competency_level" name="target_competency_level" min="1" max="5" value="<?php echo $entry['target_competency_level']; ?>" required>
                                                        <div class="form-text">1 = Basic, 5 = Expert</div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="development_interventions" class="form-label">Development Interventions</label>
                                                        <textarea class="form-control" id="development_interventions" name="development_interventions" rows="3" required><?php echo htmlspecialchars($entry['development_interventions']); ?></textarea>
                                                        <div class="form-text">Specify the learning activities to develop the competency</div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="success_indicators" class="form-label">Success Indicators</label>
                                                        <textarea class="form-control" id="success_indicators" name="success_indicators" rows="3" required><?php echo htmlspecialchars($entry['success_indicators']); ?></textarea>
                                                        <div class="form-text">How will you measure success?</div>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label for="timeline_start" class="form-label">Timeline Start</label>
                                                            <input type="date" class="form-control" id="timeline_start" name="timeline_start" value="<?php echo $entry['timeline_start']; ?>">
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="timeline_end" class="form-label">Timeline End</label>
                                                            <input type="date" class="form-control" id="timeline_end" name="timeline_end" value="<?php echo $entry['timeline_end']; ?>">
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="resources_needed" class="form-label">Resources Needed</label>
                                                        <textarea class="form-control" id="resources_needed" name="resources_needed" rows="2"><?php echo htmlspecialchars($entry['resources_needed']); ?></textarea>
                                                        <div class="form-text">What resources will you need? (budget, materials, mentors, etc.)</div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary" name="edit_entry">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Delete Entry Modal -->
                                <div class="modal fade" id="deleteEntryModal<?php echo $entry['id']; ?>" tabindex="-1" aria-labelledby="deleteEntryModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteEntryModalLabel">Confirm Deletion</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to delete this development need? This action cannot be undone.
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form method="post" action="edit_idp.php?id=<?php echo $record_id; ?>">
                                                    <input type="hidden" name="entry_id" value="<?php echo $entry['id']; ?>">
                                                    <button type="submit" name="delete_entry" class="btn btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-journal-plus text-muted" style="font-size: 3rem;"></i>
                    </div>
                    <h5>No Development Needs Added Yet</h5>
                    <p class="text-muted">
                        Add at least one development need to your IDP.
                    </p>
                    <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addEntryModal">
                        <i class="bi bi-plus-circle"></i> Add Development Need
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between">
                <a href="idp.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to IDP List
                </a>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEntryModal">
                        <i class="bi bi-plus-circle"></i> Add Development Need
                    </button>
                    <?php if ($entries_result->num_rows > 0): ?>
                        <form method="post" action="idp.php" class="d-inline">
                            <input type="hidden" name="record_id" value="<?php echo $record_id; ?>">
                            <button type="submit" name="submit_idp" class="btn btn-success ms-2">
                                <i class="bi bi-send"></i> Submit for Approval
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Entry Modal -->
<div class="modal fade" id="addEntryModal" tabindex="-1" aria-labelledby="addEntryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEntryModalLabel">Add Development Need</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="edit_idp.php?id=<?php echo $record_id; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="development_needs" class="form-label">Development Needs</label>
                        <input type="text" class="form-control" id="development_needs" name="development_needs" required>
                        <div class="form-text">Specify the competency or skill you want to develop</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="target_competency_level" class="form-label">Target Competency Level (1-5)</label>
                        <input type="number" class="form-control" id="target_competency_level" name="target_competency_level" min="1" max="5" value="3" required>
                        <div class="form-text">1 = Basic, 5 = Expert</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="development_interventions" class="form-label">Development Interventions</label>
                        <textarea class="form-control" id="development_interventions" name="development_interventions" rows="3" required></textarea>
                        <div class="form-text">Specify the learning activities to develop the competency</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="success_indicators" class="form-label">Success Indicators</label>
                        <textarea class="form-control" id="success_indicators" name="success_indicators" rows="3" required></textarea>
                        <div class="form-text">How will you measure success?</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="timeline_start" class="form-label">Timeline Start</label>
                            <input type="date" class="form-control" id="timeline_start" name="timeline_start">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="timeline_end" class="form-label">Timeline End</label>
                            <input type="date" class="form-control" id="timeline_end" name="timeline_end">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="resources_needed" class="form-label">Resources Needed</label>
                        <textarea class="form-control" id="resources_needed" name="resources_needed" rows="2"></textarea>
                        <div class="form-text">What resources will you need? (budget, materials, mentors, etc.)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="add_entry">Add Development Need</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$conn->close();
include_once('includes/footer.php');
?> 