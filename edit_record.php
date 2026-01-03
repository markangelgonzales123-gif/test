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
$host = "localhost";
$username = "root";
$password = "";
$database = "epms_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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

// Check permissions - users can only edit their own records or if they have appropriate roles
$can_edit = false;

if ($user_id == $record['user_id']) {
    // Users can edit their own records if they're pending or draft
    $can_edit = ($record['status'] == 'Pending' || $record['status'] == 'Draft');
} else if ($user_role == 'admin') {
    // Admins can edit all records
    $can_edit = true;
} else if ($user_role == 'department_head' && $user_department_id == $record['department_id']) {
    // Department heads can edit records from their department if they're pending or draft
    $can_edit = ($record['status'] == 'Pending' || $record['status'] == 'Draft');
} else if ($user_role == 'president') {
    // Presidents can edit all records
    $can_edit = true;
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

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form data based on form type
    if (isset($_POST['update_record'])) {
        $form_type = $record['form_type'];
        $updated_content = $_POST['content'] ?? '';
        
        // Update the record
        $update_query = "UPDATE records SET content = ?, date_submitted = NOW() WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $updated_content, $record_id);
        
        if ($stmt->execute()) {
            $message = "Record updated successfully.";
            $message_type = "success";
            
            // If it's a DPCR, update the DPCR entries table too
            if ($form_type === 'DPCR' && isset($_POST['major_output']) && is_array($_POST['major_output'])) {
                // First delete existing entries
                $delete_query = "DELETE FROM dpcr_entries WHERE record_id = ?";
                $delete_stmt = $conn->prepare($delete_query);
                $delete_stmt->bind_param("i", $record_id);
                $delete_stmt->execute();
                
                // Then insert new entries
                $entry_query = "INSERT INTO dpcr_entries 
                               (record_id, major_output, success_indicators, budget, accountable, accomplishments, 
                                q1_rating, q2_rating, q3_rating, q4_rating) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $entry_stmt = $conn->prepare($entry_query);
                
                for ($i = 0; $i < count($_POST['major_output']); $i++) {
                    $major_output = $_POST['major_output'][$i] ?? '';
                    $success_indicators = $_POST['success_indicators'][$i] ?? '';
                    $budget = $_POST['budget'][$i] ?? '';
                    $accountable = $_POST['accountable'][$i] ?? '';
                    $accomplishments = $_POST['accomplishments'][$i] ?? '';
                    $q1 = $_POST['q1'][$i] ?? null;
                    $q2 = $_POST['q2'][$i] ?? null;
                    $q3 = $_POST['q3'][$i] ?? null;
                    $q4 = $_POST['q4'][$i] ?? null;
                    
                    $entry_stmt->bind_param("issssdddd", 
                        $record_id, $major_output, $success_indicators, $budget, 
                        $accountable, $accomplishments, $q1, $q2, $q3, $q4);
                    $entry_stmt->execute();
                }
            }
            
            // Redirect to view page after successful update
            header("Location: view_record.php?id=" . $record_id);
            exit();
        } else {
            $message = "Error updating record: " . $conn->error;
            $message_type = "danger";
        }
    }
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
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
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
                <!-- IPCR Edit Form -->
                <div id="ipcr-edit-section">
                    <!-- IPCR specific fields will be displayed here -->
                    <!-- This will be implemented in a more complete version -->
                    <div class="alert alert-info">
                        IPCR editing functionality will be implemented soon. Please go back to view the record.
                    </div>
                </div>
                
                <?php elseif ($record['form_type'] === 'IDP' && $content): ?>
                <!-- IDP Edit Form -->
                <div id="idp-edit-section">
                    <!-- IDP specific fields will be displayed here -->
                    <!-- This will be implemented in a more complete version -->
                    <div class="alert alert-info">
                        IDP editing functionality will be implemented soon. Please go back to view the record.
                    </div>
                </div>
                
                <?php else: ?>
                <div class="alert alert-warning">
                    This record type cannot be edited in this interface. Please contact your administrator.
                </div>
                <?php endif; ?>
                
                <!-- Hidden field for JSON content -->
                <input type="hidden" name="content" id="form-content-json">
                
                <div class="d-flex justify-content-end mt-4">
                    <a href="view_record.php?id=<?php echo $record_id; ?>" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" name="update_record" class="btn btn-primary">Update Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add row functionality for DPCR
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
                
                // Add event listener to the new remove button
                newRow.querySelector('.remove-row').addEventListener('click', function() {
                    if (tbody.children.length > 1) {
                        this.closest('tr').remove();
                    } else {
                        alert('You cannot remove the last row.');
                    }
                });
            });
        }
        
        // Add remove functionality for existing DPCR rows
        document.querySelectorAll('.remove-row').forEach(button => {
            button.addEventListener('click', function() {
                const tbody = document.getElementById('dpcr-table-body');
                if (tbody.children.length > 1) {
                    this.closest('tr').remove();
                } else {
                    alert('You cannot remove the last row.');
                }
            });
        });
        
        // Form submission handling
        document.getElementById('edit-form').addEventListener('submit', function(e) {
            <?php if ($record['form_type'] === 'DPCR'): ?>
            // For DPCR, compile the form data into JSON
            const formData = {
                entries: []
            };
            
            // Get all DPCR rows
            const rows = document.querySelectorAll('#dpcr-table-body tr');
            rows.forEach(row => {
                const inputs = row.querySelectorAll('input');
                formData.entries.push({
                    major_output: inputs[0].value,
                    success_indicators: inputs[1].value,
                    budget: inputs[2].value,
                    accountable: inputs[3].value,
                    accomplishments: inputs[4].value,
                    q1: inputs[5].value,
                    q2: inputs[6].value,
                    q3: inputs[7].value,
                    q4: inputs[8].value
                });
            });
            
            // Store the JSON in the hidden field
            document.getElementById('form-content-json').value = JSON.stringify(formData);
            <?php endif; ?>
        });
    });
</script>

<?php
// Close database connection
$conn->close();

// Include footer
include_once('includes/footer.php');
?> 