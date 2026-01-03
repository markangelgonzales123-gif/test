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

$dept_head_name = 'N/A';
$dept_head_query = "SELECT name FROM users WHERE department_id = ? AND role = 'department_head' LIMIT 1";
$stmt = $conn->prepare($dept_head_query);
$stmt->bind_param("i", $record['department_id']);
$stmt->execute();
$dept_head_result = $stmt->get_result();
if ($row = $dept_head_result->fetch_assoc()) {
    $dept_head_name = $row['name'];
}
$stmt->close();

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
    $content = json_decode($record['content'], true);
    $idp_goals = $content['idp_goals'] ?? [];
    // ---------------------------------------------------------


    // 1. Get the first entry from the array (index 0)
    // Ensure the array is not empty before trying to access index 0
    if (!empty($idp_goals)) {
        $first_goal = $idp_goals[0];

        // 2. Assign the specific fields to variables using array indexing
        $objective_entry = $first_goal['objective'];
        $action_plan_entry = $first_goal['action_plan'];
        $target_date_entry = $first_goal['target_date']; // Renamed from 'target' for accuracy to your JSON
        
    } else {
        echo "No IDP goals found in the content.";
    }
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
        /* REMOVE: .record-info - NO LONGER USED FOR IPCR */
        /* REMOVE: .record-info table - NO LONGER USED FOR IPCR */
        /* REMOVE: .record-info td - NO LONGER USED FOR IPCR */
        /* REMOVE: .record-info .label - NO LONGER USED FOR IPCR */
        
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
                /* Reset margin to 0 for print, let @page handle it */
                margin: 0; 
                background-color: #fff;
            }
            
            /* Hide everything not in the print-container on print */
            .no-print, 
            .sidebar, 
            body > *:not(#print-container) { 
                display: none !important;
            }
            
            #print-container {
                display: block !important;
                position: relative; /* Changed from absolute for better flow */
                width: 100%;
                margin: 0.5in; /* Apply page margins here for content */
                box-sizing: border-box;
            }
            .print-container.idp-portrait {
                /* Specific dimensions for A4 portrait layout */
                /* max-width: 210mm; 
                min-height: 297mm;
                width: 210mm; */
            }
            
            /* Ensure the main table and its rows can break across pages */
            .data-table {
                page-break-inside: auto;
            }
            .data-table tr {
                page-break-inside: avoid; /* Keep rows together */
                page-break-after: auto;
            }
            
            /* Keep header and footer sections together */
            .ipcr-header,
            .ipcr-footer-section {
                page-break-inside: avoid;
            }

            @page {
                size: <?php echo ($form_type === 'IDP' ? 'letter portrait' : 'letter landscape'); ?> ; 
                margin: 0.5in;
            }
            .print-container.idp-portrait {
                /* Specific dimensions for A4 portrait layout */
                /* max-width: 210mm; 
                min-height: 297mm;
                width: 210mm; */
            }
            /* Header Styling */
            .header-info {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 5px;
                border-bottom: 2px solid #333; /* Separator */
            }
            .header-info img {
                width: 70px; 
                height: auto;
                margin-right: 20px;
            }
            .header-text {
                text-align: center;
                flex-grow: 1;
            }
            .header-text h4, .header-text h3 {
                margin: 0;
                line-height: 1.2;
            }
            .header-text h4 { font-size: 12pt; }
            .header-text h3 { font-size: 16pt; margin-top: 5px; }

            /* Employee Details Box */
            .employee-details {
                margin-bottom: 20px;
                border: 1px solid #000;
                padding: 10px;
            }
            .employee-details p {
                margin-bottom: 5px;
                margin-top: 0;
            }
            
            /* Table Styling */
            .table-bordered {
                border-collapse: collapse;
                width: 100%;
            }
            .table-bordered th, .table-bordered td {
                border: 1px solid #000 !important;
                padding: 6px;
                vertical-align: top;
            }
            .form-table thead th {
                background-color: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
                font-weight: bold;
                text-align: center;
                height: 30px;
            }
            .form-table tbody td {
                min-height: 50px; /* Ensure minimum row height */
            }

            /* Signature Block */
            .signature-block {
                display: flex;
                justify-content: space-around;
                margin-top: 50px;
                text-align: center;
                width: 100%;
            }
            .signature-item {
                width: 45%;
            }
            .signature-line {
                margin-top: 50px;
                border-bottom: 1px solid #000;
                height: 1px;
                width: 100%;
            }
            .signature-label {
                margin-top: 5px;
                font-size: 9pt;
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
    

</head>
<body>
    <!-- Screen-only controls -->
    <div class="screen-only no-print mb-3">
        <button class="btn btn-primary" onclick="window.print()">Print</button>
        <a href="view_record.php?id=<?php echo $record_id; ?>" class="btn btn-secondary">Back</a>
    </div>
    
    <!-- Print container that will be the only thing visible when printing -->
    <div id="print-container">
        
        <!-- DPCR Form Content -->
        <?php if ($record['form_type'] === 'DPCR'): ?>
            <div style="text-align: center; margin-bottom: 10px;">
                <h3 class="dpcr-title">DEPARTMENT PERFORMANCE COMMITMENT AND REVIEW (DPCR)</h3>
                <div class="dpcr-period">
                    i<tr>
                    <td class="label">Employee:</td>
                    <td><?php echo htmlspecialchars($record['employee_name']); ?></td>
                </tr>commit to deliver and agree to be rated on the attainment of the following targets in accordance with the indicated measures for the period
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

            <table class="dpcr-header-table">
                <tr>
                    <td style="width: 25%;" class="align-center signature-cell" colspan="2">
                        Assessed by:
                        <div style="height: 20px;"></div>
                        <div style="border-bottom: 1px solid #000; margin: 0 10px;">
                            <?php 
                                // Placeholder for City Mayor/Head of Agency Name
                                echo '____________________'; 
                            ?>
                        </div>
                        <small>Planning Office</small>
                    </td>
                    <td style="width: 8%;" rowspan="2" class="dpcr-rating-key-cell">
                        <table class="dpcr-rating-key-table">
                            <tr><td>Date</td></tr>
                            
                        </table>
                    </td>
                    <td style="width: 25%;" class="align-center signature-cell" colspan="2">
                        
                        <div style="height: 40px;"></div>
                        <div style="border-bottom: 1px solid #000; margin: 0 10px;">
                            <?php 
                                // Placeholder for City Mayor/Head of Agency Name
                                echo '____________________'; 
                            ?>
                        </div>
                        <small>PMT</small>
                    </td>
                    <td style="width: 25%;" class="align-center signature-cell" colspan="2">
                        Final rating by:
                        <div style="height: 20px;"></div>
                        <div style="border-bottom: 1px solid #000; margin: 0 10px;">
                            <?php 
                                // Placeholder for City Mayor/Head of Agency Name
                                echo '____________________'; 
                            ?>
                        </div>
                        <small>City Mayor</small>
                    </td>
                    <td style="width: 25%;" class="align-center signature-cell">
                        Date
                        <div style="height: 20px;"></div>
                        <div style="border-bottom: 1px solid #000; margin: 0 10px;">
                            <?php 
                                // Placeholder for Date
                                echo '____________________'; 
                            ?>
                        </div>
                    </td>
                </tr>
                
            </table>
            <small>Legends :  1 – QUANTITY 		2 – EFFICIENCY		3 – TIMELINESS		4 - AVERAGE</small>
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
                    <td style="border: none; padding: 1px 0; display:flex;">
                        Name of Employee: <div style="font-weight: bold; padding-left: 2px"><?php echo htmlspecialchars($record['employee_name']); ?></div>
                    </td>
                    <td style="border: none; padding: 1px 0;">
                        **Position:** <?php echo htmlspecialchars($record['position'] ?? 'N/A'); ?>
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

            <div style="border: 1px solid #000; padding: 5px; margin-bottom: 20px; ">
                <div style="font-weight: bold;">Comments and Recommendations for Development Purposes</div>
                <?php echo nl2br(htmlspecialchars($record['feedback'] ?? '')); ?>
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
                    <td style="width: 50%; border: none; text-align: right; font-weight: bold;">
                        Date: <?php echo date('F d, Y'); ?>
                    </td>
                </tr>
            </table>

        <?php endif; ?>
        
        <!-- IDP Form Content -->
        <?php if ($record['form_type'] === 'IDP'): 
            $content = json_decode($record['content'], true) ?? [];
            ?>
            
            <div class="header-info">
            <img src="images/CCA.jpg" alt="CCA Logo" onerror="this.onerror=null; this.src='https://placehold.co/70x70/cccccc/333333?text=Logo'">
            <div class="header-text">
                <h4>Republic of the Philippines</h4>
                <h4>City College of Angeles</h4>
                <h3>STRATEGIC PERFORMANCE MANAGEMENT SYSTEM (SPMS)</h3>
            </div>
        </div>

        <!-- EMPLOYEE AND RECORD DETAILS -->
        <div class="employee-details">
            <div class="row">
                <div class="col-6">
                    <p><strong>Employee Name:</strong> <?php echo $record['employee_name']; ?></p>
                </div>
                <div class="col-6">
                    <p><strong>Department/Office:</strong> <?php echo $record['department_name']; ?></p>
                </div>
            </div>
            <p><strong>Record ID:</strong> <?php echo $record_id; ?></p>
            <p><strong>Period Covered:</strong> <?php echo $record['period']; ?></p>
            <p><strong>Reviewer:</strong> <?php echo ($dept_head_name); ?></p>
        </div>
            <h4 style="margin-top: 20px; margin-bottom: 10px; text-align: center;">Development Goals</h4>
            
            <!-- IDP TABLE -->
            <table class="table table-bordered table-sm form-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">Main Objective/s</th>
                        <th style="width: 30%;">Plan of Action</th>
                        <th style="width: 15%;">Target Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($idp_goals)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No Individual Development Plan entries found.</td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td><?php echo nl2br(htmlspecialchars($objective_entry ?? '')); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($action_plan_entry ?? '')); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($target_date_entry ?? '')); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Signature Block for IDP -->
            <div class="signature-block">
                <div class="signature-item">
                    <div class="signature-line"></div>
                    <p class="signature-label">Employee Signature over Printed Name</p>
                </div>
                <div class="signature-item">
                    <div class="signature-line"></div>
                    <p class="signature-label"><?php echo htmlspecialchars($dept_head_name); ?><br>Department Head Signature over Printed Name</p>
                </div>
            </div>
        <?php endif; ?>
        
        
        <!-- <div class="footnote">
            <p>This document is generated by the City College of Angeles Employee Performance Management System.</p>
            <p>Printed on <?php echo date('F d, Y'); ?></p>
        </div> -->
    </div>
    
    <script>
        // Automatically focus the print container when page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('print-container').focus();
        });
    </script>
</body>
</html> 