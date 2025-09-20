<!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="bookings.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'bookings.php') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>My Bookings</span>
            </a>
            </div>
            <div class="nav-item">
                <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog"></i>
                    Profile
                </a>
            </div>
        </nav>
        
        <div class="user-profile mt-auto">
            <div class="text-center mb-4">
                <div class="user-avatar mx-auto mb-3">
                    <i class="fas fa-user"></i>
                </div>
                <div class="fw-bold fs-6"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                
            </div>
            <a href="../centralized-login/controller/LoginController.php?action=logout" class="btn btn-outline-light btn-sm w-100">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </div>
    </div>