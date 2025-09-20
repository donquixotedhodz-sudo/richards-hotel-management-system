<?php
session_start();

// Check if user has temp data (came from signup)
if (!isset($_SESSION['temp_user_data'])) {
    header('Location: signup.php?error=' . urlencode('Session expired. Please start the registration process again.'));
    exit();
}

$userData = $_SESSION['temp_user_data'];
$error = $_GET['error'] ?? '';
$message = $_GET['message'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Richards Hotel Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="css/signup.css" rel="stylesheet">
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
    <div class="signup-container">
        <!-- Left side - Hotel Image -->
        <div class="signup-image-section">
            <div class="image-overlay">
                <div class="welcome-text">
                    <img src="../images/logo/logo.png" alt="Richards Hotel Logo" class="logo mb-3">
                    <h1>Almost There!</h1>
                    <p>Just one more step to complete your registration</p>
                </div>
            </div>
        </div>
        
        <!-- Right side - OTP Verification -->
        <div class="signup-form-section">
            <div class="signup-card">
                <div class="otp-container">
                    <div class="text-center mb-4">
                        <i class="fas fa-envelope-open-text verification-icon"></i>
                        <h2>Verify Your Email</h2>
                        <p class="text-muted mb-3">We've sent a 6-digit verification code to:</p>
                        <div class="email-display"><?php echo htmlspecialchars($userData['email']); ?></div>
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
                    <form id="otpVerificationForm">
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
                        
                        <button type="submit" class="btn btn-danger btn-signup w-100" id="verifyBtn">
                            <i class="fas fa-check-circle me-2"></i>
                            <span class="btn-text">Verify & Create Account</span>
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
                            <a href="signup.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Back to Registration
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/verify-otp.js"></script>
</body>
</html>