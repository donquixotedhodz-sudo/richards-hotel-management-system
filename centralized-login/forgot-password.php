<?php
session_start();

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../customer/dashboard.php');
    }
    exit();
}

$error = $_GET['error'] ?? '';
$message = $_GET['message'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Richards Hotel Management System</title>
     <!-- Favicon -->
    <link rel="icon" type="image/png" href="../images/logo/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <!-- Left side - Hotel Image -->
        <div class="login-image-section">
            <div class="image-overlay">
                <div class="welcome-text">
                    <img src="../images/logo/logo.png" alt="Richards Hotel Logo" class="logo mb-3">
                    <h1>Reset Your Password</h1>
                    <p>We'll help you get back into your account</p>
                </div>
            </div>
        </div>
        
        <!-- Right side - Forgot Password Form -->
        <div class="login-form-section">
            <div class="login-card">
                <div class="login-header">
                    <h2>Forgot Password</h2>
                    <p>Enter your email to receive a reset code</p>
                </div>
                
                <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($message): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="controller/ForgotPasswordController.php?action=send_reset_otp" id="forgotPasswordForm">
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Enter your email" required>
                        <label for="email">
                            <i class="fas fa-envelope me-2"></i>Email Address
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-danger btn-login" id="sendResetBtn">
                        <i class="fas fa-paper-plane me-2"></i>
                        <span class="btn-text">Send Reset Code</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </form>
                
                <div class="login-link">
                    <p>Remember your password? <a href="login.php" class="text-danger">Sign in here</a></p>
                </div>
                
                <div class="back-link">
                    <a href="../index.php">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Homepage
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation and submission
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const sendBtn = document.getElementById('sendResetBtn');
            const btnText = sendBtn.querySelector('.btn-text');
            const spinner = sendBtn.querySelector('.spinner-border');
            
            if (!email) {
                e.preventDefault();
                alert('Please enter your email address');
                return false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return false;
            }
            
            // Show loading state
            sendBtn.disabled = true;
            btnText.textContent = 'Sending...';
            spinner.classList.remove('d-none');
        });
        
        // Auto-focus on email field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
    </script>
</body>
</html>