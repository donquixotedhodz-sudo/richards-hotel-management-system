<?php
// Get current page name for active navigation
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
           
            <div class="nav-item">
                <a href="bookings.php" class="nav-link <?php echo ($current_page == 'bookings.php') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    Bookings
                </a>
            </div>
            <div class="nav-item">
                <a href="customers.php" class="nav-link <?php echo ($current_page == 'customers.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    Customers
                </a>
            </div>
             <div class="nav-item">
                <a href="rooms.php" class="nav-link <?php echo ($current_page == 'rooms.php') ? 'active' : ''; ?>">
                    <i class="fas fa-bed"></i>
                    Room Management
                </a>
            </div>
            <div class="nav-item">
                <a href="reports.php" class="nav-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    Reports & Analytics
                </a>
            </div>
            <div class="nav-item">
                <a href="settings.php" class="nav-link <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </div>
        </nav>
        
        <div class="user-profile mt-auto">
            <div class="text-center mb-3">
                <div class="user-avatar mx-auto mb-2">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="fw-bold"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                <small class="opacity-75 d-block"><?php echo htmlspecialchars($currentUser['email']); ?></small>
                <small class="badge bg-light text-dark mt-1">Administrator</small>
            </div>
            <a href="../centralized-login/controller/LoginController.php?action=logout" class="btn btn-outline-light btn-sm w-100">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </div>
    </div>