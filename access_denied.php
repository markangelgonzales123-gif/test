<?php
// Set page title
$page_title = "Access Denied - EPMS";

// Include header
include_once('includes/header.php');
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-shield-lock-fill text-danger" style="font-size: 5rem;"></i>
                    </div>
                    <h2 class="mb-4">Access Denied</h2>
                    <p class="mb-4">You do not have permission to access this page. Please contact your administrator if you believe this is an error.</p>
                    
                    <?php if(isset($_SESSION['user_role'])): ?>
                        <p class="text-muted">Your current role is: <strong>
                            <?php 
                            $role_display = "User";
                            switch ($_SESSION['user_role']) {
                                case 'admin':
                                    $role_display = "Administrator";
                                    break;
                                case 'president':
                                    $role_display = "President";
                                    break;
                                case 'department_head':
                                    $role_display = "Department Head";
                                    break;
                                case 'regular_employee':
                                    $role_display = "Employee";
                                    break;
                            }
                            echo $role_display;
                            ?>
                        </strong></p>
                        
                        <div class="card bg-light my-3">
                            <div class="card-body text-start">
                                <h6 class="card-title"><i class="bi bi-info-circle-fill text-primary me-2"></i>Allowed Access Based on Your Role:</h6>
                                <div class="ms-3 mt-2">
                                    <?php if($_SESSION['user_role'] == 'regular_employee'): ?>
                                        <p class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>IPCR (Individual Performance Commitment and Review)</p>
                                        <p class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>IDP (Individual Development Plan)</p>
                                    <?php elseif($_SESSION['user_role'] == 'department_head'): ?>
                                        <p class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>DPCR (Department Performance Commitment and Review)</p>
                                        <p class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>Department IPCR forms</p>
                                        <p class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>Your own IPCR and IDP</p>
                                    <?php elseif($_SESSION['user_role'] == 'president'): ?>
                                        <p class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>All forms (DPCR, IPCR, IDP)</p>
                                        <p class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>All reports</p>
                                    <?php elseif($_SESSION['user_role'] == 'admin'): ?>
                                        <p class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>User management</p>
                                        <p class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>Role management</p>
                                        <p class="mb-1"><i class="bi bi-check-circle-fill text-success me-2"></i>All forms and reports</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="dashboard.php" class="btn btn-primary me-2">Go to Dashboard</a>
                            <a href="logout.php" class="btn btn-outline-secondary">Logout</a>
                        <?php else: ?>
                            <a href="index.php" class="btn btn-primary">Back to Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once('includes/footer.php');
?> 