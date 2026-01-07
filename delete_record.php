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
require_once 'includes/db_connect.php';

// Check if the record exists and is a draft or pending
$check_query = "SELECT * FROM records WHERE id = ? AND (status = 'Draft' OR status = 'Pending')";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Record not found or cannot be deleted. Only Draft or Pending records can be deleted.";
    header("Location: records.php");
    exit();
}

// Check if the user has permission to delete this record
$record = $result->fetch_assoc();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_department_id = $_SESSION['user_department_id'] ?? null;

// Get the record's owner department
$user_query = "SELECT department_id FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $record['user_id']);
$stmt->execute();
$user_result = $stmt->get_result();
$record_owner = $user_result->fetch_assoc();
$record_department_id = $record_owner['department_id'];

// Check permissions:
// 1. Record owner can delete their own records
// 2. Admin can delete any record
// 3. Department head can delete IPCR and IDP records from their department
$can_delete = false;

if ($record['user_id'] == $user_id) {
    // Owner can delete their own record
    $can_delete = true;
} elseif ($user_role === 'admin') {
    // Admin can delete any record
    $can_delete = true;
} elseif ($user_role === 'department_head' && $user_department_id == $record_department_id && 
          ($record['form_type'] === 'IPCR' || $record['form_type'] === 'IDP')) {
    // Department head can delete IPCR and IDP records from their department
    $can_delete = true;
}

if (!$can_delete) {
    $_SESSION['error_message'] = "You don't have permission to delete this record.";
    header("Location: records.php");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete associated entries based on form type
    switch ($record['form_type']) {
        case 'DPCR':
            $delete_entries = "DELETE FROM dpcr_entries WHERE record_id = ?";
            break;
        case 'IPCR':
            $delete_entries = "DELETE FROM ipcr_entries WHERE record_id = ?";
            break;
        case 'IDP':
            $delete_entries = "DELETE FROM idp_entries WHERE record_id = ?";
            break;
    }
    
    $stmt = $conn->prepare($delete_entries);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    
    // Delete the record
    $delete_record = "DELETE FROM records WHERE id = ?";
    $stmt = $conn->prepare($delete_record);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    
    // Commit the transaction
    $conn->commit();
    
    $_SESSION['success_message'] = "Record has been successfully deleted.";
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    $_SESSION['error_message'] = "Error deleting record: " . $e->getMessage();
}

// Redirect back to records page
header("Location: records.php");
exit(); 