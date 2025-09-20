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

// Check if reset email is in session
if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot-password.php?error=' . urlencode('Please start the password reset process first.'));
    exit();
}

$error = $_GET['error'] ?? '';
$message = $_GET['message'] ?? '';
$resetEmail = $_SESSION['reset_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Richards Hotel Management System</title>
     <!-- Favicon -->
    <link rel="icon" type="image/png" href="../images/logo/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">
    <style>
        .otp-input {
            font-size: 1.5rem;
            letter-spacing: 0.5rem;
            text-align: center;
        }
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
        .password-requirements {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 0.25rem;
        }
        .requirement i {
            margin-right: 0.5rem;
            width: 16px;
        }
        .requirement.met {
            color: #28a745;
        }
        .requirement.unmet {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left side - Hotel Image -->
        <div class="login-image-section">
            <div class="image-overlay">
                <div class="welcome-text">
                    <img src="../images/logo/logo.png" alt="Richards Hotel Logo" class="logo mb-3">
                    <h1>Create New Password</h1>
                    <p>Enter the code we sent and create a secure password</p>
                </div>
            </div>
        </div>
        
        <!-- Right side - Reset Password Form -->
        <div class="login-form-section">
            <div class="login-card">
                <div class="login-header">
                    <h2>Reset Password</h2>
                    <p>Create your new password</p>
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
                
                <form method="POST" action="controller/ResetPasswordController.php?action=reset_password" id="resetPasswordForm">
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="new_password" name="new_password" 
                               placeholder="New Password" required>
                        <label for="new_password">
                            <i class="fas fa-lock me-2"></i>New Password
                        </label>
                        <div class="password-strength" id="passwordStrength"></div>
                        <div class="password-requirements" id="passwordRequirements">
                            <div class="requirement unmet" id="req-length">
                                <i class="fas fa-times"></i>
                                At least 8 characters
                            </div>
                            <div class="requirement unmet" id="req-uppercase">
                                <i class="fas fa-times"></i>
                                One uppercase letter
                            </div>
                            <div class="requirement unmet" id="req-lowercase">
                                <i class="fas fa-times"></i>
                                One lowercase letter
                            </div>
                            <div class="requirement unmet" id="req-number">
                                <i class="fas fa-times"></i>
                                One number
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm New Password" required>
                        <label for="confirm_password">
                            <i class="fas fa-lock me-2"></i>Confirm New Password
                        </label>
                        <div id="passwordMatch" class="mt-2"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-danger btn-login" id="resetBtn">
                        <i class="fas fa-key me-2"></i>
                        <span class="btn-text">Reset Password</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </form>
                
                <div class="login-link">
                    <p>Didn't receive the code? <a href="controller/ForgotPasswordController.php?action=resend_reset_otp" class="text-danger">Resend Code</a></p>
                </div>
                
                <div class="back-link">
                    <a href="login.php">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password)
            };
            
            // Update requirement indicators
            Object.keys(requirements).forEach(req => {
                const element = document.getElementById(`req-${req}`);
                const icon = element.querySelector('i');
                
                if (requirements[req]) {
                    element.classList.remove('unmet');
                    element.classList.add('met');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-check');
                    strength++;
                } else {
                    element.classList.remove('met');
                    element.classList.add('unmet');
                    icon.classList.remove('fa-check');
                    icon.classList.add('fa-times');
                }
            });
            
            // Update strength indicator
            const strengthElement = document.getElementById('passwordStrength');
            if (strength === 0) {
                strengthElement.textContent = '';
            } else if (strength < 3) {
                strengthElement.textContent = 'Weak';
                strengthElement.className = 'password-strength strength-weak';
            } else if (strength < 4) {
                strengthElement.textContent = 'Medium';
                strengthElement.className = 'password-strength strength-medium';
            } else {
                strengthElement.textContent = 'Strong';
                strengthElement.className = 'password-strength strength-strong';
            }
            
            return strength === 4;
        }
        
        // Password match checker
        function checkPasswordMatch() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchElement = document.getElementById('passwordMatch');
            
            if (confirmPassword === '') {
                matchElement.textContent = '';
                return false;
            }
            
            if (password === confirmPassword) {
                matchElement.innerHTML = '<i class="fas fa-check text-success me-2"></i><span class="text-success">Passwords match</span>';
                return true;
            } else {
                matchElement.innerHTML = '<i class="fas fa-times text-danger me-2"></i><span class="text-danger">Passwords do not match</span>';
                return false;
            }
        }
        
        // Event listeners
        document.getElementById('new_password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
            if (document.getElementById('confirm_password').value) {
                checkPasswordMatch();
            }
        });
        
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
        
        // Form validation
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            const otp = document.getElementById('otp').value.trim();
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const resetBtn = document.getElementById('resetBtn');
            const btnText = resetBtn.querySelector('.btn-text');
            const spinner = resetBtn.querySelector('.spinner-border');
            
            if (!otp || otp.length !== 6) {
                e.preventDefault();
                alert('Please enter a valid 6-digit reset code');
                return false;
            }
            
            if (!checkPasswordStrength(password)) {
                e.preventDefault();
                alert('Please ensure your password meets all requirements');
                return false;
            }
            
            if (!checkPasswordMatch()) {
                e.preventDefault();
                alert('Passwords do not match');
                return false;
            }
            
            // Show loading state
            resetBtn.disabled = true;
            btnText.textContent = 'Resetting...';
            spinner.classList.remove('d-none');
        });
        
        // Auto-focus on OTP field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('otp').focus();
        });
        
        // OTP input formatting
        document.getElementById('otp').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
        });
    </script>
</body>
</html>