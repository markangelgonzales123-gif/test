<?php
// Set page title (though it will redirect, so this is just a precaution)
$page_title = "Update Ratings - EPMS";

// Include header (for session management)
include_once('includes/header.php');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'department_head' && $_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'president')) {
    header("Location: access_denied.php");
    exit();
}

// Check if form data was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['record_id'])) {
    $_SESSION['error_message'] = "Invalid request. Please try again.";
    header("Location: records.php");
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

// Get record ID and user info
$record_id = intval($_POST['record_id']);
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$department_id = $_SESSION['user_department_id'] ?? null;

// Verify the record exists and user has permission to edit it
$record_query = "SELECT r.*, u.department_id
                FROM records r
                JOIN users u ON r.user_id = u.id
                WHERE r.id = ? AND r.form_type = 'IPCR' AND r.status = 'Pending'";
$stmt = $conn->prepare($record_query);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$record_result = $stmt->get_result();

if ($record_result->num_rows === 0) {
    $_SESSION['error_message'] = "Record not found or not eligible for rating!";
    header("Location: records.php");
    exit();
}

$record = $record_result->fetch_assoc();

// Verify user has permission to rate this record
$can_review = ($user_role == 'department_head' && $record['department_id'] == $department_id && $record['user_id'] != $user_id) || 
              ($user_role == 'president') ||
              ($user_role == 'admin');

if (!$can_review) {
    $_SESSION['error_message'] = "You don't have permission to rate this record!";
    header("Location: view_record.php?id=" . $record_id);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Get the content JSON
    $content_query = "SELECT content FROM records WHERE id = ?";
    $stmt = $conn->prepare($content_query);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $content_result = $stmt->get_result();
    $content_row = $content_result->fetch_assoc();
    
    if (empty($content_row['content'])) {
        throw new Exception("Record has no content data");
    }
    
    $content = json_decode($content_row['content'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error parsing record data: " . json_last_error_msg());
    }
    
    // Process strategic function ratings
    if (isset($_POST['strategic_supervisor_q']) && is_array($_POST['strategic_supervisor_q'])) {
        // Process strategic functions
        foreach ($_POST['strategic_supervisor_q'] as $index => $q_value) {
            if (isset($content['strategic_functions'][$index])) {
                // Keep the original MFO, Indicators, and Accomplishments
                $mfo = $content['strategic_functions'][$index]['mfo'] ?? '';
                $indicators = $content['strategic_functions'][$index]['success_indicators'] ?? '';
                $accomplishments = $content['strategic_functions'][$index]['accomplishments'] ?? '';
                
                // Update supervisor ratings
                $content['strategic_functions'][$index]['supervisor_q'] = $_POST['strategic_supervisor_q'][$index] ?? '';
                $content['strategic_functions'][$index]['supervisor_e'] = $_POST['strategic_supervisor_e'][$index] ?? '';
                $content['strategic_functions'][$index]['supervisor_t'] = $_POST['strategic_supervisor_t'][$index] ?? '';
                $content['strategic_functions'][$index]['supervisor_a'] = $_POST['strategic_supervisor_a'][$index] ?? '';
                $content['strategic_functions'][$index]['remarks'] = $_POST['strategic_remarks'][$index] ?? '';
            }
        }
    }
    
    // Process core function ratings
    if (isset($_POST['core_supervisor_q']) && is_array($_POST['core_supervisor_q'])) {
        // Process core functions
        foreach ($_POST['core_supervisor_q'] as $index => $q_value) {
            if (isset($content['core_functions'][$index])) {
                // Keep the original MFO, Indicators, and Accomplishments
                $mfo = $content['core_functions'][$index]['mfo'] ?? '';
                $indicators = $content['core_functions'][$index]['success_indicators'] ?? '';
                $accomplishments = $content['core_functions'][$index]['accomplishments'] ?? '';
                
                // Update supervisor ratings
                $content['core_functions'][$index]['supervisor_q'] = $_POST['core_supervisor_q'][$index] ?? '';
                $content['core_functions'][$index]['supervisor_e'] = $_POST['core_supervisor_e'][$index] ?? '';
                $content['core_functions'][$index]['supervisor_t'] = $_POST['core_supervisor_t'][$index] ?? '';
                $content['core_functions'][$index]['supervisor_a'] = $_POST['core_supervisor_a'][$index] ?? '';
                $content['core_functions'][$index]['remarks'] = $_POST['core_remarks'][$index] ?? '';
            }
        }
    }
    
    // Final rating
    if (isset($_POST['final_rating']) && !empty($_POST['final_rating'])) {
        $final_rating = floatval($_POST['final_rating']);
        $content['final_rating'] = $final_rating;
        
        // Add interpretation of the rating
        if ($final_rating >= 4.50) {
            $content['rating_interpretation'] = "Outstanding";
        } elseif ($final_rating >= 3.50) {
            $content['rating_interpretation'] = "Very Satisfactory";
        } elseif ($final_rating >= 2.50) {
            $content['rating_interpretation'] = "Satisfactory";
        } elseif ($final_rating >= 1.50) {
            $content['rating_interpretation'] = "Unsatisfactory";
        } else {
            $content['rating_interpretation'] = "Poor";
        }
    }
    
    // Update the record with the new content
    $content_json = json_encode($content);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error encoding record data: " . json_last_error_msg());
    }
    
    $update_query = "UPDATE records SET content = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $content_json, $record_id);
    $stmt->execute();
    
    // Check if the form was approved
    if (isset($_POST['action']) && $_POST['action'] === 'approve') {
        $comments = trim($_POST['comments'] ?? '');
        
        $update_query = "UPDATE records SET status = 'Approved', reviewed_by = ?, date_reviewed = NOW(), comments = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("isi", $user_id, $comments, $record_id);
        $stmt->execute();
        
        $_SESSION['success_message'] = "IPCR has been rated and approved!";
    } else {
        $_SESSION['success_message'] = "IPCR ratings have been saved!";
    }
    
    // Commit the transaction
    $conn->commit();
    
    // Redirect back to the record
    header("Location: view_record.php?id=" . $record_id);
    exit();
} catch (Exception $e) {
    // Roll back transaction on error
    $conn->rollback();
    

}

// Close database connection
$conn->close();
?> 