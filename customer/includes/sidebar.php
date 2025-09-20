<!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="#" class="nav-link active" onclick="showSection('dashboard')">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link" onclick="showSection('bookings')">
                    <i class="fas fa-calendar-alt"></i>
                    My Bookings
                </a>
            </div>
            <div class="nav-item">
                <a href="#" class="nav-link" onclick="showSection('profile')">
                    <i class="fas fa-user-cog"></i>
                    Profile
                </a>
            </div>
        </nav>
        
        <div class="user-profile mt-auto">
            <div class="text-center mb-3">
                <div class="user-avatar mx-auto mb-2">
                    <i class="fas fa-user"></i>
                </div>
                <div class="fw-bold"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                <small class="opacity-75 d-block"><?php echo htmlspecialchars($currentUser['email']); ?></small>
            </div>
            <a href="../centralized-login/controller/LoginController.php?action=logout" class="btn btn-outline-light btn-sm w-100">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </div>
    </div>