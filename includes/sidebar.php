<?php
// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: index.php");
    exit();
}

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);

// Get current directory
$current_dir = dirname($_SERVER['PHP_SELF']);
$base_path = '';

// Check if we're in admin section
$is_admin_section = (strpos($current_page, 'admin/') !== false || strpos($_SERVER['PHP_SELF'], '/admin/') !== false);

// Set base path based on directory
if ($is_admin_section) {
    $base_path = '../';
    $admin_path = '';
} else {
    $base_path = '';
    $admin_path = 'admin/';
}

// Add database connection to check for pending submissions
// Check for pending submissions if user is a department head
$has_pending_submissions = false;
$pending_count = 0;


// Define menu items based on roles
$menu_items = [];

switch ($_SESSION['user_role']) {
    case 'regular_employee':
        // Regular employees can access IPCR & IDP
        $menu_items = [
            ['icon' => 'bi bi-list-check', 'title' => 'My Records', 'url' => $base_path . 'records.php', 'active' => ($current_page == 'records.php')],
            ['icon' => 'bi bi-person-vcard', 'title' => 'My IPCR', 'url' => $base_path . 'ipcr.php', 'active' => ($current_page == 'ipcr.php')],
            ['icon' => 'bi bi-journal-text', 'title' => 'My IDP', 'url' => $base_path . 'idp.php', 'active' => ($current_page == 'idp.php')],
            ['icon' => 'bi bi-file-earmark-person', 'title' => 'My PDS', 'url' => $base_path . 'pdsT2.php', 'active' => ($current_page == 'pdsT2.php')],
            ['icon' => 'bi bi-person-fill', 'title' => 'My Profile', 'url' => $base_path . 'profile.php', 'active' => ($current_page == 'profile.php')]
        ];
        break;
        
    case 'department_head':
        // Department heads can access DPCR and departmental IPCR forms
        $menu_items = [
            ['icon' => 'bi bi-speedometer2', 'title' => 'Dashboard', 'url' => $base_path . 'dashboard.php', 'active' => ($current_page == 'dashboard.php')],
            ['icon' => 'bi bi-building', 'title' => 'Department DPCR', 'url' => $base_path . 'dpcr.php', 'active' => ($current_page == 'dpcr.php')],
            ['icon' => 'bi bi-person-vcard', 'title' => 'My IPCR', 'url' => $base_path . 'ipcr.php', 'active' => ($current_page == 'ipcr.php')],
            ['icon' => 'bi bi-people', 'title' => 'Staff IPCR', 'url' => $base_path . 'staff_ipcr.php', 'active' => ($current_page == 'staff_ipcr.php')],
            ['icon' => 'bi bi-journal-text', 'title' => 'Staff IDP', 'url' => $base_path . 'staff_idp.php', 'active' => ($current_page == 'staff_idp.php')],
            ['icon' => 'bi bi-journal-text', 'title' => 'My IDP', 'url' => $base_path . 'idp.php', 'active' => ($current_page == 'idp.php')],
            ['icon' => 'bi bi-file-earmark-person', 'title' => 'My PDS', 'url' => $base_path . 'pdsT2.php', 'active' => ($current_page == 'pdsT2.php')],
            ['icon' => 'bi bi-list-check', 'title' => 'My Records', 'url' => $base_path . 'records.php', 'active' => ($current_page == 'records.php')],
            ['icon' => 'bi bi-person-fill', 'title' => 'My Profile', 'url' => $base_path . 'profile.php', 'active' => ($current_page == 'profile.php')]
        ];
        break;
        
    case 'president':
        // President can access all forms and reports
        $menu_items = [
            ['icon' => 'bi bi-speedometer2', 'title' => 'Dashboard', 'url' => $base_path . 'dashboard.php', 'active' => ($current_page == 'dashboard.php')],
            ['icon' => 'bi bi-building', 'title' => 'Department DPCR', 'url' => $base_path . 'all_dpcr.php', 'active' => ($current_page == 'all_dpcr.php')],
            ['icon' => 'bi bi-person-vcard', 'title' => 'Staff IPCR', 'url' => $base_path . 'all_ipcr.php', 'active' => ($current_page == 'all_ipcr.php')],
            ['icon' => 'bi bi-journal-text', 'title' => 'Staff IDP', 'url' => $base_path . 'all_idp.php', 'active' => ($current_page == 'all_idp.php')],
            ['icon' => 'bi bi-clipboard-check', 'title' => 'Pending Reviews', 'url' => $base_path . 'records.php?status=Pending', 'active' => ($current_page == 'records.php' && isset($_GET['status']) && $_GET['status'] == 'Pending')],
            ['icon' => 'bi bi-list-check', 'title' => 'All Records', 'url' => $base_path . 'records.php', 'active' => ($current_page == 'records.php' && (!isset($_GET['status']) || $_GET['status'] != 'Pending'))],
            ['icon' => 'bi bi-person-fill', 'title' => 'My Profile', 'url' => $base_path . 'profile.php', 'active' => ($current_page == 'profile.php')]
        ];
        break;
        
    case 'admin':
        if ($is_admin_section) {
            // Admin panel links - for admin section
            $menu_items = [
                ['icon' => 'bi bi-speedometer2', 'title' => 'Dashboard', 'url' => 'dashboard.php', 'active' => ($current_page == 'dashboard.php')],
                ['icon' => 'bi bi-people', 'title' => 'Manage Users', 'url' => 'users.php', 'active' => ($current_page == 'users.php')],
                ['icon' => 'bi bi-building', 'title' => 'Manage Departments', 'url' => 'departments.php', 'active' => ($current_page == 'departments.php')],
                ['icon' => 'bi bi-arrow-left-circle', 'title' => 'Back to Main Site', 'url' => '../dashboard.php', 'active' => false]
            ];
        } else {
            // Main site links - for admin when outside the admin panel
            $menu_items = [
                ['icon' => 'bi bi-speedometer2', 'title' => 'Dashboard', 'url' => 'dashboard.php', 'active' => ($current_page == 'dashboard.php')],
                ['icon' => 'bi bi-building', 'title' => 'Department DPCR', 'url' => 'all_dpcr.php', 'active' => ($current_page == 'all_dpcr.php')],
                ['icon' => 'bi bi-person-vcard', 'title' => 'Staff IPCR', 'url' => 'all_ipcr.php', 'active' => ($current_page == 'all_ipcr.php')],
                ['icon' => 'bi bi-journal-text', 'title' => 'Staff IDP', 'url' => 'all_idp.php', 'active' => ($current_page == 'all_idp.php')],
                ['icon' => 'bi bi-list-check', 'title' => 'All Records', 'url' => 'records.php', 'active' => ($current_page == 'records.php')],
                ['icon' => 'bi bi-sliders', 'title' => 'Admin Panel', 'url' => 'admin/dashboard.php', 'active' => false],
                ['icon' => 'bi bi-person-fill', 'title' => 'My Profile', 'url' => 'profile.php', 'active' => ($current_page == 'profile.php')]
            ];
        }
        break;
        
    default:
        // Default items for any user (limited access)
        $menu_items = [
            ['icon' => 'bi bi-list-check', 'title' => 'My Records', 'url' => $base_path . 'records.php', 'active' => ($current_page == 'records.php')],
            ['icon' => 'bi bi-person-fill', 'title' => 'My Profile', 'url' => $base_path . 'profile.php', 'active' => ($current_page == 'profile.php')]
        ];
        break;
}
?>

<div class="sidebar" id="sidebar">
    <div class="d-flex flex-column h-100">
        <!-- Header with logo and text -->
        <div class="text-center text-white p-3" style="background-color: #2d5d2a;">
            <img src="<?php echo $is_admin_section ? '../' : ''; ?>images/CCA.jpg" alt="College Logo" class="sidebar-logo my-2" style="width: 60px; height: auto;">
            <h5 class="mb-0">CITY COLLEGE OF ANGELES</h5>
            <div class="small text-white-50 mb-3">Employee Performance Management System</div>
            
            <hr class="border-light opacity-25 mt-2 mb-3">
            
            <div class="user-info mb-2">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                <div class="badge bg-light text-success mt-1">
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
                </div>
            </div>
        </div>
        
        <!-- Navigation menu -->
        <div class="nav-menu flex-grow-1 px-2 py-2" style="background-color: #2d5d2a;">
            <ul class="nav nav-pills flex-column">
                <?php 
                // Add section headers based on role
                if ($_SESSION['user_role'] === 'admin'): ?>
                    <li class="text-white-50 small px-3 py-1 mt-2"><i class="bi bi-grid-fill me-2"></i>ADMIN NAVIGATION</li>
                <?php elseif ($_SESSION['user_role'] === 'president'): ?>
                    <li class="text-white-50 small px-3 py-1 mt-2"><i class="bi bi-grid-fill me-2"></i>PRESIDENT NAVIGATION</li>
                <?php elseif ($_SESSION['user_role'] === 'department_head'): ?>
                    <li class="text-white-50 small px-3 py-1 mt-2"><i class="bi bi-grid-fill me-2"></i>DEPARTMENT HEAD NAVIGATION</li>
                <?php else: ?>
                    <li class="text-white-50 small px-3 py-1 mt-2"><i class="bi bi-grid-fill me-2"></i>EMPLOYEE NAVIGATION</li>
                <?php endif; ?>
                
                <?php foreach ($menu_items as $item): ?>
                    <li class="nav-item my-1">
                        <a href="<?php echo $item['url']; ?>" class="nav-link <?php echo $item['active'] ? 'active bg-white text-success' : 'text-white'; ?>">
                            <i class="<?php echo $item['icon']; ?> me-2"></i>
                            <?php echo $item['title']; ?>
                            
                            <?php if ($item['title'] === 'Staff IPCR' && $has_pending_submissions): ?>
                                <span class="badge bg-danger ms-2 badge-new"><?php echo $pending_count; ?> New</span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- Footer with dropdown -->
        <div class="mt-auto" style="background-color: #2d5d2a;">
            <hr class="border-light opacity-25 mb-2">
            
            <div class="dropdown p-3">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-2"></i>
                    <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                </a>
                <ul class="dropdown-menu text-small shadow" aria-labelledby="dropdownUser">
                    <li><a class="dropdown-item" href="<?php echo $is_admin_section ? '../' : ''; ?>profile.php">Profile</a></li>
                    <li><a class="dropdown-item" href="<?php echo $is_admin_section ? '../' : ''; ?>change_password.php">Change Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?php echo $is_admin_section ? '../' : ''; ?>logout.php">Sign out</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom sidebar styling */
.sidebar {
    width: 280px;
    min-height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 100;
    overflow-y: auto;
}

/* Nav link styling */
.sidebar .nav-link {
    border-radius: 4px;
    padding: 8px 16px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.sidebar .nav-link:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

.sidebar .nav-link.active {
    font-weight: 600;
}

/* Make dropdown menu match theme */
.dropdown-menu {
    border: 1px solid rgba(45, 93, 42, 0.15);
}

.dropdown-menu .dropdown-item:hover {
    background-color: rgba(45, 93, 42, 0.1);
}

/* Animation for badge */
@keyframes badge-pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.badge-new {
    animation: badge-pulse 1.5s infinite;
}

/* Responsive fixes */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        position: relative;
        min-height: auto;
    }
}
</style> 