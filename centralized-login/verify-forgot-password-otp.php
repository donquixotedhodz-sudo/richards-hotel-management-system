<?php
session_start();

// Check if user has email in session (came from forgot password)
if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot-password.php?error=' . urlencode('Session expired. Please start the password reset process again.'));
    exit();
}

$email = $_SESSION['reset_email'];
$error = $_GET['error'] ?? '';
$message = $_GET['message'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Richards Hotel Management System</title>
    <link rel="icon" type="image/x-icon" href="../images/logo/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">
    <style>
        .otp-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 2rem;
        }
        .otp-input {
            font-size: 2rem;
            letter-spacing: 1rem;
            text-align: center;
            font-weight: bold;
        }
        .verification-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
        .email-display {
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            color: #dc3545;
        }
        .timer-display {
            font-size: 1.1rem;
            font-weight: 600;
            color: #dc3545;
        }
        .resend-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #dee2e6;
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
                    <h1>Password Reset</h1>
                    <p>Enter the verification code sent to your email</p>
                </div>
            </div>
        </div>
        
        <!-- Right side - OTP Verification -->
        <div class="login-form-section">
            <div class="login-card">
                <div class="otp-container">
                    <div class="text-center mb-4">
                        <i class="fas fa-envelope-open-text verification-icon"></i>
                        <h2>Verify Your Email</h2>
                        <p class="text-muted mb-3">We've sent a 6-digit verification code to:</p>
                        <div class="email-display"><?php echo htmlspecialchars($email); ?></div>
                    </div>
                    
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
                    
                    <!-- OTP Verification Form -->
                    <form id="otpVerificationForm" method="POST" action="controller/ForgotPasswordController.php">
                        <input type="hidden" name="action" value="verify_otp">
                        
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control otp-input" id="otp" name="otp" 
                                   placeholder="000000" maxlength="6" required autocomplete="off">
                            <label for="otp">
                                <i class="fas fa-key me-2"></i>Enter 6-digit OTP
                            </label>
                        </div>
                        
                        <div class="text-center mb-3">
                            <div id="timerDisplay" class="timer-display mb-2" style="display: none;">
                                Code expires in: <span id="countdown">10:00</span>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-danger btn-login w-100" id="verifyBtn">
                            <i class="fas fa-check-circle me-2"></i>
                            <span class="btn-text">Verify Code</span>
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                        
                        <div class="resend-section text-center">
                            <p class="text-muted mb-2">Didn't receive the code?</p>
                            <button type="button" class="btn btn-outline-danger" id="resendBtn">
                                <i class="fas fa-paper-plane me-2"></i>
                                <span class="btn-text">Resend OTP</span>
                                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            </button>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="forgot-password.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Back to Forgot Password
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // OTP input formatting
        document.getElementById('otp').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            e.target.value = value;
        });

        // Form submission
        document.getElementById('otpVerificationForm').addEventListener('submit', function(e) {
            const otp = document.getElementById('otp').value;
            
            if (otp.length !== 6) {
                e.preventDefault();
                showAlert('Please enter a valid 6-digit OTP code.', 'danger');
                return;
            }
            
            // Show loading state
            const verifyBtn = document.getElementById('verifyBtn');
            const btnText = verifyBtn.querySelector('.btn-text');
            const spinner = verifyBtn.querySelector('.spinner-border');
            
            btnText.textContent = 'Verifying...';
            spinner.classList.remove('d-none');
            verifyBtn.disabled = true;
        });

        // Resend OTP
        document.getElementById('resendBtn').addEventListener('click', function() {
            const resendBtn = this;
            const btnText = resendBtn.querySelector('.btn-text');
            const spinner = resendBtn.querySelector('.spinner-border');
            
            btnText.textContent = 'Sending...';
            spinner.classList.remove('d-none');
            resendBtn.disabled = true;
            
            fetch('controller/ForgotPasswordController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=resend_reset_otp'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Verification code sent successfully!', 'success');
                } else {
                    showAlert(data.message || 'Failed to resend code. Please try again.', 'danger');
                }
            })
            .catch(error => {
                showAlert('Network error. Please try again.', 'danger');
            })
            .finally(() => {
                btnText.textContent = 'Resend OTP';
                spinner.classList.add('d-none');
                resendBtn.disabled = false;
            });
        });

        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            alertContainer.innerHTML = '';
            alertContainer.appendChild(alertDiv);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>