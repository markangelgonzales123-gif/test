<?php
// Start session
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../access_denied.php");
    exit();
}

// Database connection
require_once '../includes/db_connect.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update DPCR computation type
        if (isset($_POST['dpcr_computation_type'])) {
            $dpcr_computation_type = $_POST['dpcr_computation_type'];
            updateSetting($conn, 'dpcr_computation_type', $dpcr_computation_type, 
                'DPCR computation type: Type1 = Strategic (45%) and Core (55%), Type2 = Strategic (45%), Core (45%), and Support (10%)');
        }
        
        // Update IPCR rating weights
        if (isset($_POST['quality_weight']) && isset($_POST['efficiency_weight']) && isset($_POST['timeliness_weight'])) {
            $quality_weight = intval($_POST['quality_weight']);
            $efficiency_weight = intval($_POST['efficiency_weight']);
            $timeliness_weight = intval($_POST['timeliness_weight']);
            
            // Validate that weights sum to 100%
            if (($quality_weight + $efficiency_weight + $timeliness_weight) !== 100) {
                throw new Exception("IPCR rating weights must sum to 100%");
            }
            
            updateSetting($conn, 'quality_weight', $quality_weight, 'Quality criteria weight for IPCR (%)');
            updateSetting($conn, 'efficiency_weight', $efficiency_weight, 'Efficiency criteria weight for IPCR (%)');
            updateSetting($conn, 'timeliness_weight', $timeliness_weight, 'Timeliness criteria weight for IPCR (%)');
        }
        
        // Update system name
        if (isset($_POST['system_name'])) {
            $system_name = $_POST['system_name'];
            updateSetting($conn, 'system_name', $system_name, 'System name displayed in the UI');
        }
        
        // Update organization name
        if (isset($_POST['organization_name'])) {
            $organization_name = $_POST['organization_name'];
            updateSetting($conn, 'organization_name', $organization_name, 'Organization name displayed in the UI');
        }
        
        // Update fiscal year
        if (isset($_POST['fiscal_year'])) {
            $fiscal_year = intval($_POST['fiscal_year']);
            updateSetting($conn, 'fiscal_year', $fiscal_year, 'Current fiscal year for reports');
        }
        
        // Commit transaction
        $conn->commit();
        
        // Set success message
        $_SESSION['success_message'] = "Settings updated successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error_message'] = "Error updating settings: " . $e->getMessage();
    }
    
    // Redirect back to dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // If accessed directly without POST data
    header("Location: dashboard.php");
    exit();
}

/**
 * Update a system setting or create it if it doesn't exist
 * 
 * @param mysqli $conn Database connection
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @param string $description Setting description
 * @return bool Success or failure
 */
function updateSetting($conn, $key, $value, $description) {
    // Check if setting exists
    $check_query = "SELECT id FROM system_settings WHERE setting_key = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $key);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing setting
        $update_query = "UPDATE system_settings SET setting_value = ?, description = ? WHERE setting_key = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sss", $value, $description, $key);
        return $update_stmt->execute();
    } else {
        // Insert new setting
        $insert_query = "INSERT INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("sss", $key, $value, $description);
        return $insert_stmt->execute();
    }
}

?> 