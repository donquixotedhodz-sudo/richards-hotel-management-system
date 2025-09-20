<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../services/OTPService.php';

class ResetPasswordController {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    public function resetPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('Invalid request method');
            return;
        }
        
        session_start();
        
        // Check if reset email is in session
        if (!isset($_SESSION['reset_email'])) {
            header('Location: forgot-password.php?error=' . urlencode('Please start the password reset process first.'));
            exit();
        }
        
        $email = $_SESSION['reset_email'];
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate input (removed OTP validation since it was already verified)
        $validationError = $this->validateInput($newPassword, $confirmPassword);
        if ($validationError) {
            $this->redirectWithError($validationError);
            return;
        }
        
        // Update password (OTP already verified in previous step)
        if ($this->updatePassword($email, $newPassword)) {
            // Clear reset session
            unset($_SESSION['reset_email']);
            
            // Redirect to login with success message
            header('Location: ../login.php?message=' . urlencode('Password reset successfully! Please login with your new password.'));
            exit();
        } else {
            $this->redirectWithError('Failed to update password. Please try again.');
        }
    }
    
    private function validateInput($newPassword, $confirmPassword) {
        if (empty($newPassword)) {
            return 'Please enter a new password';
        }
        
        if (strlen($newPassword) < 8) {
            return 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $newPassword)) {
            return 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $newPassword)) {
            return 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/\d/', $newPassword)) {
            return 'Password must contain at least one number';
        }
        
        if ($newPassword !== $confirmPassword) {
            return 'Passwords do not match';
        }
        
        return null;
    }
    
    private function updatePassword($email, $newPassword) {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE email = ?");
            return $stmt->execute([$hashedPassword, $email]);
        } catch (PDOException $e) {
            error_log('Password update error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function redirectWithError($message) {
        header('Location: ../reset-password.php?error=' . urlencode($message));
        exit();
    }
}

// Handle the request
if (isset($_GET['action'])) {
    $controller = new ResetPasswordController();
    
    switch ($_GET['action']) {
        case 'reset_password':
            $controller->resetPassword();
            break;
        default:
            header('Location: ../reset-password.php?error=' . urlencode('Invalid action'));
            exit();
    }
} else {
    header('Location: ../reset-password.php?error=' . urlencode('No action specified'));
    exit();
}
?>