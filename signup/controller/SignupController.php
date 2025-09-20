<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../services/OTPService.php';

class SignupController {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    public function sendOTP() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Invalid request method');
            return;
        }
        
        // Get and sanitize input data
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $terms = isset($_POST['terms']);
        
        // Validate input
        $validation_error = $this->validateInput($first_name, $last_name, $email, $phone, $password, $confirm_password, $terms);
        if ($validation_error) {
            $this->jsonResponse(false, $validation_error);
            return;
        }
        
        // Check if email already exists
        if ($this->userExists($email)) {
            $this->jsonResponse(false, 'Email already exists');
            return;
        }
        
        // Store user data in session temporarily
        session_start();
        $_SESSION['temp_user_data'] = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password
        ];
        
        // Generate and send OTP
        $otpService = new OTPService();
        
        // Check if there's already a pending OTP
        if ($otpService->hasPendingOTP($email)) {
            $this->jsonResponse(false, 'An OTP has already been sent to this email. Please wait before requesting a new one.');
            return;
        }
        
        $otp = $otpService->generateOTP();
        
        if ($otpService->storeOTP($email, $otp) && $otpService->sendOTP($email, $otp, $first_name)) {
            $this->jsonResponse(true, 'OTP sent successfully to your email address');
        } else {
            $this->jsonResponse(false, 'Failed to send OTP. Please try again.');
        }
    }
    
    public function verifyOTPAndRegister() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Invalid request method');
            return;
        }
        
        session_start();
        
        // Check if temp user data exists
        if (!isset($_SESSION['temp_user_data'])) {
            $this->jsonResponse(false, 'Session expired. Please start the registration process again.');
            return;
        }
        
        $otp = trim($_POST['otp'] ?? '');
        $userData = $_SESSION['temp_user_data'];
        
        if (empty($otp)) {
            $this->jsonResponse(false, 'Please enter the OTP code');
            return;
        }
        
        // Verify OTP
        $otpService = new OTPService();
        if (!$otpService->verifyOTP($userData['email'], $otp)) {
            $this->jsonResponse(false, 'Invalid or expired OTP code');
            return;
        }
        
        // Hash password
        $hashed_password = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Insert user into database
        try {
            $query = "INSERT INTO users (first_name, last_name, email, phone, password_hash, created_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $userData['first_name'], PDO::PARAM_STR);
            $stmt->bindParam(2, $userData['last_name'], PDO::PARAM_STR);
            $stmt->bindParam(3, $userData['email'], PDO::PARAM_STR);
            $stmt->bindParam(4, $userData['phone'], PDO::PARAM_STR);
            $stmt->bindParam(5, $hashed_password, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                // Clear temp session data
                unset($_SESSION['temp_user_data']);
                $this->jsonResponse(true, 'Account created successfully! You can now login.');
            } else {
                $this->jsonResponse(false, 'Failed to create account. Please try again.');
            }
        } catch (Exception $e) {
            error_log('Signup error: ' . $e->getMessage());
            error_log('Signup error trace: ' . $e->getTraceAsString());
            $this->jsonResponse(false, 'Database error occurred. Please try again.');
        }
    }
    
    public function verifyOTP() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Invalid request method');
            return;
        }
        
        session_start();
        
        // Check if temp user data exists
        if (!isset($_SESSION['temp_user_data'])) {
            $this->jsonResponse(false, 'Session expired. Please start the registration process again.');
            return;
        }
        
        $otp = trim($_POST['otp'] ?? '');
        $userData = $_SESSION['temp_user_data'];
        
        if (empty($otp)) {
            $this->jsonResponse(false, 'Please enter the OTP code');
            return;
        }
        
        // Verify OTP
        $otpService = new OTPService();
        if (!$otpService->verifyOTP($userData['email'], $otp)) {
            $this->jsonResponse(false, 'Invalid or expired OTP code');
            return;
        }
        
        // Hash password
        $hashed_password = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Insert user into database
        try {
            $query = "INSERT INTO users (first_name, last_name, email, phone, password_hash, created_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $userData['first_name'], PDO::PARAM_STR);
            $stmt->bindParam(2, $userData['last_name'], PDO::PARAM_STR);
            $stmt->bindParam(3, $userData['email'], PDO::PARAM_STR);
            $stmt->bindParam(4, $userData['phone'], PDO::PARAM_STR);
            $stmt->bindParam(5, $hashed_password, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                // Clear temp session data
                unset($_SESSION['temp_user_data']);
                $this->jsonResponse(true, 'Account created successfully! Redirecting to login...');
            } else {
                $this->jsonResponse(false, 'Failed to create account. Please try again.');
            }
        } catch (Exception $e) {
            error_log('Signup error: ' . $e->getMessage());
            error_log('Signup error trace: ' . $e->getTraceAsString());
            $this->jsonResponse(false, 'Database error occurred. Please try again.');
        }
    }
    
    public function resendOTP() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Invalid request method');
            return;
        }
        
        session_start();
        
        // Check if temp user data exists
        if (!isset($_SESSION['temp_user_data'])) {
            $this->jsonResponse(false, 'Session expired. Please start the registration process again.');
            return;
        }
        
        $userData = $_SESSION['temp_user_data'];
        $email = $userData['email'];
        $first_name = $userData['first_name'];
        
        // Generate and send new OTP
        $otpService = new OTPService();
        $otp = $otpService->generateOTP();
        
        if ($otpService->storeOTP($email, $otp) && $otpService->sendOTP($email, $otp, $first_name)) {
            $this->jsonResponse(true, 'New OTP sent successfully to your email address');
        } else {
            $this->jsonResponse(false, 'Failed to send OTP. Please try again.');
        }
    }
    
    private function validateInput($first_name, $last_name, $email, $phone, $password, $confirm_password, $terms) {
        // Check required fields
        if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($password)) {
            return 'All fields are required';
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Invalid email format';
        }
        
        // Validate phone number (basic validation)
        if (!preg_match('/^[0-9+\-\s()]{10,15}$/', $phone)) {
            return 'Invalid phone number format';
        }
        
        // Validate password strength
        if (strlen($password) < 6) {
            return 'Password must be at least 6 characters long';
        }
        
        // Check password confirmation
        if ($password !== $confirm_password) {
            return 'Passwords do not match';
        }
        
        // Check terms acceptance
        if (!$terms) {
            return 'You must agree to the terms and conditions';
        }
        
        return null; // No validation errors
    }
    
    private function userExists($email) {
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    private function redirect($type, $message) {
        $encoded_message = urlencode($message);
        header("Location: ../signup.php?{$type}={$encoded_message}");
        exit();
    }
    
    private function jsonResponse($success, $message, $data = null) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit();
    }
}

// Handle the request
if (isset($_GET['action'])) {
    $controller = new SignupController();
    
    switch ($_GET['action']) {
        case 'send_otp':
            $controller->sendOTP();
            break;
        case 'verify_otp':
            $controller->verifyOTP();
            break;
        case 'verify_otp_and_register':
            $controller->verifyOTPAndRegister();
            break;
        case 'resend_otp':
            $controller->resendOTP();
            break;
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }
} else {
    header('Location: ../signup.php?error=' . urlencode('Invalid action'));
    exit();
}
?>