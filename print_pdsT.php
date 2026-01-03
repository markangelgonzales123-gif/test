<?php
// Include session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$host = "localhost";
$username = "root";
// NOTE: I am using '' as the password based on other provided snippets, adjust if necessary.
$password = ""; 
$database = "epms_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the user ID for the PDS to be printed. 
// It will use the ID passed via the URL, or default to the current user's ID.
$target_user_id = $_GET['user_id'] ?? $_SESSION['user_id'] ?? null;

if (empty($target_user_id)) {
    die("Error: User ID not specified for printing PDS.");
}

$pds_data = null;
$employee_name = 'N/A';
$department_name = 'N/A';

// Prepare the query to fetch the PDS data
$pds_query = "SELECT u.name AS employee_name, d.name AS department_name, pr.pds_data
              FROM pds_records pr
              JOIN users u ON pr.user_id = u.id
              LEFT JOIN departments d ON u.department_id = d.id
              WHERE pr.user_id = ?";

$stmt = $conn->prepare($pds_query);

if ($stmt === false) {
    error_log("SQL Prepare Error in print_pdsT.php: " . $conn->error);
    die("Database Error: Could not prepare the PDS data query. Please check SQL syntax and table names in the query.");
}

$stmt->bind_param("i", $target_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $employee_name_full = htmlspecialchars($row['employee_name']); // Full name from 'users' table
    $department_name = htmlspecialchars($row['department_name']);
    $pds_data_json = $row['pds_data'];
    $pds_data = json_decode($pds_data_json, true);
    
    // Construct employee name from PDS data if available, otherwise use full name from 'users'
    $pds_surname = $pds_data['personal_info']['surname'] ?? '';
    $pds_first_name = $pds_data['personal_info']['first_name'] ?? '';
    $pds_middle_name = $pds_data['personal_info']['middle_name'] ?? '';
    
    // Prefer PDS name structure for display if filled out
    if ($pds_surname || $pds_first_name) {
        // This is primarily for the top header display. The table below handles the name components.
        $employee_name = trim("$pds_surname, $pds_first_name $pds_middle_name");
    } else {
         $employee_name = $employee_name_full;
    }
    
} else {
    // Fallback to fetch employee name even if PDS data is missing
    $employee_query = "SELECT name FROM users WHERE id = ?";
    $e_stmt = $conn->prepare($employee_query);
    if ($e_stmt && $e_stmt->bind_param("i", $target_user_id) && $e_stmt->execute()) {
        $e_result = $e_stmt->get_result();
        $e_row = $e_result->fetch_assoc();
        if ($e_row) {
             $employee_name = htmlspecialchars($e_row['name']);
        }
    }
    $pds_data = []; // Initialize as empty array
}

$stmt->close();
$conn->close();

/**
 * Helper function to safely get a value from a specific section of the PDS data.
 * IMPORTANT: This version handles combined names for family members (father, mother, spouse)
 * using the separate surname/first_name/middle_name fields stored in pdsT.php.
 * * @param string $section The main section key (e.g., 'personal_info').
 * @param string $field The field key within the section (e.g., 'dob').
 * @param array $pdsData The entire PDS data array.
 * @return string The escaped value or 'N/A'.
 */
function getPDSValue($section, $field, $pdsData) {
    // Handle combined names for Family Background Section II
    if ($section === 'family_background' && in_array($field, ['spouse_name', 'father_name', 'mother_name'])) {
        $prefix = str_replace('_name', '', $field); // 'spouse', 'father', 'mother'
        $surname = $pdsData[$section]["{$prefix}_surname"] ?? '';
        $first_name = $pdsData[$section]["{$prefix}_first_name"] ?? '';
        $middle_name = $pdsData[$section]["{$prefix}_middle_name"] ?? '';
        // Format: Surname, First Name Middle Name
        $full_name = trim(($surname ? $surname . ', ' : '') . $first_name . ' ' . $middle_name);
        return htmlspecialchars($full_name) ?: 'N/A';
    }
    
    // Handle combined date of birth for children
    if ($section === 'children' && $field === 'name_and_dob') {
        // This case is no longer needed as children are handled by renderDynamicTable.
        return 'N/A';
    }

    // Standard field retrieval
    return htmlspecialchars($pdsData[$section][$field] ?? 'N/A');
}

/**
 * Function to render a dynamic table section based on pdsT.php structure.
 * @param string $title Display title for the section.
 * @param string $sectionKey The key used in pdsT.php to store the dynamic array (e.g., 'children').
 * @param array $pdsData The entire PDS data array.
 * @param array $columns An associative array: [data_key => 'Column Header'].
 */
function renderDynamicTable($title, $sectionKey, $pdsData, $columns) {
    // The main key in pdsT.php is used as $sectionKey
    $data = $pdsData[$sectionKey] ?? [];
    echo "<h3 class=\"section-title\">" . htmlspecialchars($title) . "</h3>";
    echo "<table class=\"table table-bordered table-sm pds-table\">";
    
    // Table Header
    echo "<thead><tr>";
    // Check if the table needs a combined column for 'Action' like other info tables
    $is_simple_list = in_array($sectionKey, ['other_skills', 'non_academic_distinctions', 'membership_in_assoc']);
    
    foreach ($columns as $header) {
        echo "<th>" . htmlspecialchars($header) . "</th>";
    }
    echo "</tr></thead>";
    
    // Table Body
    echo "<tbody>";
    if (!empty($data) && is_array($data)) {
        foreach ($data as $row) {
            echo "<tr>";
            foreach ($columns as $fieldKey => $header) {
                $value = $row[$fieldKey] ?? 'N/A';
                
                // --- CUSTOM LOGIC FOR COMBINED FIELDS BASED ON pdsT.php INPUTS ---
                
                // Children: Combine name and dob for simplified print view (optional, but clean)
                if ($sectionKey === 'children' && $fieldKey === 'name_and_dob') {
                    $name = $row['name'] ?? 'N/A';
                    $dob = $row['dob'] ?? 'N/A';
                    $value = "<strong>Name:</strong> " . $name . "<br><strong>DOB:</strong> " . $dob;
                    
                // Education: Combine period_from and period_to
                } elseif ($sectionKey === 'educational_background' && $fieldKey === 'period_of_attendance') {
                     $from = $row['period_from'] ?? 'N/A';
                     $to = $row['period_to'] ?? 'Present';
                     $value = $from . ' - ' . $to;

                // Work Exp/Voluntary Work/L&D: Combine from_date and to_date
                } elseif (in_array($sectionKey, ['work_experience', 'voluntary_work', 'learning_dev']) && $fieldKey === 'inclusive_dates') {
                    $from = $row['from_date'] ?? 'N/A';
                    $to = $row['to_date'] ?? 'Present';
                    $value = $from . ' - ' . $to;
                    
                // Eligibility: Combine license_no and date_of_validity
                } elseif ($sectionKey === 'eligibility' && $fieldKey === 'license_details') {
                    $license_no = $row['license_no'] ?? 'N/A';
                    $date_of_validity = $row['date_of_validity'] ?? 'N/A';
                    $value = "<strong>License No:</strong> " . $license_no . "<br><strong>Validity:</strong> " . $date_of_validity;

                // Other Info (Skills, Distinctions, Membership): Use the correct single-field key
                } elseif ($is_simple_list) {
                    if (isset($row['skill'])) {
                        $value = $row['skill'];
                    } elseif (isset($row['distinction'])) {
                        $value = $row['distinction'];
                    } elseif (isset($row['membership'])) {
                        $value = $row['membership'];
                    }
                }
                
                echo "<td>" . nl2br(htmlspecialchars($value)) . "</td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan=\"" . count($columns) . "\">No entries recorded.</td></tr>";
    }
    echo "</tbody>";
    echo "</table>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDS Print - <?php echo $employee_name; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 0;
            padding: 0;
            color: #000;
        }
        .print-container {
            width: 8.5in; /* Letter size width */
            margin: 0 auto;
            padding: 0.5in;
        }
        @media print {
            .print-container {
                margin: 0;
                padding: 0;
            }
            body {
                font-size: 9pt;
            }
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 16pt;
            margin-bottom: 5px;
        }
        .header h2 {
            font-size: 12pt;
            font-weight: normal;
        }
        .pds-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .pds-table th, .pds-table td {
            padding: 4px;
            vertical-align: top;
            border: 1px solid #000;
        }
        .pds-table th {
            background-color: #f0f0f0;
            text-align: left;
            font-weight: bold;
        }
        .section-title {
            font-size: 12pt;
            margin-top: 15px;
            margin-bottom: 5px;
            padding: 5px 0;
            border-bottom: 2px solid #000;
        }
        .info-row td {
            padding: 8px 4px;
        }
    </style>
</head>
<body onload="window.print()">
    <div class="print-container">
        <div class="header">
            <h1>PERSONAL DATA SHEET (PDS)</h1>
            <h2>Employee Performance Management System</h2>
            <p><strong>Employee:</strong> <?php echo $employee_name; ?></p>
            <p><strong>Department:</strong> <?php echo $department_name; ?></p>
        </div>

        <?php if (!empty($pds_data)): ?>

        <h3 class="section-title">I. Personal Information</h3>
        <table class="table table-bordered table-sm pds-table">
            <tr>
                <th style="width: 25%;">Name</th>
                <td colspan="3"><?php echo getPDSValue('personal_info', 'surname', $pds_data) . ', ' . getPDSValue('personal_info', 'first_name', $pds_data) . ' ' . getPDSValue('personal_info', 'middle_name', $pds_data); ?></td>
            </tr>
            <tr>
                <th style="width: 25%;">Date of Birth</th>
                <td style="width: 25%;"><?php echo getPDSValue('personal_info', 'dob', $pds_data); ?></td>
                <th style="width: 25%;">Sex</th>
                <td style="width: 25%;"><?php echo getPDSValue('personal_info', 'sex', $pds_data); ?></td>
            </tr>
            <tr>
                <th>Place of Birth</th>
                <td><?php echo getPDSValue('personal_info', 'pob', $pds_data); ?></td>
                <th>Civil Status</th>
                <td><?php echo getPDSValue('personal_info', 'civil_status', $pds_data); ?></td>
            </tr>
            <tr>
                <th>Height (m)</th>
                <td><?php echo getPDSValue('personal_info', 'height', $pds_data); ?></td>
                <th>Weight (kg)</th>
                <td><?php echo getPDSValue('personal_info', 'weight', $pds_data); ?></td>
            </tr>
            <tr>
                <th>Blood Type</th>
                <td><?php echo getPDSValue('personal_info', 'blood_type', $pds_data); ?></td>
                <th>Agency Employee No.</th>
                <td><?php echo getPDSValue('personal_info', 'agency_employee_no', $pds_data); ?></td>
            </tr>
            <tr>
                <th>GSIS ID No.</th>
                <td><?php echo getPDSValue('personal_info', 'gsis_id', $pds_data); ?></td>
                <th>PAG-IBIG ID No.</th>
                <td><?php echo getPDSValue('personal_info', 'pagibig_id', $pds_data); ?></td>
            </tr>
            <tr>
                <th>PHILHEALTH No.</th>
                <td><?php echo getPDSValue('personal_info', 'philhealth_no', $pds_data); ?></td>
                <th>SSS No.</th>
                <td><?php echo getPDSValue('personal_info', 'sss_no', $pds_data); ?></td>
            </tr>
            <tr>
                <th>TIN No.</th>
                <td><?php echo getPDSValue('personal_info', 'tin_no', $pds_data); ?></td>
                <th>Telephone No. (Res)</th>
                <td><?php echo getPDSValue('personal_info', 'tel_no_res', $pds_data); ?></td>
            </tr>
            
            <tr class="info-row">
                <th colspan="4" style="text-align: center; background-color: #ddd;">ADDRESS AND CONTACT DETAILS</th>
            </tr>
            <tr>
                <th>Residential Address</th>
                <td colspan="1"><?php echo getPDSValue('personal_info', 'residential_address', $pds_data); ?></td>
                <th>ZIP Code</th>
                <td><?php echo getPDSValue('personal_info', 'res_zip_code', $pds_data); ?></td>
            </tr>
            <tr>
                <th>Permanent Address</th>
                <td colspan="1"><?php echo getPDSValue('personal_info', 'permanent_address', $pds_data); ?></td>
                <th>ZIP Code</th>
                <td><?php echo getPDSValue('personal_info', 'perm_zip_code', $pds_data); ?></td>
            </tr>
            <tr>
                <th>Mobile No.</th>
                <td><?php echo getPDSValue('personal_info', 'mobile_no', $pds_data); ?></td>
                <th>Email Address</th>
                <td colspan="1"><?php echo getPDSValue('personal_info', 'email_address', $pds_data); ?></td>
            </tr>
            
        </table>

        <h3 class="section-title">II. Family Background</h3>
        <table class="table table-bordered table-sm pds-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Relationship</th>
                    <th>Name (Surname, First Name, Middle Name)</th>
                    <th style="width: 30%;">Occupation/Employer/Business</th>
                </tr>
            </thead>
            <tbody>
                <tr class="info-row">
                    <td>Spouse</td>
                    <td><?php echo getPDSValue('family_background', 'spouse_name', $pds_data); ?></td>
                    <td><?php echo getPDSValue('family_background', 'spouse_occupation', $pds_data) . ' / ' . getPDSValue('family_background', 'spouse_employer', $pds_data); ?></td>
                </tr>
                <tr class="info-row">
                    <td>Father</td>
                    <td><?php echo getPDSValue('family_background', 'father_name', $pds_data); ?></td>
                    <td>N/A</td> </tr>
                <tr class="info-row">
                    <td>Mother (Maiden Name)</td>
                    <td><?php echo getPDSValue('family_background', 'mother_name', $pds_data); ?></td>
                    <td>N/A</td> </tr>
            </tbody>
        </table>

        <?php 
        // --- Dynamic Table Definitions (Matching pdsT.php keys) ---
        
        $children_cols = [
            'name' => 'Name of Child', 
            'dob' => 'Date of Birth (mm/dd/yyyy)' 
        ];
        renderDynamicTable('Children', 'children', $pds_data, $children_cols);

        $education_cols = [
            'level' => 'Level',
            'school' => 'Name of School',
            'course' => 'Basic Education/Course',
            'highest_level' => 'Highest Level/Units Earned',
            'period_of_attendance' => 'Attendance (From - To)', // Combined
            'graduated_year' => 'Year Graduated',
        ];
        // Remove 'period_to' and 'period_from' as they are combined in 'period_of_attendance' in the render function.
        renderDynamicTable('Educational Background', 'educational_background', $pds_data, $education_cols);
        
        $eligibility_cols = [
            'career_service' => 'Career Service / Licensure', 
            'rating' => 'Rating',
            'date_of_exam' => 'Date of Examination/Conferment',
            'place_of_exam' => 'Place of Examination/Conferment',
            'license_details' => 'License No. / Date of Validity', // Combined
        ];
        // Remove 'license_no' and 'date_of_validity' as they are combined in 'license_details' in the render function.
        renderDynamicTable('Civil Service Eligibility', 'eligibility', $pds_data, $eligibility_cols);

        $work_exp_cols = [
            'inclusive_dates' => 'Inclusive Dates (From - To)', // Combined
            'position_title' => 'Position Title',
            'company' => 'Department/Agency/Office/Company',
            'monthly_salary' => 'Monthly Salary',
            'status_of_appointment' => 'Status of Appointment',
            'is_govt_service' => 'Gov\'t Service (Y/N)'
        ];
        // Remove 'from_date' and 'to_date' as they are combined in 'inclusive_dates' in the render function.
        renderDynamicTable('Work Experience', 'work_experience', $pds_data, $work_exp_cols);

        $voluntary_work_cols = [
            'name_address' => 'Name and Address of Organization', 
            'inclusive_dates' => 'Inclusive Dates (From - To)', // Combined
            'num_hours' => 'Number of Hours',
            'position' => 'Position/Nature of Work'
        ];
        // Remove 'from_date' and 'to_date' as they are combined in 'inclusive_dates' in the render function.
        renderDynamicTable('Voluntary Work or Involvement in Civic / Non-Government / People / Voluntary Organization', 'voluntary_work', $pds_data, $voluntary_work_cols);
        
        $training_cols = [
            'title' => 'Title of Training/Seminar', 
            'inclusive_dates' => 'Inclusive Dates (From - To)', // Combined
            'num_hours' => 'Number of Hours',
            'sponsored_by' => 'Conducted / Sponsored By'
        ];
        // Remove 'from_date' and 'to_date' as they are combined in 'inclusive_dates' in the render function.
        renderDynamicTable('Learning and Development Interventions / Training Programs Attended', 'learning_dev', $pds_data, $training_cols);

        $other_skills_cols = [
            'skill' => 'Special Skills / Hobbies',
        ];
        renderDynamicTable('A. Special Skills and Hobbies', 'other_skills', $pds_data, $other_skills_cols);
        
        $non_academic_cols = [
            'distinction' => 'Non-Academic Distinctions / Recognition',
        ];
        renderDynamicTable('B. Non-Academic Distinctions / Recognition', 'non_academic_distinctions', $pds_data, $non_academic_cols);
        
        $membership_cols = [
            'membership' => 'Membership in Association / Organization',
        ];
        renderDynamicTable('C. Membership in Association / Organization', 'membership_in_assoc', $pds_data, $membership_cols);


        $conditional_questions = [
            'q34' => '34. Are you related by consanguinity or affinity to the appointing/recommending authority?',
            'q35a' => '35. a. Are you a citizen of a foreign country?',
            'q36' => '36. Are you a member of any indigenous group?',
            'q37' => '37. Are you a person with disability (PWD)?',
            'q38' => '38. Are you a Solo Parent?',
            'q39' => '39. Have you ever been found guilty of any administrative offense?',
            'q40' => '40. Have you been charged with any offense or crime?'
        ];
        ?>

        <h3 class="section-title">VII. Conditional Questions</h3>
        <table class="table table-bordered table-sm pds-table">
            
            <tr>
                <td class="question-col" style="width: 70%;">
                    <span class="font-weight-bold">34. Are you related by consanguinity or affinity to the appointing/recommending authority?</span>
                </td>
                <td class="answer-col" style="width: 15%; text-align: center;">
                    <?php echo getPDSValue('conditional', 'q34', $pds_data); ?>
                </td>
                <td class="details-col" style="width: 15%; font-size: 0.75rem;">
                    <?php echo (getPDSValue('conditional', 'q34', $pds_data) == 'Yes' ? 'Details: ' . getPDSValue('conditional', 'q34_details', $pds_data) : ''); ?>
                </td>
            </tr>

            <tr>
                <td class="question-col">
                    <span class="font-weight-bold">35. a. Are you a citizen of a foreign country?</span>
                </td>
                <td class="answer-col" style="text-align: center;">
                    <?php echo getPDSValue('conditional', 'q35a', $pds_data); ?>
                </td>
                <td class="details-col" style="font-size: 0.75rem;">
                    <?php echo (getPDSValue('conditional', 'q35a', $pds_data) == 'Yes' ? 'Specify: ' . getPDSValue('conditional', 'q35a_details', $pds_data) : ''); ?>
                </td>
            </tr>

            <tr>
                <td class="question-col">
                    <span class="font-weight-bold">35. b. Have you acquired the status of an immigrant or permanent resident of another country?</span>
                </td>
                <td class="answer-col" style="text-align: center;">
                    <?php echo getPDSValue('conditional', 'q35b', $pds_data); ?>
                </td>
                <td class="details-col" style="font-size: 0.75rem;">
                    <?php echo (getPDSValue('conditional', 'q35b', $pds_data) == 'Yes' ? 'Details: ' . getPDSValue('conditional', 'q35b_details', $pds_data) : ''); ?>
                </td>
            </tr>

            <tr>
                <td class="question-col">
                    <span class="font-weight-bold">36. Have you ever been found guilty of any administrative offense?</span>
                </td>
                <td class="answer-col" style="text-align: center;">
                    <?php echo getPDSValue('conditional', 'q36', $pds_data); ?>
                </td>
                <td class="details-col" style="font-size: 0.75rem;">
                    <?php echo (getPDSValue('conditional', 'q36', $pds_data) == 'Yes' ? 'Details: ' . getPDSValue('conditional', 'q36_details', $pds_data) : ''); ?>
                </td>
            </tr>

            <tr>
                <td class="question-col">
                    <span class="font-weight-bold">37. Have you been criminally charged before any court?</span>
                </td>
                <td class="answer-col" style="text-align: center;">
                    <?php echo getPDSValue('conditional', 'q37', $pds_data); ?>
                </td>
                <td class="details-col" style="font-size: 0.75rem;">
                    <?php echo (getPDSValue('conditional', 'q37', $pds_data) == 'Yes' ? 'Details: ' . getPDSValue('conditional', 'q37_details', $pds_data) : ''); ?>
                </td>
            </tr>
            
            <tr>
                <td class="question-col">
                    <span class="font-weight-bold">38. Have you been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, or other means?</span>
                </td>
                <td class="answer-col" style="text-align: center;">
                    <?php echo getPDSValue('conditional', 'q38', $pds_data); ?>
                </td>
                <td class="details-col" style="font-size: 0.75rem;">
                    <?php echo (getPDSValue('conditional', 'q38', $pds_data) == 'Yes' ? 'Details: ' . getPDSValue('conditional', 'q38_details', $pds_data) : ''); ?>
                </td>
            </tr>

            <tr>
                <td class="question-col">
                    <span class="font-weight-bold">39. Have you ever been a candidate in a national or local election, except for Barangay election?</span>
                </td>
                <td class="answer-col" style="text-align: center;">
                    <?php echo getPDSValue('conditional', 'q39', $pds_data); ?>
                </td>
                <td class="details-col" style="font-size: 0.75rem;">
                    <?php echo (getPDSValue('conditional', 'q39', $pds_data) == 'Yes' ? 'Details: ' . getPDSValue('conditional', 'q39_details', $pds_data) : ''); ?>
                </td>
            </tr>
            
            <tr>
                <td class="question-col">
                    <span class="font-weight-bold">40. Have you acquired any business, commercial, or professional interest in any transaction with the Government?</span>
                </td>
                <td class="answer-col" style="text-align: center;">
                    <?php echo getPDSValue('conditional', 'q40', $pds_data); ?>
                </td>
                <td class="details-col" style="font-size: 0.75rem;">
                    <?php echo (getPDSValue('conditional', 'q40', $pds_data) == 'Yes' ? 'Details: ' . getPDSValue('conditional', 'q40_details', $pds_data) : ''); ?>
                </td>
            </tr>

            <tr>
                <td class="question-col">
                    <span class="font-weight-bold">41. Lived abroad and/or member of any association/organization (e.g. NGO, PO, or any other group) that advocates violence or illegal activities?</span>
                </td>
                <td class="answer-col" style="text-align: center;">
                    <?php echo getPDSValue('conditional', 'q41', $pds_data); ?>
                </td>
                <td class="details-col" style="font-size: 0.75rem;">
                    <?php echo (getPDSValue('conditional', 'q41', $pds_data) == 'Yes' ? 'Details: ' . getPDSValue('conditional', 'q41_details', $pds_data) : ''); ?>
                </td>
            </tr>

            <tr>
                <td class="question-col">
                    <span class="font-weight-bold">42. Are you a:</span>
                </td>
                <td colspan="2"></td>
            </tr>
            <tr>
                <td class="question-col" style="padding-left: 20px;">
                    a. Person with Disability (PWD)?
                </td>
                <td class="answer-col" style="text-align: center;">
                    <?php echo getPDSValue('conditional', 'q42a', $pds_data); ?>
                </td>
                <td class="details-col" style="font-size: 0.75rem;">
                    <?php echo (getPDSValue('conditional', 'q42a', $pds_data) == 'Yes' ? 'ID No.: ' . getPDSValue('conditional', 'q42a_details', $pds_data) : ''); ?>
                </td>
            </tr>
            <tr>
                <td class="question-col" style="padding-left: 20px;">
                    b. Solo Parent?
                </td>
                <td class="answer-col" style="text-align: center;">
                    <?php echo getPDSValue('conditional', 'q42b', $pds_data); ?>
                </td>
                <td class="details-col" style="font-size: 0.75rem;">
                    <?php echo (getPDSValue('conditional', 'q42b', $pds_data) == 'Yes' ? 'ID No.: ' . getPDSValue('conditional', 'q42b_details', $pds_data) : ''); ?>
                </td>
            </tr>
            <tr>
                <td class="question-col" style="padding-left: 20px;">
                    c. Member of an Indigenous Group?
                </td>
                <td class="answer-col" style="text-align: center;">
                    <?php echo getPDSValue('conditional', 'q42c', $pds_data); ?>
                </td>
                <td class="details-col" style="font-size: 0.75rem;">
                    <?php echo (getPDSValue('conditional', 'q42c', $pds_data) == 'Yes' ? 'Group: ' . getPDSValue('conditional', 'q42c_details', $pds_data) : ''); ?>
                </td>
            </tr>

        </table>

        <h3 class="section-title">VIII. References</h3>
        <table class="table table-bordered table-sm pds-table">
            <thead>
                <tr>
                    <th style="width: 33%;">Name</th>
                    <th style="width: 33%;">Address</th>
                    <th style="width: 34%;">Telephone No.</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // References are stored under 'references' as a numeric array (1, 2, 3) in pdsT.php
                $references = $pds_data['references'] ?? [];
                for ($i = 1; $i <= 3; $i++): 
                    $ref = $references[$i] ?? ['name' => 'N/A', 'address' => 'N/A', 'tel' => 'N/A'];
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($ref['name']); ?></td>
                    <td><?php echo htmlspecialchars($ref['address']); ?></td>
                    <td><?php echo htmlspecialchars($ref['tel']); ?></td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>


        <div style="margin-top: 40px;">
            <p>I declare under oath that I have personally accomplished this Personal Data Sheet which is a true, correct and complete statement pursuant to the provisions of pertinent laws, rules and regulations of the Republic of the Philippines.</p>
            <div style="text-align: right; margin-top: 50px;">
                <div style="display: inline-block; border-top: 1px solid #000; padding-top: 5px; width: 300px; text-align: center;">
                    Signature over Printed Name
                </div>
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <div style="display: inline-block; border-top: 1px solid #000; padding-top: 5px; width: 300px; text-align: center;">
                    Date Accomplished: N/A (Field missing in pdsT.php)
                </div>
            </div>
        </div>

        <?php else: ?>
            <div class="alert alert-warning mt-4" role="alert">
                No Personal Data Sheet (PDS) data found for this user.
            </div>
        <?php endif; ?>

    </div>
    
    <script>
        // Use a short delay before printing to ensure the DOM is fully rendered
        setTimeout(function() {
            window.print();
        }, 500);
    </script>
</body>
</html>