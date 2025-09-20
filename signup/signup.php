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
    <title>Sign Up - Richards Hotel Management System</title>
     <!-- Favicon -->
    <link rel="icon" type="image/png" href="../images/logo/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="css/signup.css" rel="stylesheet">
</head>
<body>
    <div class="signup-container">
        <!-- Left side - Hotel Image -->
        <div class="signup-image-section">
            <div class="image-overlay">
                <div class="welcome-text">
                    <img src="../images/logo/logo.png" alt="Richards Hotel Logo" class="logo mb-3">
                    <h1>Join Richards Hotel</h1>
                    <p>Create your account and start your luxury journey</p>
                </div>
            </div>
        </div>
        
        <!-- Right side - Signup Form -->
        <div class="signup-form-section">
            <div class="signup-card">
                <div class="signup-header">
                    <h2>Create Account</h2>
                    <p>Join our community today</p>
                </div>
                
                <div class="signup-body">
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
                
                <!-- Alert Container for Dynamic Messages -->
                <div id="alertContainer"></div>
                
                <!-- Step 1: User Information Form -->
                <form id="signupForm" style="display: block;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       placeholder="First Name" required>
                                <label for="first_name">
                                    <i class="fas fa-user me-2"></i>First Name
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       placeholder="Last Name" required>
                                <label for="last_name">
                                    <i class="fas fa-user me-2"></i>Last Name
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Email Address" required>
                        <label for="email">
                            <i class="fas fa-envelope me-2"></i>Email Address
                        </label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               placeholder="Phone Number" required>
                        <label for="phone">
                            <i class="fas fa-phone me-2"></i>Phone Number
                        </label>
                    </div>
                    
                    <div class="form-floating mb-3 position-relative">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Password" required>
                        <label for="password">
                            <i class="fas fa-lock me-2"></i>Password
                        </label>
                        
                        <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y me-3 p-0 border-0 d-none" 
                                onclick="togglePassword('password', 'passwordIcon')" style="z-index: 10; background: none;" id="passwordToggleBtn">
                            <i class="fas fa-eye text-muted" id="passwordIcon"></i>
                        </button>
                    </div>
                    
                    <div class="form-floating mb-3 position-relative">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm Password" required>
                        <label for="confirm_password">
                            <i class="fas fa-lock me-2"></i>Confirm Password
                        </label>
                        
                        <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y me-3 p-0 border-0 d-none" 
                                onclick="togglePassword('confirm_password', 'confirmPasswordIcon')" style="z-index: 10; background: none;" id="confirmPasswordToggleBtn">
                            <i class="fas fa-eye text-muted" id="confirmPasswordIcon"></i>
                        </button>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="#" class="text-danger">Terms and Conditions</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-danger btn-signup" id="sendOtpBtn">
                        <i class="fas fa-paper-plane me-2"></i>
                        <span class="btn-text">Send OTP</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </form>
                
                <!-- Step 2: OTP Verification Form -->
                <form id="otpForm" style="display: none;">
                    <div class="text-center mb-4">
                        <i class="fas fa-envelope-open-text text-danger" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">Verify Your Email</h4>
                        <p class="text-muted">We've sent a 6-digit code to <strong id="emailDisplay"></strong></p>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control text-center" id="otp" name="otp" 
                               placeholder="Enter OTP" maxlength="6" required 
                               style="font-size: 1.5rem; letter-spacing: 0.5rem;">
                        <label for="otp">
                            <i class="fas fa-key me-2"></i>Enter 6-digit OTP
                        </label>
                    </div>
                    
                    <div class="text-center mb-3">
                        <small class="text-muted">
                            Didn't receive the code? 
                            <button type="button" class="btn btn-link p-0 text-danger" id="resendOtpBtn">
                                Resend OTP
                            </button>
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-danger btn-signup" id="verifyOtpBtn">
                        <i class="fas fa-check-circle me-2"></i>
                        <span class="btn-text">Verify & Create Account</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                    
                    <button type="button" class="btn btn-outline-secondary btn-signup mt-2" id="backToFormBtn">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Form
                    </button>
                </form>
                
                <div class="login-link">
                    <p>Already have an account? <a href="../centralized-login/login.php" class="text-danger">Login here</a></p>
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
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/signup.js"></script>
    <script>
        function togglePassword(inputId, iconId) {
            const passwordField = document.getElementById(inputId);
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
            } else {
                passwordField.type = 'password';
            }
        }
         
         // Show/hide password toggle buttons based on input
         document.getElementById('password').addEventListener('input', function() {
             const passwordToggleBtn = document.getElementById('passwordToggleBtn');
             const passwordField = document.getElementById('password');
             
             if (this.value.length > 0) {
                 passwordToggleBtn.classList.remove('d-none');
             } else {
                 passwordToggleBtn.classList.add('d-none');
                 // Reset password field to hidden when empty
                 passwordField.type = 'password';
             }
         });
         
         document.getElementById('confirm_password').addEventListener('input', function() {
             const confirmPasswordToggleBtn = document.getElementById('confirmPasswordToggleBtn');
             const confirmPasswordField = document.getElementById('confirm_password');
             
             if (this.value.length > 0) {
                 confirmPasswordToggleBtn.classList.remove('d-none');
             } else {
                 confirmPasswordToggleBtn.classList.add('d-none');
                 // Reset confirm password field to hidden when empty
                 confirmPasswordField.type = 'password';
             }
         });
    </script>
</body>
</html>