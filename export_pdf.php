<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if ID and type parameters are provided
if (!isset($_GET['id']) || !isset($_GET['type'])) {
    echo "Missing parameters";
    exit();
}

$record_id = intval($_GET['id']);
$record_type = $_GET['type'];

// Only allow PDF export for IDP and IPCR
if (!in_array($record_type, ['IDP', 'IPCR'])) {
    echo "Invalid record type";
    exit();
}

// Database connection
require_once 'includes/db_connect.php';

// Get record data
$query = "SELECT r.*, u.name as user_name, d.name as department_name 
          FROM records r 
          JOIN users u ON r.user_id = u.id
          LEFT JOIN departments d ON u.department_id = d.id
          WHERE r.id = ? AND r.form_type = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $record_id, $record_type);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Record not found";
    exit();
}

$record = $result->fetch_assoc();

// Check if user has permission to view this record
// Regular employees can only view their own records
if ($_SESSION['user_role'] === 'regular_employee' && $record['user_id'] != $_SESSION['user_id']) {
    echo "Access denied";
    exit();
}

// Department heads can view their own records and records from their department
if ($_SESSION['user_role'] === 'department_head' && 
    $record['user_id'] != $_SESSION['user_id'] && 
    $record['department_id'] != $_SESSION['user_department_id']) {
    echo "Access denied";
    exit();
}

// Generate PDF content based on record type
$html = '';

if ($record_type === 'IDP') {
    // Get IDP entries
    $entries_query = "SELECT * FROM idp_entries WHERE record_id = ? ORDER BY id ASC";
    $stmt = $conn->prepare($entries_query);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $entries_result = $stmt->get_result();
    
    $idp_entries = [];
    while ($entry = $entries_result->fetch_assoc()) {
        $idp_entries[] = $entry;
    }
    
    // Generate IDP PDF content
    $html = generateIDPContent($record, $idp_entries);
} else if ($record_type === 'IPCR') {
    // Get IPCR entries
    $entries_query = "SELECT * FROM ipcr_entries WHERE record_id = ? ORDER BY category, id ASC";
    $stmt = $conn->prepare($entries_query);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $entries_result = $stmt->get_result();
    
    $strategic_entries = [];
    $core_entries = [];
    $support_entries = [];
    
    while ($entry = $entries_result->fetch_assoc()) {
        if ($entry['category'] === 'Strategic') {
            $strategic_entries[] = $entry;
        } elseif ($entry['category'] === 'Core') {
            $core_entries[] = $entry;
        } elseif ($entry['category'] === 'Support') {
            $support_entries[] = $entry;
        }
    }
    
    // Generate IPCR PDF content
    $html = generateIPCRContent($record, $strategic_entries, $core_entries, $support_entries);
}

// Set headers for PDF download
header('Content-Type: text/html');
echo $html;

/**
 * Generate HTML content for IDP PDF
 */
function generateIDPContent($record, $idp_entries) {
    $html = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Individual Development Plan - ' . htmlspecialchars($record['period']) . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 20px;
            }
            h1, h2, h3, h4 {
                color: #2d5d2a;
                margin-top: 0;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #2d5d2a;
                padding-bottom: 10px;
            }
            .info-section {
                margin-bottom: 20px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
            }
            .category-header {
                background-color: #e9ecef;
                font-weight: bold;
                text-align: center;
            }
            .signature-section {
                margin-top: 50px;
                display: flex;
                justify-content: space-between;
            }
            .signature-box {
                width: 45%;
            }
            .signature-line {
                border-top: 1px solid #000;
                margin-top: 50px;
                margin-bottom: 5px;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 12px;
                color: #666;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h2>INDIVIDUAL DEVELOPMENT PLAN (IDP)</h2>
            <h3>City College of Angeles</h3>
            <p><strong>Period:</strong> ' . htmlspecialchars($record['period']) . '</p>
        </div>
        
        <div class="info-section">
            <p><strong>Name:</strong> ' . htmlspecialchars($record['user_name']) . '</p>
            <p><strong>Department:</strong> ' . htmlspecialchars($record['department_name']) . '</p>
            <p><strong>Status:</strong> ' . htmlspecialchars($record['document_status']) . '</p>
            <p><strong>Date Submitted:</strong> ' . ($record['date_submitted'] ? date('F d, Y', strtotime($record['date_submitted'])) : 'Not yet submitted') . '</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Development Needs</th>
                    <th>Development Interventions</th>
                    <th>Target Competency Level</th>
                    <th>Success Indicators</th>
                    <th>Timeline</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';
    
    if (count($idp_entries) > 0) {
        foreach ($idp_entries as $entry) {
            $html .= '
                <tr>
                    <td>' . htmlspecialchars($entry['development_needs']) . '</td>
                    <td>' . htmlspecialchars($entry['development_interventions']) . '</td>
                    <td>' . htmlspecialchars($entry['target_competency_level']) . '</td>
                    <td>' . htmlspecialchars($entry['success_indicators']) . '</td>
                    <td>' . ($entry['timeline_start'] ? date('M d, Y', strtotime($entry['timeline_start'])) : '') . ' - ' . 
                          ($entry['timeline_end'] ? date('M d, Y', strtotime($entry['timeline_end'])) : '') . '</td>
                    <td>' . htmlspecialchars($entry['status']) . '</td>
                </tr>';
        }
    } else {
        $html .= '<tr><td colspan="6" style="text-align: center;">No development items found</td></tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <p>Employee Signature</p>
                <p>Date: _______________</p>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <p>Department Head Signature</p>
                <p>Date: _______________</p>
            </div>
        </div>
        
        <div class="footer">
            <p>City College of Angeles - Employee Performance Management System</p>
            <p>Document generated on ' . date('F d, Y') . '</p>
        </div>
        
        <script>
            window.onload = function() {
                window.print();
            };
        </script>
    </body>
    </html>';
    
    return $html;
}

/**
 * Generate HTML content for IPCR PDF
 */
function generateIPCRContent($record, $strategic_entries, $core_entries, $support_entries) {
    $html = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Individual Performance Commitment and Review - ' . htmlspecialchars($record['period']) . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 20px;
            }
            h1, h2, h3, h4 {
                color: #2d5d2a;
                margin-top: 0;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #2d5d2a;
                padding-bottom: 10px;
            }
            .info-section {
                margin-bottom: 20px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
                font-size: 12px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 6px;
                text-align: left;
                vertical-align: top;
            }
            th {
                background-color: #f2f2f2;
            }
            .category-header {
                background-color: #e9ecef;
                font-weight: bold;
                text-align: center;
                padding: 8px;
            }
            .rating-cell {
                text-align: center;
            }
            .signature-section {
                margin-top: 50px;
                display: flex;
                justify-content: space-between;
            }
            .signature-box {
                width: 45%;
            }
            .signature-line {
                border-top: 1px solid #000;
                margin-top: 50px;
                margin-bottom: 5px;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 12px;
                color: #666;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h2>INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR)</h2>
            <h3>City College of Angeles</h3>
            <p><strong>Period:</strong> ' . htmlspecialchars($record['period']) . '</p>
        </div>
        
        <div class="info-section">
            <p><strong>Name:</strong> ' . htmlspecialchars($record['user_name']) . '</p>
            <p><strong>Department:</strong> ' . htmlspecialchars($record['department_name']) . '</p>
            <p><strong>Status:</strong> ' . htmlspecialchars($record['document_status']) . '</p>
            <p><strong>Date Submitted:</strong> ' . ($record['date_submitted'] ? date('F d, Y', strtotime($record['date_submitted'])) : 'Not yet submitted') . '</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th rowspan="2">Major Final Output</th>
                    <th rowspan="2">Success Indicators</th>
                    <th rowspan="2">Actual Accomplishments</th>
                    <th colspan="4">Rating</th>
                    <th rowspan="2">Remarks</th>
                </tr>
                <tr>
                    <th>Q</th>
                    <th>E</th>
                    <th>T</th>
                    <th>A</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="8" class="category-header">STRATEGIC FUNCTIONS (45%)</td>
                </tr>';
    
    if (count($strategic_entries) > 0) {
        foreach ($strategic_entries as $entry) {
            $html .= '
                <tr>
                    <td>' . nl2br(htmlspecialchars($entry['major_output'])) . '</td>
                    <td>' . nl2br(htmlspecialchars($entry['success_indicators'])) . '</td>
                    <td>' . nl2br(htmlspecialchars($entry['actual_accomplishments'] ?? '')) . '</td>
                    <td class="rating-cell">' . htmlspecialchars($entry['q_rating'] ?? '') . '</td>
                    <td class="rating-cell">' . htmlspecialchars($entry['e_rating'] ?? '') . '</td>
                    <td class="rating-cell">' . htmlspecialchars($entry['t_rating'] ?? '') . '</td>
                    <td class="rating-cell">' . htmlspecialchars($entry['final_rating'] ?? '') . '</td>
                    <td>' . htmlspecialchars($entry['remarks'] ?? '') . '</td>
                </tr>';
        }
    } else {
        $html .= '<tr><td colspan="8" style="text-align: center;">No strategic functions defined</td></tr>';
    }
    
    $html .= '
                <tr>
                    <td colspan="8" class="category-header">CORE FUNCTIONS (45%)</td>
                </tr>';
    
    if (count($core_entries) > 0) {
        foreach ($core_entries as $entry) {
            $html .= '
                <tr>
                    <td>' . nl2br(htmlspecialchars($entry['major_output'])) . '</td>
                    <td>' . nl2br(htmlspecialchars($entry['success_indicators'])) . '</td>
                    <td>' . nl2br(htmlspecialchars($entry['actual_accomplishments'] ?? '')) . '</td>
                    <td class="rating-cell">' . htmlspecialchars($entry['q_rating'] ?? '') . '</td>
                    <td class="rating-cell">' . htmlspecialchars($entry['e_rating'] ?? '') . '</td>
                    <td class="rating-cell">' . htmlspecialchars($entry['t_rating'] ?? '') . '</td>
                    <td class="rating-cell">' . htmlspecialchars($entry['final_rating'] ?? '') . '</td>
                    <td>' . htmlspecialchars($entry['remarks'] ?? '') . '</td>
                </tr>';
        }
    } else {
        $html .= '<tr><td colspan="8" style="text-align: center;">No core functions defined</td></tr>';
    }
    
    if (count($support_entries) > 0) {
        $html .= '
                <tr>
                    <td colspan="8" class="category-header">SUPPORT FUNCTIONS (10%)</td>
                </tr>';
        
        foreach ($support_entries as $entry) {
            $html .= '
                <tr>
                    <td>' . nl2br(htmlspecialchars($entry['major_output'])) . '</td>
                    <td>' . nl2br(htmlspecialchars($entry['success_indicators'])) . '</td>
                    <td>' . nl2br(htmlspecialchars($entry['actual_accomplishments'] ?? '')) . '</td>
                    <td class="rating-cell">' . htmlspecialchars($entry['q_rating'] ?? '') . '</td>
                    <td class="rating-cell">' . htmlspecialchars($entry['e_rating'] ?? '') . '</td>
                    <td class="rating-cell">' . htmlspecialchars($entry['t_rating'] ?? '') . '</td>
                    <td class="rating-cell">' . htmlspecialchars($entry['final_rating'] ?? '') . '</td>
                    <td>' . htmlspecialchars($entry['remarks'] ?? '') . '</td>
                </tr>';
        }
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <p>Employee Signature</p>
                <p>Date: _______________</p>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <p>Department Head Signature</p>
                <p>Date: _______________</p>
            </div>
        </div>
        
        <div class="footer">
            <p>City College of Angeles - Employee Performance Management System</p>
            <p>Document generated on ' . date('F d, Y') . '</p>
        </div>
        
        <script>
            window.onload = function() {
                window.print();
            };
        </script>
    </body>
    </html>';
    
    return $html;
}
?> 