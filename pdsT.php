<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$page_title = "Personal Data Sheet (PDS)";

// Include header and sidebar (assuming these files exist in 'includes/')
include_once('includes/header.php');
include_once('includes/sidebar.php');

// Check user role
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'regular_employee' && $_SESSION['user_role'] !== 'department_head' && $_SESSION['user_role'] !== 'president' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: access_denied.php");
    exit();
}

// Database connection details
$host = "localhost";
$username = "root";
$password = "";
$database = "epms_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$pds_data_json = 'null'; // Stores the JSON data fetched from DB

// --- PHP: HANDLE FORM SUBMISSION (SAVE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pds_data'])) {
    $pds_data = $_POST['pds_data'];

    // List of all dynamic arrays that must be re-indexed and handled
    $dynamic_sections = [
        'children', 'educational_background', 'eligibility', 'work_experience', 
        'voluntary_work', 'learning_dev', 'other_skills', 
        'non_academic_distinctions', 'membership_in_assoc'
    ];

    // Clean and re-index dynamic arrays for clean JSON storage
    foreach ($dynamic_sections as $section) {
        if (isset($pds_data[$section]) && is_array($pds_data[$section])) {
            // Remove any empty array entries (from deleted rows) and re-index numerically (0, 1, 2...)
            $pds_data[$section] = array_values(array_filter($pds_data[$section]));
        } else {
            $pds_data[$section] = []; // Ensure section exists as an empty array if no data submitted
        }
    }

    // Convert the entire PHP array structure to a JSON string
    $pds_data_json_to_save = json_encode($pds_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    // Check if a PDS record already exists for this user
    $check_query = "SELECT id FROM pds_records WHERE user_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    try {
        if ($result->num_rows > 0) {
            // UPDATE existing record
            $update_query = "UPDATE pds_records SET pds_data = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $pds_data_json_to_save, $user_id);
        } else {
            // INSERT new record
            $insert_query = "INSERT INTO pds_records (user_id, pds_data) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("is", $user_id, $pds_data_json_to_save);
        }

        if ($stmt->execute()) {
            $message = "Personal Data Sheet (PDS) saved successfully!";
            $alert_type = "success";
        } else {
            $message = "Error saving PDS: " . $conn->error;
            $alert_type = "danger";
        }
    } catch (Exception $e) {
        $message = "An unexpected error occurred during save: " . $e->getMessage();
        $alert_type = "danger";
    }

    // After save, reload the data
    $pds_data_json = $pds_data_json_to_save;
}

// --- PHP: HANDLE INITIAL LOAD ---
$fetch_query = "SELECT pds_data FROM pds_records WHERE user_id = ?";
$stmt = $conn->prepare($fetch_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $record = $result->fetch_assoc();
    $pds_data_json = $record['pds_data'];
}

$conn->close();
?>

<div class="container-fluid py-4">
    <div class="card shadow-lg border-0 mb-5">
        <div class="card-header text-white p-3" style="background-color: #224221;">
            <h5 class="mb-0">Civil Service Personal Data Sheet (PDS)</h5>
            <p class="mb-0 text-sm">Please fill out all sections completely and accurately. Fields marked with <span class="text-warning">*</span> are required.</p>
        </div>
        <div class="card-body">

            <?php if (isset($message)): ?>
                <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form id="pdsForm" method="POST">
                <input type="hidden" name="action" value="save_pds">

                <!-- SECTION I: PERSONAL INFORMATION -->
                <h6 class="text-primary mt-4 mb-3 border-bottom pb-1 font-weight-bolder">I. Personal Information</h6>
                <div class="row g-3 border rounded p-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Surname <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="pds_data[personal_info][surname]" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="pds_data[personal_info][first_name]" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Middle Name</label>
                        <input type="text" class="form-control" name="pds_data[personal_info][middle_name]">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="pds_data[personal_info][dob]" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Place of Birth</label>
                        <input type="text" class="form-control" name="pds_data[personal_info][pob]">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sex <span class="text-danger">*</span></label>
                        <select class="form-select" name="pds_data[personal_info][sex]" required>
                            <option value="">Select...</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Civil Status <span class="text-danger">*</span></label>
                        <select class="form-select" name="pds_data[personal_info][civil_status]" required>
                            <option value="">Select...</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Separated">Separated</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Height (m)</label>
                        <input type="number" step="1" class="form-control" name="pds_data[personal_info][height]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Weight (kg)</label>
                        <input type="number" step="0.1" class="form-control" name="pds_data[personal_info][weight]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Blood Type</label>
                        <input type="text" class="form-control" name="pds_data[personal_info][blood_type]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">GSIS ID No.</label>
                        <input type="text" class="form-control" name="pds_data[personal_info][gsis_id]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">PAG-IBIG ID No.</label>
                        <input type="text" class="form-control" name="pds_data[personal_info][pagibig_id]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">PHILHEALTH No.</label>
                        <input type="text" class="form-control" name="pds_data[personal_info][philhealth_no]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">SSS No.</label>
                        <input type="text" class="form-control" name="pds_data[personal_info][sss_no]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">TIN No.</label>
                        <input type="text" class="form-control" name="pds_data[personal_info][tin_no]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Agency Employee No.</label>
                        <input type="text" class="form-control" name="pds_data[personal_info][agency_employee_no]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Agency Employee No.</label>
                        <input type="text" class="form-control" name="pds_data[personal_info][agency_employee_no]">
                    </div>
                    
                    <h6 class="text-secondary col-12 mt-4 mb-2">Address Information</h6>
                    
                    <div class="col-6">
                        <label class="form-label">Residential Address <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="pds_data[personal_info][residential_address]" rows="2" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">ZIP Code (Residential)</label>
                        <input type="text" class="form-control" name="pds_data[personal_info][res_zip_code]">
                    </div>
                    
                    <div class="col-6">
                        <label class="form-label">Permanent Address <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="pds_data[personal_info][permanent_address]" rows="2" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">ZIP Code (Permanent)</label>
                        <input type="text" class="form-control" name="pds_data[personal_info][perm_zip_code]">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Telephone No. (Residential)</label>
                        <input type="text" class="form-control" name="pds_data[personal_info][tel_no_res]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Mobile No. <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="pds_data[personal_info][mobile_no]" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="pds_data[personal_info][email_address]" required>
                    </div>
                    </div>
                </div>

                <!-- SECTION II: FAMILY BACKGROUND -->
                <h6 class="text-primary mt-5 mb-3 border-bottom pb-1 font-weight-bolder">II. Family Background</h6>
                <div class="row g-3 border rounded p-3 mb-4">
                    <!-- Spouse Info -->
                    <h6 class="text-secondary mt-2 mb-3">Spouse's Information (If Married)</h6>
                    <div class="col-md-4">
                        <label class="form-label">Surname</label>
                        <input type="text" class="form-control" name="pds_data[family_background][spouse_surname]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" name="pds_data[family_background][spouse_first_name]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Middle Name</label>
                        <input type="text" class="form-control" name="pds_data[family_background][spouse_middle_name]">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Occupation</label>
                        <input type="text" class="form-control" name="pds_data[family_background][spouse_occupation]">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Employer / Business Name</label>
                        <input type="text" class="form-control" name="pds_data[family_background][spouse_employer]">
                    </div>
                    
                    <!-- Parents Info -->
                    <h6 class="text-secondary mt-4 mb-3">Father's Information</h6>
                    <div class="col-md-4">
                        <label class="form-label">Surname <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="pds_data[family_background][father_surname]" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="pds_data[family_background][father_first_name]" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Middle Name</label>
                        <input type="text" class="form-control" name="pds_data[family_background][father_middle_name]">
                    </div>
                    <h6 class="text-secondary mt-4 mb-3">Mother's Information (Maiden Name)</h6>
                    <div class="col-md-4">
                        <label class="form-label">Surname <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="pds_data[family_background][mother_surname]" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="pds_data[family_background][mother_first_name]" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Middle Name</label>
                        <input type="text" class="form-control" name="pds_data[family_background][mother_middle_name]">
                    </div>

                    <!-- Children (Dynamic Table) -->
                    <h6 class="text-secondary mt-4 mb-3">Children (Write full name and date of birth)</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle" id="children_table">
                            <thead class="bg-light">
                                <tr class="text-center">
                                    <th style="width: 50%;">Full Name of Child</th>
                                    <th style="width: 40%;">Date of Birth</th>
                                    <th style="width: 10%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="children_table_body"></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-center">
                                        <button type="button" class="btn btn-sm btn-info w-50" onclick="addRow('children_table')">
                                            <i class="bi bi-plus-circle"></i> Add Child
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- SECTION III: EDUCATIONAL BACKGROUND (Dynamic Table) -->
                <h6 class="text-primary mt-5 mb-3 border-bottom pb-1 font-weight-bolder">III. Educational Background</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle" id="educational_background_table">
                        <thead class="bg-light">
                            <tr class="text-center">
                                <th style="width: 10%;">Level</th>
                                <th style="width: 25%;">Name of School</th>
                                <th style="width: 20%;">Basic Education/Degree/Course</th>
                                <th style="width: 10%;">Highest Level/Unit Earned</th>
                                <th style="width: 15%;">Period of Attendance</th>
                                <th style="width: 10%;">Graduated Year</th>
                                <th style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="educational_background_table_body"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7" class="text-center">
                                    <button type="button" class="btn btn-sm btn-success w-50" onclick="addRow('educational_background_table')">
                                        <i class="bi bi-plus-circle"></i> Add Educational Entry
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- SECTION IV: CIVIL SERVICE ELIGIBILITY (Dynamic Table) -->
                <h6 class="text-primary mt-5 mb-3 border-bottom pb-1 font-weight-bolder">IV. Civil Service Eligibility</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle" id="eligibility_table">
                        <thead class="bg-light">
                            <tr class="text-center">
                                <th style="width: 30%;">Career Service/Licensure</th>
                                <th style="width: 10%;">Rating</th>
                                <th style="width: 10%;">Date of Exam/Conferment</th>
                                <th style="width: 25%;">Place of Exam/Conferment</th>
                                <th style="width: 15%;">License No. / Date of Validity</th>
                                <th style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="eligibility_table_body"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" class="text-center">
                                    <button type="button" class="btn btn-sm btn-info w-50" onclick="addRow('eligibility_table')">
                                        <i class="bi bi-plus-circle"></i> Add Eligibility
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- SECTION V: WORK EXPERIENCE (Dynamic Table) -->
                <h6 class="text-primary mt-5 mb-3 border-bottom pb-1 font-weight-bolder">V. Work Experience</h6>
                <p class="text-muted text-sm">Inclusive Dates: From / To (Present, if currently employed)</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle" id="work_experience_table">
                        <thead class="bg-light">
                            <tr class="text-center">
                                <th style="width: 15%;">Inclusive Dates</th>
                                <th style="width: 25%;">Position Title</th>
                                <th style="width: 25%;">Department/Agency/Office/Company</th>
                                <th style="width: 10%;">Monthly Salary</th>
                                <th style="width: 10%;">Status of Appointment</th>
                                <th style="width: 5%;">Gov't Service</th>
                                <th style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="work_experience_table_body"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7" class="text-center">
                                    <button type="button" class="btn btn-sm btn-success w-50" onclick="addRow('work_experience_table')">
                                        <i class="bi bi-plus-circle"></i> Add Work Experience
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- SECTION VI: VOLUNTARY WORK, TRAINING, OTHER INFO -->
                <h6 class="text-primary mt-5 mb-3 border-bottom pb-1 font-weight-bolder">VI. Voluntary Work, Training & Other Information</h6>
                
                <!-- Voluntary Work (Dynamic Table) -->
                <h6 class="text-secondary mt-4 mb-3">Voluntary Work or Involvement in Civic/Non-Government/People/Voluntary Organization</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle" id="voluntary_work_table">
                        <thead class="bg-light">
                            <tr class="text-center">
                                <th style="width: 40%;">Name & Address of Organization</th>
                                <th style="width: 15%;">Inclusive Dates (From/To)</th>
                                <th style="width: 25%;">Number of Hours</th>
                                <th style="width: 10%;">Position/Nature of Work</th>
                                <th style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="voluntary_work_table_body"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-center">
                                    <button type="button" class="btn btn-sm btn-info w-50" onclick="addRow('voluntary_work_table')">
                                        <i class="bi bi-plus-circle"></i> Add Voluntary Work
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Learning and Development (Dynamic Table) -->
                <h6 class="text-secondary mt-4 mb-3">Learning and Development (L&D) Interventions/Training Programs Attended</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle" id="learning_dev_table">
                        <thead class="bg-light">
                            <tr class="text-center">
                                <th style="width: 30%;">Title of L&D Interventions</th>
                                <th style="width: 10%;">Inclusive Dates (From/To)</th>
                                <th style="width: 15%;">Number of Hours</th>
                                <th style="width: 35%;">Conducted/Sponsored By</th>
                                <th style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="learning_dev_table_body"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-center">
                                    <button type="button" class="btn btn-sm btn-success w-50" onclick="addRow('learning_dev_table')">
                                        <i class="bi bi-plus-circle"></i> Add Training
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Other Information (3 Dynamic Tables) -->
                <h6 class="text-secondary mt-4 mb-3">Other Information</h6>
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">A. Special Skills and Hobbies</label>
                        <table class="table table-bordered table-sm align-middle" id="other_skills_table">
                            <tbody id="other_skills_table_body"></tbody>
                            <tfoot>
                                <tr><td class="text-center"><button type="button" class="btn btn-sm btn-secondary w-75" onclick="addRow('other_skills_table')">Add Skill</button></td></tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">B. Non-Academic Distinctions</label>
                        <table class="table table-bordered table-sm align-middle" id="non_academic_distinctions_table">
                            <tbody id="non_academic_distinctions_table_body"></tbody>
                            <tfoot>
                                <tr><td class="text-center"><button type="button" class="btn btn-sm btn-secondary w-75" onclick="addRow('non_academic_distinctions_table')">Add Distinction</button></td></tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">C. Membership in Association/Organization</label>
                        <table class="table table-bordered table-sm align-middle" id="membership_in_assoc_table">
                            <tbody id="membership_in_assoc_table_body"></tbody>
                            <tfoot>
                                <tr><td class="text-center"><button type="button" class="btn btn-sm btn-secondary w-75" onclick="addRow('membership_in_assoc_table')">Add Membership</button></td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- SECTION VII: CONDITIONAL QUESTIONS -->
                <h6 class="text-primary mt-5 mb-3 border-bottom pb-1 font-weight-bolder">VII. Conditional Questions</h6>
                <div class="border rounded p-3 mb-4">

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">34. Are you related by consanguinity or affinity to the appointing/recommending authority?</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="pds_data[conditional][q34]" id="q34_yes" value="Yes" onclick="handleConditional('q34', 'Yes')" required>
                            <label class="form-check-label" for="q34_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="pds_data[conditional][q34]" id="q34_no" value="No" onclick="handleConditional('q34', 'No')" required>
                            <label class="form-check-label" for="q34_no">No</label>
                        </div>
                        <div id="q34_details" class="mt-2 p-2 border-start border-3 border-warning" style="display: none;">
                            <textarea class="form-control" name="pds_data[conditional][q34_details]" rows="2" placeholder="If YES, state the relationship and full name of the appointing/recommending official"></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">35. a. Are you a citizen of a foreign country?</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="pds_data[conditional][q35a]" id="q35a_yes" value="Yes" onclick="handleConditional('q35a', 'Yes')" required>
                            <label class="form-check-label" for="q35a_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="pds_data[conditional][q35a]" id="q35a_no" value="No" onclick="handleConditional('q35a', 'No')" required>
                            <label class="form-check-label" for="q35a_no">No</label>
                        </div>
                        <div id="q35a_details" class="mt-2 p-2 border-start border-3 border-warning" style="display: none;">
                            <label class="form-label">If YES, specify:</label>
                            <select class="form-select" name="pds_data[conditional][q35a_details]">
                                <option value="">Select...</option>
                                <option value="By Birth">By Birth</option>
                                <option value="By Naturalization">By Naturalization</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">35. b. Have you acquired the status of an immigrant or permanent resident of another country?</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="pds_data[conditional][q35b]" id="q35b_yes" value="Yes" onclick="handleConditional('q35b', 'Yes')" required>
                            <label class="form-check-label" for="q35b_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="pds_data[conditional][q35b]" id="q35b_no" value="No" onclick="handleConditional('q35b', 'No')" required>
                            <label class="form-check-label" for="q35b_no">No</label>
                        </div>
                        <div id="q35b_details" class="mt-2 p-2 border-start border-3 border-warning" style="display: none;">
                            <input type="text" class="form-control" name="pds_data[conditional][q35b_details]" placeholder="If YES, state country and date of acquisition">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">36. Have you ever been found guilty of any administrative offense?</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="pds_data[conditional][q36]" id="q36_yes" value="Yes" onclick="handleConditional('q36', 'Yes')" required>
                            <label class="form-check-label" for="q36_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="pds_data[conditional][q36]" id="q36_no" value="No" onclick="handleConditional('q36', 'No')" required>
                            <label class="form-check-label" for="q36_no">No</label>
                        </div>
                        <div id="q36_details" class="mt-2 p-2 border-start border-3 border-warning" style="display: none;">
                            <textarea class="form-control" name="pds_data[conditional][q36_details]" rows="2" placeholder="If YES, give particulars"></textarea>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">37. Have you been criminally charged before any court?</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="pds_data[conditional][q37]" id="q37_yes" value="Yes" onclick="handleConditional('q37', 'Yes')" required>
                            <label class="form-check-label" for="q37_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="pds_data[conditional][q37]" id="q37_no" value="No" onclick="handleConditional('q37', 'No')" required>
                            <label class="form-check-label" for="q37_no">No</label>
                        </div>
                        <div id="q37_details" class="mt-2 p-2 border-start border-3 border-warning" style="display: none;">
                            <textarea class="form-control" name="pds_data[conditional][q37_details]" rows="2" placeholder="If YES, give particulars"></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">38. Have you been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, or other means?</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="pds_data[conditional][q38]" id="q38_yes" value="Yes" onclick="handleConditional('q38', 'Yes')" required>
                            <label class="form-check-label" for="q38_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="pds_data[conditional][q38]" id="q38_no" value="No" onclick="handleConditional('q38', 'No')" required>
                            <label class="form-check-label" for="q38_no">No</label>
                        </div>
                        <div id="q38_details" class="mt-2 p-2 border-start border-3 border-warning" style="display: none;">
                            <input type="text" class="form-control" name="pds_data[conditional][q38_details]" placeholder="If YES, give particulars">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">39. Have you ever been a candidate in a national or local election, except for Barangay election?</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="pds_data[conditional][q39]" id="q39_yes" value="Yes" onclick="handleConditional('q39', 'Yes')" required>
                            <label class="form-check-label" for="q39_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="pds_data[conditional][q39]" id="q39_no" value="No" onclick="handleConditional('q39', 'No')" required>
                            <label class="form-check-label" for="q39_no">No</label>
                        </div>
                        <div id="q39_details" class="mt-2 p-2 border-start border-3 border-warning" style="display: none;">
                            <input type="text" class="form-control" name="pds_data[conditional][q39_details]" placeholder="If YES, give particulars">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">40. Have you acquired any business, commercial, or professional interest in any transaction with the Government?</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="pds_data[conditional][q40]" id="q40_yes" value="Yes" onclick="handleConditional('q40', 'Yes')" required>
                            <label class="form-check-label" for="q40_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="pds_data[conditional][q40]" id="q40_no" value="No" onclick="handleConditional('q40', 'No')" required>
                            <label class="form-check-label" for="q40_no">No</label>
                        </div>
                        <div id="q40_details" class="mt-2 p-2 border-start border-3 border-warning" style="display: none;">
                            <input type="text" class="form-control" name="pds_data[conditional][q40_details]" placeholder="If YES, give particulars">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">41. Lived abroad and/or member of any association/organization (e.g. NGO, PO, or any other group) that advocates violence or illegal activities?</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="pds_data[conditional][q41]" id="q41_yes" value="Yes" onclick="handleConditional('q41', 'Yes')" required>
                            <label class="form-check-label" for="q41_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="pds_data[conditional][q41]" id="q41_no" value="No" onclick="handleConditional('q41', 'No')" required>
                            <label class="form-check-label" for="q41_no">No</label>
                        </div>
                        <div id="q41_details" class="mt-2 p-2 border-start border-3 border-warning" style="display: none;">
                            <input type="text" class="form-control" name="pds_data[conditional][q41_details]" placeholder="If YES, give particulars">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">42. Are you a:</label>
                        <div class="row mt-2 g-2">
                            <div class="col-md-4">
                                <label class="form-label">a. Person with Disability (PWD)?</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="pds_data[conditional][q42a]" id="q42a_yes" value="Yes" onclick="handleConditional('q42a', 'Yes')" required>
                                    <label class="form-check-label" for="q42a_yes">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="pds_data[conditional][q42a]" id="q42a_no" value="No" onclick="handleConditional('q42a', 'No')" required>
                                    <label class="form-check-label" for="q42a_no">No</label>
                                </div>
                                <div id="q42a_details" class="mt-2 p-2 border-start border-3 border-warning" style="display: none;">
                                    <input type="text" class="form-control form-control-sm" name="pds_data[conditional][q42a_details]" placeholder="If YES, specify ID No.">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">b. Solo Parent?</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="pds_data[conditional][q42b]" id="q42b_yes" value="Yes" onclick="handleConditional('q42b', 'Yes')" required>
                                    <label class="form-check-label" for="q42b_yes">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="pds_data[conditional][q42b]" id="q42b_no" value="No" onclick="handleConditional('q42b', 'No')" required>
                                    <label class="form-check-label" for="q42b_no">No</label>
                                </div>
                                <div id="q42b_details" class="mt-2 p-2 border-start border-3 border-warning" style="display: none;">
                                    <input type="text" class="form-control form-control-sm" name="pds_data[conditional][q42b_details]" placeholder="If YES, specify ID No.">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">c. Member of an Indigenous Group?</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="pds_data[conditional][q42c]" id="q42c_yes" value="Yes" onclick="handleConditional('q42c', 'Yes')" required>
                                    <label class="form-check-label" for="q42c_yes">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="pds_data[conditional][q42c]" id="q42c_no" value="No" onclick="handleConditional('q42c', 'No')" required>
                                    <label class="form-check-label" for="q42c_no">No</label>
                                </div>
                                <div id="q42c_details" class="mt-2 p-2 border-start border-3 border-warning" style="display: none;">
                                    <input type="text" class="form-control form-control-sm" name="pds_data[conditional][q42c_details]" placeholder="If YES, specify group">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION VIII: REFERENCES -->
                <h6 class="text-primary mt-5 mb-3 border-bottom pb-1 font-weight-bolder">VIII. References</h6>
                <p class="text-muted text-sm">Please provide three (3) references not related by consanguinity or affinity to you.</p>
                <div class="row g-3 border rounded p-3 mb-4">
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                        <div class="col-md-4 mb-3">
                            <label class="form-label font-weight-bold">Reference <?= $i ?></label>
                            <input type="text" class="form-control form-control-sm mb-1" name="pds_data[references][<?= $i ?>][name]" placeholder="Full Name" required>
                            <input type="text" class="form-control form-control-sm mb-1" name="pds_data[references][<?= $i ?>][address]" placeholder="Address" required>
                            <input type="text" class="form-control form-control-sm" name="pds_data[references][<?= $i ?>][tel]" placeholder="Tel. No." required>
                        </div>
                    <?php endfor; ?>
                </div>

                <!-- SAVE BUTTON -->
                <!-- <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                        <i class="bi bi-save"></i> Save Complete Personal Data Sheet
                    </button>
                </div> -->
                <div class="col-12 text-center my-4">
                    <button type="submit" class="btn btn-success btn-lg me-3" name="save_pds">
                        <i class="bi bi-save"></i> Save PDS
                    </button>
                    
                    <!-- NEW PRINT BUTTON -->
                    <a href="print_pdsT.php?user_id=<?php echo htmlspecialchars($user_id); ?>" target="_blank" class="btn btn-primary btn-lg">
                        <i class="bi bi-printer"></i> Print PDS
                    </a>
                    
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // --- PDS JAVASCRIPT LOGIC ---

    // The raw JSON data passed from PHP.
    const pdsData = JSON.parse('<?php echo $pds_data_json; ?>');
    
    // Counters for dynamic rows to ensure unique array indices for the PHP backend
    let row_counters = {
        children: 0,
        educational_background: 0,
        eligibility: 0,
        work_experience: 0,
        voluntary_work: 0,
        learning_dev: 0,
        other_skills: 0,
        non_academic_distinctions: 0,
        membership_in_assoc: 0,
    };

    /**
     * Helper to get the next index for a dynamic section and increment the counter.
     */
    function getIndexAndIncrement(sectionKey) {
        const index = row_counters[sectionKey];
        row_counters[sectionKey]++;
        return index;
    }

    // HTML Templates for dynamic rows
    const templates = {
        'children_table': (index) => `
            <td><input type="text" class="form-control form-control-sm" name="pds_data[children][${index}][name]" required></td>
            <td><input type="date" class="form-control form-control-sm" name="pds_data[children][${index}][dob]" required></td>
            <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>
        `,
        'educational_background_table': (index) => `
            <td>
                <select class="form-select form-select-sm" name="pds_data[educational_background][${index}][level]" required>
                    <option value="">Select</option>
                    <option value="Elementary">Elementary</option>
                    <option value="Secondary">Secondary</option>
                    <option value="Vocational">Vocational</option>
                    <option value="College">College</option>
                    <option value="Graduate Studies">Graduate Studies</option>
                </select>
            </td>
            <td><input type="text" class="form-control form-control-sm" name="pds_data[educational_background][${index}][school]" required></td>
            <td><input type="text" class="form-control form-control-sm" name="pds_data[educational_background][${index}][course]" required></td>
            <td><input type="text" class="form-control form-control-sm" name="pds_data[educational_background][${index}][highest_level]"></td>
            <td>
                <input type="text" class="form-control form-control-sm" name="pds_data[educational_background][${index}][period_from]" placeholder="From Year">
                <input type="text" class="form-control form-control-sm mt-1" name="pds_data[educational_background][${index}][period_to]" placeholder="To Year">
            </td>
            <td><input type="text" class="form-control form-control-sm" name="pds_data[educational_background][${index}][graduated_year]"></td>
            <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>
        `,
        'eligibility_table': (index) => `
            <td><input type="text" class="form-control form-control-sm" name="pds_data[eligibility][${index}][career_service]" required></td>
            <td><input type="text" class="form-control form-control-sm" name="pds_data[eligibility][${index}][rating]"></td>
            <td><input type="date" class="form-control form-control-sm" name="pds_data[eligibility][${index}][date_of_exam]"></td>
            <td><input type="text" class="form-control form-control-sm" name="pds_data[eligibility][${index}][place_of_exam]"></td>
            <td>
                <input type="text" class="form-control form-control-sm" name="pds_data[eligibility][${index}][license_no]" placeholder="License No.">
                <input type="date" class="form-control form-control-sm mt-1" name="pds_data[eligibility][${index}][date_of_validity]" placeholder="Date of Validity">
            </td>
            <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>
        `,
        'work_experience_table': (index) => `
            <td>
                <input type="date" class="form-control form-control-sm" name="pds_data[work_experience][${index}][from_date]" placeholder="From Date" required>
                <input type="date" class="form-control form-control-sm mt-1" name="pds_data[work_experience][${index}][to_date]" placeholder="To Date">
            </td>
            <td><input type="text" class="form-control form-control-sm" name="pds_data[work_experience][${index}][position_title]" required></td>
            <td><input type="text" class="form-control form-control-sm" name="pds_data[work_experience][${index}][company]" required></td>
            <td><input type="number" step="1" class="form-control form-control-sm" name="pds_data[work_experience][${index}][monthly_salary]"></td>
            <td><input type="text" class="form-control form-control-sm" name="pds_data[work_experience][${index}][status_of_appointment]"></td>
            <td>
                <select class="form-select form-select-sm" name="pds_data[work_experience][${index}][is_govt_service]">
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
            </td>
            <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>
        `,
        'voluntary_work_table': (index) => `
            <td><input type="text" class="form-control form-control-sm" name="pds_data[voluntary_work][${index}][name_address]" required></td>
            <td>
                <input type="date" class="form-control form-control-sm" name="pds_data[voluntary_work][${index}][from_date]" placeholder="From Date">
                <input type="date" class="form-control form-control-sm mt-1" name="pds_data[voluntary_work][${index}][to_date]" placeholder="To Date">
            </td>
            <td><input type="number" class="form-control form-control-sm" name="pds_data[voluntary_work][${index}][num_hours]"></td>
            <td><input type="text" class="form-control form-control-sm" name="pds_data[voluntary_work][${index}][position]"></td>
            <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>
        `,
        'learning_dev_table': (index) => `
            <td><input type="text" class="form-control form-control-sm" name="pds_data[learning_dev][${index}][title]" required></td>
            <td>
                <input type="date" class="form-control form-control-sm" name="pds_data[learning_dev][${index}][from_date]" placeholder="From Date">
                <input type="date" class="form-control form-control-sm mt-1" name="pds_data[learning_dev][${index}][to_date]" placeholder="To Date">
            </td>
            <td><input type="number" class="form-control form-control-sm" name="pds_data[learning_dev][${index}][num_hours]"></td>
            <td><input type="text" class="form-control form-control-sm" name="pds_data[learning_dev][${index}][sponsored_by]"></td>
            <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>
        `,
        'other_skills_table': (index) => `
            <td><div class="input-group"><input type="text" class="form-control form-control-sm" name="pds_data[other_skills][${index}][skill]"><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="bi bi-x"></i></button></div></td>
        `,
        'non_academic_distinctions_table': (index) => `
            <td><div class="input-group"><input type="text" class="form-control form-control-sm" name="pds_data[non_academic_distinctions][${index}][distinction]"><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="bi bi-x"></i></button></div></td>
        `,
        'membership_in_assoc_table': (index) => `
            <td><div class="input-group"><input type="text" class="form-control form-control-sm" name="pds_data[membership_in_assoc][${index}][membership]"><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)"><i class="bi bi-x"></i></button></div></td>
        `,
    };

    /**
     * Adds a new row to a specified dynamic table.
     * @param {string} tableId - The ID of the table (e.g., 'children_table').
     * @param {object} [data={}] - Optional data object to populate the row inputs with.
     */
    function addRow(tableId, data = {}) {
        const tableBody = document.getElementById(`${tableId}_body`);
        if (!tableBody || !templates[tableId]) return;

        // Extract section key from tableId (e.g., 'children_table' -> 'children')
        const sectionKey = tableId.replace('_table', '');

        // Get the next unique array index and increment the counter
        const index = getIndexAndIncrement(sectionKey); 

        const newRow = tableBody.insertRow(-1);
        newRow.innerHTML = templates[tableId](index);

        // Populate the new row if data is provided (used during load)
        if (Object.keys(data).length > 0) {
            // Give a slight delay to ensure elements are fully added to the DOM
            setTimeout(() => {
                // Select inputs belonging to this specific indexed row
                const inputs = newRow.querySelectorAll(`[name^="pds_data[${sectionKey}][${index}]"]`);
                
                inputs.forEach(input => {
                    // Extract the field name (e.g., 'name', 'dob', 'level')
                    const nameParts = input.name.match(/\[(\w+)\]$/);
                    const fieldName = nameParts ? nameParts[1] : null;

                    if (fieldName && data.hasOwnProperty(fieldName)) {
                        input.value = data[fieldName] || '';
                    }
                });
            }, 0);
        }
    }

    /**
     * Removes the nearest <tr> ancestor of the button clicked.
     */
    function removeRow(btn) {
        const row = btn.closest('tr');
        if (row) {
            row.remove();
        }
    }

    /**
     * Toggles visibility and required attribute for conditional fields (Q34-Q40).
     */
    function handleConditional(questionBaseName, value) {
        
        const detailsId = `${questionBaseName}_details`;
        const details = document.getElementById(detailsId);
        
        if (details) {
            const show = value === 'Yes';
            details.style.display = show ? 'block' : 'none';

            const inputs = details.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (show) {
                    // Make the input required when the 'Yes' option is selected
                    input.setAttribute('required', 'required');
                } else {
                    // Remove required and clear the value when 'No' is selected/when hiding
                    input.removeAttribute('required');
                    if (input.type !== 'radio' && input.type !== 'checkbox') {
                        input.value = ''; 
                    }
                }
            });
        }
    }



    /**
     * Populates the PDS form from the JSON data fetched from the database.
     */
    function loadPDS() {
        // Define all sections (static and dynamic) to ensure initialization
        const allSections = {
            static: ['personal_info', 'family_background', 'conditional', 'references'],
            dynamic: {
                children: 'children_table', 
                educational_background: 'educational_background_table',
                eligibility: 'eligibility_table',
                work_experience: 'work_experience_table',
                voluntary_work: 'voluntary_work_table',
                learning_dev: 'learning_dev_table',
                other_skills: 'other_skills_table',
                non_academic_distinctions: 'non_academic_distinctions_table',
                membership_in_assoc: 'membership_in_assoc_table'
            }
        };

        if (!pdsData || Object.keys(pdsData).length === 0) {
            console.log("No existing PDS data found to load. Initializing dynamic tables.");
            // Initialize one empty row for each main dynamic section for usability
            Object.values(allSections.dynamic).forEach(tableId => {
                 addRow(tableId);
            });
            return;
        }

        console.log("Loading PDS data:", pdsData);

        // 1. Populate static fields
        allSections.static.forEach(sectionKey => {
            const sectionData = pdsData[sectionKey];
            if (sectionData) {
                for (const fieldKey in sectionData) {
                    const inputName = `pds_data[${sectionKey}][${fieldKey}]`;
                    const inputElement = document.querySelector(`[name="${inputName}"]`);
                    
                    if (inputElement) {
                        if (inputElement.type === 'radio') {
                             // Handle radio buttons (especially for conditional fields)
                            if (inputElement.value === sectionData[fieldKey]) {
                                inputElement.checked = true;
                                if (sectionKey === 'conditional') {
                                    handleConditional(inputName, inputElement.value);
                                }
                            }
                        } else {
                            inputElement.value = sectionData[fieldKey] || '';
                        }
                    }
                }
            }
        });

        // 2. Populate dynamic tables
        Object.entries(allSections.dynamic).forEach(([sectionKey, tableId]) => {
            const sectionData = pdsData[sectionKey];
            if (sectionData && Array.isArray(sectionData) && sectionData.length > 0) {
                // Clear the default row (if any) and load existing data
                const tableBody = document.getElementById(`${tableId}_body`);
                if (tableBody) tableBody.innerHTML = '';
                
                sectionData.forEach(item => {
                    addRow(tableId, item);
                });
            } else {
                // If data is empty or missing, add one blank row
                addRow(tableId);
            }
        });
    }
    
    // Load the data when the document is ready
    document.addEventListener('DOMContentLoaded', loadPDS);
</script>

<?php
// Include footer
include_once('includes/footer.php');
?>
