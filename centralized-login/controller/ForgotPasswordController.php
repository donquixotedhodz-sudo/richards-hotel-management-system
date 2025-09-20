<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../services/OTPService.php';

class ForgotPasswordController {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    public function sendResetOTP() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('Invalid request method');
            return;
        }
        
        $email = trim($_POST['email'] ?? '');
        
        // Validate email
        if (empty($email)) {
            $this->redirectWithError('Please enter your email address');
            return;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirectWithError('Please enter a valid email address');
            return;
        }
        
        // Check if user exists
        if (!$this->userExists($email)) {
            $this->redirectWithError('No account found with this email address');
            return;
        }
        
        // Get user's first name for personalized email
        $firstName = $this->getUserFirstName($email);
        
        // Generate and send OTP
        $otpService = new OTPService();
        
        // Check if there's already a pending OTP
        if ($otpService->hasPendingOTP($email, 'password_reset')) {
            $this->redirectWithError('A reset code has already been sent to this email. Please wait before requesting a new one.');
            return;
        }
        
        $otp = $otpService->generateOTP();
        
        if ($otpService->storeOTP($email, $otp, 'password_reset') && $otpService->sendPasswordResetOTP($email, $otp, $firstName)) {
            // Store email in session for reset process
            session_start();
            $_SESSION['reset_email'] = $email;
            
            header('Location: ../verify-forgot-password-otp.php?message=' . urlencode('Verification code sent to your email'));
            exit();
        } else {
            $this->redirectWithError('Failed to send reset code. Please try again.');
        }
    }
    
    public function resendResetOTP() {
        session_start();
        
        if (!isset($_SESSION['reset_email'])) {
            $this->redirectWithError('Please start the password reset process first');
            return;
        }
        
        $email = $_SESSION['reset_email'];
        $firstName = $this->getUserFirstName($email);
        
        // Generate and send new OTP
        $otpService = new OTPService();
        $otp = $otpService->generateOTP();
        
        if ($otpService->storeOTP($email, $otp, 'password_reset') && $otpService->sendPasswordResetOTP($email, $otp, $firstName)) {
            header('Location: reset-password.php?message=' . urlencode('New reset code sent successfully to your email address'));
            exit();
        } else {
            header('Location: reset-password.php?error=' . urlencode('Failed to send reset code. Please try again.'));
            exit();
        }
    }
    
    private function userExists($email) {
        try {
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('User existence check error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function getUserFirstName($email) {
        try {
            $stmt = $this->conn->prepare("SELECT first_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['first_name'] : '';
        } catch (PDOException $e) {
            error_log('Get user first name error: ' . $e->getMessage());
            return '';
        }
    }
    
    public function verifyOTP() {
        session_start();
        
        if (!isset($_SESSION['reset_email'])) {
            header('Location: ../forgot-password.php?error=' . urlencode('Session expired. Please start again.'));
            exit();
        }
        
        $otp = trim($_POST['otp'] ?? '');
        $email = $_SESSION['reset_email'];
        
        // Validate OTP format
        if (!preg_match('/^\d{6}$/', $otp)) {
            header('Location: ../verify-forgot-password-otp.php?error=' . urlencode('Please enter a valid 6-digit OTP'));
            exit();
        }
        
        try {
            $otpService = new OTPService();
            
            // Verify OTP
            if ($otpService->verifyOTP($email, $otp, 'password_reset')) {
                // OTP is valid, redirect to reset password page
                header('Location: ../reset-password.php');
                exit();
            } else {
                header('Location: ../verify-forgot-password-otp.php?error=' . urlencode('Invalid or expired OTP. Please try again.'));
                exit();
            }
        } catch (Exception $e) {
            error_log("OTP verification error: " . $e->getMessage());
            header('Location: ../verify-forgot-password-otp.php?error=' . urlencode('Verification failed. Please try again.'));
            exit();
        }
    }
    
    private function redirectWithError($message) {
        header('Location: ../forgot-password.php?error=' . urlencode($message));
        exit();
    }
}

// Handle the request
$action = $_GET['action'] ?? $_POST['action'] ?? null;

if ($action) {
    $controller = new ForgotPasswordController();
    
    switch ($action) {
        case 'send_reset_otp':
            $controller->sendResetOTP();
            break;
        case 'resend_reset_otp':
            $controller->resendResetOTP();
            break;
        case 'verify_otp':
            $controller->verifyOTP();
            break;
        default:
            header('Location: ../forgot-password.php?error=' . urlencode('Invalid action'));
            exit();
    }
} else {
    header('Location: ../forgot-password.php?error=' . urlencode('No action specified'));
    exit();
}
?>