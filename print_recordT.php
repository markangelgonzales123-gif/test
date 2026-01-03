<?php
// Include session management
session_start();

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
$host = "localhost";
$username = "root";
$password = "";
$database = "epms_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
    die("Record not found!");
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
    die("You don't have permission to view this record!");
}

function getPDSValue($data, $key, $default = '') {
    return htmlspecialchars($data[$key] ?? $default);
}
function getPDSCheck($data, $key, $value) {
    return ($data[$key] ?? '') === $value ? '☑' : '☐';
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
    $content = json_decode($record['content'], true);

    if ($content !== null && is_array($content)) {
        // Extract the entries directly from the decoded content
        $strategic_entries = $content['strategic_functions'] ?? [];
        $core_entries = $content['core_functions'] ?? [];
        $support_entries = $content['support_functions'] ?? [];
    } else {
        // Handle case where JSON decoding failed or 'content' is malformed
        $strategic_entries = [];
        $core_entries = [];
        $support_entries = [];
        // Optionally add error logging here
    }
    
    $entries = [
        'strategic' => $strategic_entries,
        'core' => $core_entries,
        'support' => $support_entries
    ];
} else if ($record['form_type'] === 'IDP') {
    $entries_query = "SELECT * FROM idp_entries WHERE record_id = ? ORDER BY id";
    $stmt = $conn->prepare($entries_query);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $entries_result = $stmt->get_result();
    
    $idp_entries = [];
    
    while ($entry = $entries_result->fetch_assoc()) {
        $idp_entries[] = $entry;
    }
    
    $entries = [
        'idp' => $idp_entries
    ];
}
$is_dept_head_ipcr_view = ($record['form_type'] === 'IPCR' && $user_role === 'department_head');
// $title = $record['form_type'] === 'PDS' ? 'PERSONAL DATA SHEET' : htmlspecialchars($record['form_type']) . ' Record';
// $page_title = $title . ' - ' . htmlspecialchars($record['employee_name']);
// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print <?php echo $record['form_type']; ?> - <?php echo htmlspecialchars($record['employee_name']); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #fff;
        }
        .print-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .print-header img {
            max-width: 80px;
            height: auto;
        }
        .record-info {
            margin-bottom: 20px;
        }
        .record-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .record-info td {
            padding: 5px 10px;
        }
        .record-info .label {
            font-weight: bold;
            width: 150px;
        }
        .section-title {
            background-color: #f0f0f0;
            padding: 10px;
            margin-top: 20px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table.data-table th,
        table.data-table td {
            border: 1px solid #ddd;
            padding: 8px;
            vertical-align: top;
        }
        table.data-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: left;
        }
        .signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-block {
            width: 45%;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 5px;
            text-align: center;
        }
        .footnote {
            margin-top: 50px;
            font-size: 0.8em;
            text-align: center;
        }
        <?php if ($form_type === 'PDS'): ?>
        
        .pds-page {
            page-break-after: always; /* Force a new page after each PDS section */
            box-sizing: border-box;
            padding: 0;
            width: 100%;
            border-collapse: collapse;
        }

        .pds-page:last-child {
            page-break-after: avoid; /* Don't break after the last page */
        }
        
        .pds-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            table-layout: fixed; /* Helps in precise column sizing */
        }

        .pds-table td, .pds-table th {
            border: 1px solid #000;
            padding: 1px 3px;
            vertical-align: top;
            line-height: 1.2;
            height: 12px; /* Standard row height for compact form */
        }
        
        .pds-table th {
            text-align: left;
            font-weight: bold;
            background-color: #e0e0e0;
            padding: 2px 3px;
        }

        .pds-header {
            text-align: right;
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .pds-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .section-header {
            font-weight: bold;
            background-color: #ccc;
            text-align: center;
            padding: 3px;
            height: 15px !important;
        }
        
        .field-label {
            font-style: italic;
            vertical-align: middle !important;
            font-size: 7pt;
            padding: 0 3px !important;
            border-right: none !important;
            width: 30%; /* Standardize label width */
        }
        
        .field-data {
            vertical-align: middle !important;
            font-weight: bold;
            border-left: 1px solid #000 !important; /* Always ensure separation */
            width: 70%;
            padding: 0 3px !important;
        }
        
        .data-only {
            font-weight: bold;
            padding: 0 3px !important;
        }

        .centered-data {
            text-align: center;
        }

        .small-text {
            font-size: 6pt;
            text-align: center;
            padding: 0 !important;
            height: 6px;
        }
        
        /* For the long lists like Work Experience or L&D */
        .multi-row-table td {
            height: 12px;
        }
        
        /* Watermark and Page Number for all PDS pages */
        .pds-page-number {
            position: absolute;
            bottom: 10mm;
            right: 10mm;
            font-size: 8pt;
            font-weight: bold;
        }
        
        .watermark {
            color: #d0d0d0;
            font-size: 15pt;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            white-space: nowrap;
            opacity: 0.5;
            pointer-events: none;
        }

        /* Adjustments for screen view */
        @media screen {
            .pds-page { border: 1px dashed #ccc; margin-bottom: 10px; }
        }
        
        /* Reset table styles for nested tables where full border is not needed */
        .pds-table table {
            border: none;
            table-layout: auto;
        }
        .pds-table table td, .pds-table table th {
            border: none;
        }
        <?php else: ?>
        /* --- IPCR / DPCR / IDP SPECIFIC STYLES (Existing) --- */
        
        .main-title {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
        }
        
        .header-info, .section-header {
            margin-bottom: 15px;
            border: 1px solid #000;
            padding: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10pt;
        }

        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 50px;
            page-break-before: avoid;
        }

        .signature-block {
            width: 45%;
            text-align: center;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .footnote {
            margin-top: 50px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            font-size: 9pt;
            text-align: center;
            page-break-before: avoid;
        }
        <?php endif; ?>
        
        /* Print-specific CSS */
        @media print {
            body {
                padding: 0;
                margin: 0.5in;
                background-color: #fff;
            }
            
            .no-print, 
            .sidebar, 
            .main-content > *:not(#print-container) {
                display: none !important;
            }
            
            #print-container {
                display: block !important;
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
            }
            
            .print-header, 
            .record-info, 
            .section-title, 
            .data-table, 
            .signatures, 
            .footnote {
                page-break-inside: avoid;
            }
            
            @page {
                size: letter landscape; /* ADDED 'landscape' */
                margin: 0.5in;
            }
        }
        
        /* Controls visibility on screen vs print */
        .print-only {
            display: none;
        }
        
        @media print {
            .print-only {
                display: block;
            }
            
            .screen-only {
                display: none;
            }
        }

        /* DPCR Layout Specific Styles */
        .dpcr-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .dpcr-period {
            font-size: 10pt;
            margin-bottom: 20px;
        }
        .dpcr-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 10pt;
        }
        .dpcr-header-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            vertical-align: top;
        }
        .dpcr-header-table .signature-cell {
            height: 50px; /* Space for signature */
        }
        .dpcr-header-table .align-center {
            text-align: center;
        }
        .dpcr-rating-key-cell {
            width: 300px;
            padding: 0 !important;
        }
        .dpcr-rating-key-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        .dpcr-rating-key-table td {
            border: none;
            padding: 1px 3px;
        }
        
        /* DPCR Main Table Styles */
        .dpcr-data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 9pt; /* Smaller font for more compact table */
            table-layout: fixed; /* Ensures column widths are respected */
        }
        .dpcr-data-table th,
        .dpcr-data-table td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: middle; /* Center vertically */
            line-height: 1.2;
        }
        .dpcr-data-table th {
            background-color: #d0d0d0;
            font-weight: bold;
            text-align: center;
        }
        .dpcr-data-table .col-mfo { width: 15%; }
        .dpcr-data-table .col-indicators { width: 25%; }
        .dpcr-data-table .col-budget { width: 10%; }
        .dpcr-data-table .col-accountable { width: 15%; }
        .dpcr-data-table .col-accomplishments { width: 15%; }
        .dpcr-data-table .col-q { width: 5%; text-align: center; } /* Q1, E2, T3, A4 */
        .dpcr-data-table .col-remarks { width: 10%; }
    </style>
    
    <!-- Additional stylesheet for print media only -->
    <style media="print">
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        /* Hide everything except our print container */
        body > *:not(#print-container) {
            display: none !important;
        }
        
        #print-container {
            display: block !important;
            width: 100%;
            padding: 0;
            margin: 0;
        }
    </style>
</head>
<body>
    <!-- Screen-only controls -->
    <div class="screen-only no-print mb-3">
        <button class="btn btn-primary" onclick="window.print()">Print</button>
        <a href="view_record.php?id=<?php echo $record_id; ?>" class="btn btn-secondary">Back</a>
    </div>
    
    <!-- Print container that will be the only thing visible when printing -->
    <div id="print-container">
        <div class="print-header">
            <img src="images/CCA.jpg" alt="City College of Angeles Logo">
            <h4>CITY COLLEGE OF ANGELES</h4>
            <h5>Employee Performance Management System</h5>
            <h3><?php echo $record['form_type']; ?></h3>
        </div>
        
        <!-- <div class="record-info">
            <table>
                <tr>
                    <td class="label">Employee:</td>
                    <td><?php echo htmlspecialchars($record['employee_name']); ?></td>
                </tr>
                <tr>
                    <td class="label">Department:</td>
                    <td><?php echo htmlspecialchars($record['department_name'] ?? 'Not Assigned'); ?></td>
                </tr>
                <tr>
                    <td class="label">Period:</td>
                    <td><?php echo htmlspecialchars($record['period']); ?></td>
                </tr>
                <tr>
                    <td class="label">Status:</td>
                    <td><?php echo $record['status']; ?></td>
                </tr>
                <?php if ($record['date_submitted']): ?>
                <tr>
                    <td class="label">Date Submitted:</td>
                    <td><?php echo date('F d, Y', strtotime($record['date_submitted'])); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($record['reviewed_by']): ?>
                <tr>
                    <td class="label">Reviewed By:</td>
                    <td><?php echo htmlspecialchars($record['reviewer_name']); ?></td>
                </tr>
                <tr>
                    <td class="label">Date Reviewed:</td>
                    <td><?php echo date('F d, Y', strtotime($record['date_reviewed'])); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div> -->
        
        <?php if (!empty($record['comments'])): ?>
        <div class="comments mb-4">
            <div class="section-title">Comments/Feedback</div>
            <p><?php echo nl2br(htmlspecialchars($record['comments'])); ?></p>
        </div>
        <?php endif; ?>
        
        <!-- DPCR Form Content -->
        <?php if ($record['form_type'] === 'DPCR'): ?>
            <div style="text-align: center; margin-bottom: 10px;">
                <h3 class="dpcr-title">DEPARTMENT PERFORMANCE COMMITMENT AND REVIEW (DPCR)</h3>
                <div class="dpcr-period">
                    <span style="border-bottom: 1px solid #000; padding: 0 50px;">
                        <?php 
                            // Attempt to format the period from the database
                            // Assuming $record['period'] is like 'YYYY-MM-DD to YYYY-MM-DD' or similar
                            $period_parts = explode(' to ', $record['period']);
                            if (count($period_parts) === 2) {
                                $start_date = date('F j', strtotime($period_parts[0]));
                                $end_date = date('F j, Y', strtotime($period_parts[1]));
                                echo htmlspecialchars($start_date . ' to ' . $end_date);
                            } else {
                                echo htmlspecialchars($record['period']);
                            }
                        ?>
                    </span>
                    commit to deliver and agree to be rated on the attainment of the following targets in accordance with the indicated measures for the period
                </div>
            </div>
            
            <table class="dpcr-header-table">
                <tr>
                    <td style="width: 25%;" class="align-center signature-cell" colspan="2">
                        Approved by:
                        <div style="height: 20px;"></div>
                        <div style="border-bottom: 1px solid #000; margin: 0 10px;">
                            <?php 
                                // Placeholder for City Mayor/Head of Agency Name
                                echo '____________________'; 
                            ?>
                        </div>
                        <small>City Mayor / Head of Agency</small>
                    </td>
                    <td style="width: 50%;" rowspan="2" class="dpcr-rating-key-cell">
                        <table class="dpcr-rating-key-table">
                            <tr><td>5 - OUTSTANDING</td></tr>
                            <tr><td>4 - VERY SATISFACTORY</td></tr>
                            <tr><td>3 - SATISFACTORY</td></tr>
                            <tr><td>2 - UNSATISFACTORY</td></tr>
                            <tr><td>1 - POOR</td></tr>
                        </table>
                    </td>
                    <td style="width: 25%;" class="align-center signature-cell">
                        Date
                        <div style="height: 20px;"></div>
                        <div style="border-bottom: 1px solid #000; margin: 0 10px;">
                            <?php 
                                // Placeholder for Date
                                echo date('F d, Y', strtotime($record['date_submitted'] ?? $record['created_at'] ?? 'now'));
                            ?>
                        </div>
                    </td>
                </tr>
            </table>
            
            <table class="dpcr-data-table">
                <thead>
                    <tr>
                        <th class="col-mfo" rowspan="2">MAJOR FINAL OUTPUT/PAP</th>
                        <th class="col-indicators" rowspan="2">SUCCESS INDICATORS (Targets + Measures)</th>
                        <th class="col-budget" rowspan="2">ALLOTTED BUDGET</th>
                        <th class="col-accountable" rowspan="2">DIVISIONS/INDIVIDUALS ACCOUNTABLE</th>
                        <th class="col-accomplishments" rowspan="2">ACTUAL ACCOMPLISHMENTS</th>
                        <th colspan="4">RATING</th>
                        <th class="col-remarks" rowspan="2">REMARKS</th>
                    </tr>
                    <tr>
                        <th class="col-q">Q1</th>
                        <th class="col-q">Q2</th>
                        <th class="col-q">T3</th>
                        <th class="col-q">A4</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $current_section = '';
                    $section_count = 0;
                    
                    // Combine Strategic and Core entries for unified table display
                    $combined_entries = array_merge($entries['strategic'], $entries['core']);
                    
                    if (empty($combined_entries)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center;">No DPCR outputs defined</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($combined_entries as $entry): ?>
                        <?php 
                            // Check for new section (Strategic, Core, Support - though Support is not in your image)
                            if ($entry['category'] !== $current_section) {
                                $current_section = $entry['category'];
                                $section_count++;
                                $section_title = $current_section . ($section_count === 1 ? ' (45%)' : ' (55%)'); // Adjust percentages as needed
                        ?>
                            <tr>
                                <td colspan="10" style="font-weight: bold; background-color: #f0f0f0;"><?php echo htmlspecialchars($section_title); ?></td>
                            </tr>
                        <?php
                            }
                        ?>
                        <tr>
                            <td class="col-mfo"><?php echo nl2br(htmlspecialchars($entry['major_output'])); ?></td>
                            <td class="col-indicators"><?php echo nl2br(htmlspecialchars($entry['success_indicators'])); ?></td>
                            <td class="col-budget" style="text-align: right;"><?php echo htmlspecialchars($entry['budget'] ? number_format($entry['budget'], 2) : 'N/A'); ?></td>
                            <td class="col-accountable"><?php echo htmlspecialchars($entry['accountable']); ?></td>
                            <td class="col-accomplishments"><?php echo nl2br(htmlspecialchars($entry['actual_accomplishments'] ?? '')); ?></td>
                            <td class="col-q"><?php echo htmlspecialchars($entry['q_rating'] ?? ''); ?></td>
                            <td class="col-q"><?php echo htmlspecialchars($entry['e_rating'] ?? ''); ?></td>
                            <td class="col-q"><?php echo htmlspecialchars($entry['t_rating'] ?? ''); ?></td>
                            <td class="col-q"><?php echo htmlspecialchars($entry['a_rating'] ?? ''); ?></td>
                            <td class="col-remarks"><?php echo htmlspecialchars($entry['remarks'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <!-- IPCR Form Content -->
        <?php if ($record['form_type'] === 'IPCR'): 
            $content = json_decode($record['content'], true) ?? [];
            $strategic_functions = $content['strategic_functions'] ?? [];
            $core_functions = $content['core_functions'] ?? [];
            $support_functions = $content['support_functions'] ?? [];
        ?>
            
            <div style="text-align: center; margin-bottom: 20px;">
                <h3 style="margin-bottom: 5px;">INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR)</h3>
                <h4 style="margin-top: 0;">SELF RATING FORM</h4>
            </div>
            
            <table style="width: 100%; border: none; margin-bottom: 15px; font-size: 10pt;">
                <tr>
                    <td style="border: none; padding: 1px 0;">
                        **Name of Employee:** <?php echo htmlspecialchars($record['employee_name']); ?>
                    </td>
                    <td style="border: none; padding: 1px 0;">
                        **Position:** <?php echo htmlspecialchars($record['employee_position'] ?? 'N/A'); ?>
                    </td>
                    <td style="border: none; padding: 1px 0;">
                        **Office:** <?php echo htmlspecialchars($record['department_name'] ?? 'N/A'); ?>
                    </td>
                </tr>
                <tr>
                    <td style="border: none; padding: 1px 0;" colspan="3">
                        **Immediate Supervisor:** <?php echo htmlspecialchars($record['reviewer_name'] ?? '____________________'); ?>
                    </td>
                </tr>
            </table>

            <table class="data-table" style="table-layout: fixed;">
                <thead>
                    <tr>
                        <th width="15%" rowspan="2">Output</th>
                        <th width="20%" rowspan="2">Success Indicator (Target + Measure)</th>
                        <th width="25%" rowspan="2">Actual Accomplishments</th>
                        <th width="20%" colspan="4" style="text-align: center;">Self-Rating</th>
                        <th width="20%" colspan="4" style="text-align: center;">Supervisor's Rating</th>
                        <th width="20%" rowspan="2">Remarks</th>
                    </tr>
                    <tr>
                        <th width="5%" style="text-align: center;">Q</th>
                        <th width="5%" style="text-align: center;">E</th>
                        <th width="5%" style="text-align: center;">T</th>
                        <th width="5%" style="text-align: center;">A</th>
                        <th width="5%" style="text-align: center;">Q</th>
                        <th width="5%" style="text-align: center;">E</th>
                        <th width="5%" style="text-align: center;">T</th>
                        <th width="5%" style="text-align: center;">A</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    function render_ipcr_entries($entries, $section_title) {
                        if (empty($entries)) {
                            echo '<tr><td colspan="11" style="font-style: italic;">No ' . strtolower($section_title) . ' functions defined</td></tr>';
                            return;
                        }
                        echo '<tr><td colspan="11" style="font-weight: bold; background-color: #f0f0f0;">' . htmlspecialchars($section_title) . '</td></tr>';
                        foreach ($entries as $entry) {
                            echo '<tr>';
                            echo '<td>' . nl2br(htmlspecialchars($entry['mfo'])) . '</td>';
                            echo '<td>' . nl2br(htmlspecialchars($entry['success_indicators'])) . '</td>';
                            echo '<td>' . nl2br(htmlspecialchars($entry['accomplishments'] ?? '')) . '</td>';
                            echo '<td style="text-align: center;">' . htmlspecialchars($entry['q'] ?? '') . '</td>';
                            echo '<td style="text-align: center;">' . htmlspecialchars($entry['e'] ?? '') . '</td>';
                            echo '<td style="text-align: center;">' . htmlspecialchars($entry['t'] ?? '') . '</td>';
                            echo '<td style="text-align: center; font-weight: bold;">' . htmlspecialchars($entry['a'] ?? '') . '</td>';
                            echo '<td style="text-align: center;">' . htmlspecialchars($entry['supervisor_q'] ?? '') . '</td>';
                            echo '<td style="text-align: center;">' . htmlspecialchars($entry['supervisor_e'] ?? '') . '</td>';
                            echo '<td style="text-align: center;">' . htmlspecialchars($entry['supervisor_t'] ?? '') . '</td>';
                            echo '<td style="text-align: center; font-weight: bold;">' . htmlspecialchars($entry['supervisor_a'] ?? '') . '</td>';
                            echo '<td>' . nl2br(htmlspecialchars($entry['remarks'] ?? '')) . '</td>';
                            echo '</tr>';
                        }
                    }

                    render_ipcr_entries($strategic_functions, 'STRATEGIC FUNCTIONS');
                    render_ipcr_entries($core_functions, 'CORE FUNCTIONS');
                    render_ipcr_entries($support_functions, 'SUPPORT FUNCTIONS');
                    ?>
                    
                    <tr>
                        <td colspan="6" style="text-align: right; font-weight: bold; border-left: none; border-bottom: none;">Total Strategic Function</td>
                        <td style="text-align: center; font-weight: bold;"><?php echo htmlspecialchars($content['strategic_average'] ?? ''); ?></td>
                        <td colspan="3" style="border: none;"></td>
                        <td style="text-align: center; font-weight: bold;"><?php echo htmlspecialchars($content['supervisor_strategic_average'] ?? ''); ?></td>
                        <td style="border-right: none;"></td>
                    </tr>
                    <tr>
                        <td colspan="6" style="text-align: right; font-weight: bold; border-left: none; border-bottom: none;">Total Core Function</td>
                        <td style="text-align: center; font-weight: bold;"><?php echo htmlspecialchars($content['core_average'] ?? ''); ?></td>
                        <td colspan="3" style="border: none;"></td>
                        <td style="text-align: center; font-weight: bold;"><?php echo htmlspecialchars($content['supervisor_core_average'] ?? ''); ?></td>
                        <td style="border-right: none;"></td>
                    </tr>
                    <tr>
                        <td colspan="6" style="text-align: right; font-weight: bold; border-left: none; border-bottom: none;">Total Support Function</td>
                        <td style="text-align: center; font-weight: bold;"><?php echo htmlspecialchars($content['support_average'] ?? ''); ?></td>
                        <td colspan="3" style="border: none;"></td>
                        <td style="text-align: center; font-weight: bold;"><?php echo htmlspecialchars($content['supervisor_support_average'] ?? ''); ?></td>
                        <td style="border-right: none;"></td>
                    </tr>
                    <tr>
                        <td colspan="6" style="text-align: right; font-weight: bold;">Final Average Rating</td>
                        <td style="text-align: center; font-weight: bold;"><?php echo htmlspecialchars($content['final_rating'] ?? ''); ?></td>
                        <td colspan="3" style="text-align: right; font-weight: bold;">Final Average Rating</td>
                        <td style="text-align: center; font-weight: bold;"><?php echo htmlspecialchars($content['supervisor_final_rating'] ?? ''); ?></td>
                        <td style="font-weight: bold;"><?php echo htmlspecialchars($content['supervisor_rating_interpretation'] ?? ''); ?></td>
                    </tr>
                </tbody>
            </table>

            <div style="border: 1px solid #000; padding: 5px; margin-bottom: 20px;">
                **Comments and Recommendations for Development Purposes**<br>
                <?php echo nl2br(htmlspecialchars($record['comments'] ?? '')); ?>
            </div>
            
            <table style="width: 100%; border: none; margin-top: 50px; font-size: 10pt;">
                <tr>
                    <td style="width: 50%; border: none; padding: 10px 0;">
                        I hereby certify that I agree with the ratings of my immediate supervisor.
                        <div style="margin-top: 30px; text-align: center; border-bottom: 1px solid #000; width: 80%; margin-left: 10%;">
                            <?php echo htmlspecialchars($record['employee_name']); ?>
                        </div>
                        <div style="text-align: center; width: 80%; margin-left: 10%;">Employee</div>
                    </td>
                    <td style="width: 50%; border: none; padding: 10px 0;">
                        Assessed by:
                        <div style="margin-top: 30px; text-align: center; border-bottom: 1px solid #000; width: 80%; margin-left: 10%;">
                            <?php echo htmlspecialchars($record['reviewer_name'] ?? '____________________'); ?>
                        </div>
                        <div style="text-align: center; width: 80%; margin-left: 10%;">Supervisor</div>
                    </td>
                </tr>
            </table>
            
            <table style="width: 100%; border: none; margin-top: 20px; font-size: 9pt;">
                <tr>
                    <td style="width: 50%; border: none;">
                        **Legend** *1 - Quantity* &nbsp;&nbsp;&nbsp; *2 - Efficiency* &nbsp;&nbsp;&nbsp; *3 - Timeliness* &nbsp;&nbsp;&nbsp; *4 - Average*
                    </td>
                    <td style="width: 50%; border: none; text-align: right;">
                        **Date:** <?php echo date('F d, Y'); ?>
                    </td>
                </tr>
            </table>

        <?php endif; ?>
        
        <!-- IDP Form Content -->
        <?php if ($record['form_type'] === 'IDP'): ?>
            <div class="section-title">Individual Development Plan</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="15%">Development Needs</th>
                        <th width="20%">Development Interventions</th>
                        <th width="10%">Target Competency Level</th>
                        <th width="20%">Success Indicators</th>
                        <th width="15%">Timeline</th>
                        <th width="10%">Resources Needed</th>
                        <th width="10%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entries['idp'])): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No development plans defined</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($entries['idp'] as $entry): ?>
                        <tr>
                            <td><?php echo nl2br(htmlspecialchars($entry['development_needs'])); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($entry['development_interventions'])); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($entry['target_competency_level']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($entry['success_indicators'])); ?></td>
                            <td>
                                <?php if ($entry['timeline_start'] && $entry['timeline_end']): ?>
                                    <?php echo date('M d, Y', strtotime($entry['timeline_start'])); ?> to 
                                    <?php echo date('M d, Y', strtotime($entry['timeline_end'])); ?>
                                <?php else: ?>
                                    Not specified
                                <?php endif; ?>
                            </td>
                            <td><?php echo nl2br(htmlspecialchars($entry['resources_needed'] ?? 'None')); ?></td>
                            <td><?php echo $entry['status']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div class="signatures">
            <div class="signature-block">
                <div class="signature-line">
                    <?php echo htmlspecialchars($record['employee_name']); ?><br>
                    <small>Employee</small>
                </div>
            </div>
            
            <div class="signature-block">
                <div class="signature-line">
                    <?php echo htmlspecialchars($record['reviewer_name'] ?? '____________________'); ?><br>
                    <small>Reviewer</small>
                </div>
            </div>
        </div>
        
        <div class="footnote">
            <p>This document is generated by the City College of Angeles Employee Performance Management System.</p>
            <p>Printed on <?php echo date('F d, Y'); ?></p>
        </div>
    </div>
    
    <script>
        // Automatically focus the print container when page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('print-container').focus();
        });
    </script>
</body>
</html> 