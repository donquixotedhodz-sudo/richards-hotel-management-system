<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize current user data
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    // You can fetch user data from database here if needed
    $currentUser = [
        'name' => $_SESSION['username'] ?? 'Admin User',
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'] ?? 'admin@richardshotel.com'
    ];
} else {
    // Default fallback for when no user is logged in
    $currentUser = [
        'name' => 'Guest User',
        'id' => null,
        'email' => 'guest@richardshotel.com'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Richards Hotel Admin' : 'Richards Hotel - Admin Dashboard'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../images/logo/logo.png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../css/style.css" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
    <link href="css/sidebar.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    
    <?php if (isset($additional_css) && is_array($additional_css)): ?>
        <?php foreach ($additional_css as $css_file): ?>
            <link href="<?php echo $css_file; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top" style="min-height: 80px; padding: 1rem 0;">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="../index.php">
                <img src="../images/logo/logo.png" alt="Richards Hotel" height="40" class="me-2">
                Richards Hotel - Admin
            </a>
            
            <!-- Mobile Toggle for Sidebar -->
            <button class="btn btn-outline-light d-lg-none me-2" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="navbar-text me-3">
                            <i class="fas fa-user-shield me-1"></i>
                            Welcome, <?php echo htmlspecialchars($currentUser['name'] ?? 'Admin User'); ?>
                        </span>
                    </li>

                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Bootstrap 5 JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>