<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class OTPService {
    private $conn;
    private $mail;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->initializeMailer();
    }
    
    /**
     * Initialize PHPMailer configuration
     */
    private function initializeMailer() {
        $this->mail = new PHPMailer(true);
        
        try {
            // Set timezone to match system
            date_default_timezone_set('UTC');
            
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host       = 'smtp.gmail.com'; // Change to your SMTP server
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = 'richardshotelmanagement@gmail.com'; // Change to your email
            $this->mail->Password   = 'dttz szma pzjp fllz'; // Change to your app password
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = 587;
            
            // Recipients
            $this->mail->setFrom('richardshotelmanagement@gmail.com', 'RHMS Hotel');
            $this->mail->isHTML(true);
        } catch (Exception $e) {    
            error_log('Mailer initialization error: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate a 6-digit OTP
     */
    public function generateOTP() {
        return sprintf('%06d', mt_rand(100000, 999999));
    }
    
    /**
     * Store OTP in database with expiration time
     */
    public function storeOTP($email, $otp, $purpose = 'signup') {
        try {
            // Delete any existing OTP for this email and purpose
            $deleteStmt = $this->conn->prepare("DELETE FROM otp_codes WHERE email = ? AND purpose = ?");
            $deleteStmt->execute([$email, $purpose]);
            
            // Insert new OTP with 10 minutes expiration using UTC time
            $currentTime = gmdate('Y-m-d H:i:s');
            $expiresAt = gmdate('Y-m-d H:i:s', strtotime('+10 minutes'));
            $stmt = $this->conn->prepare("
                INSERT INTO otp_codes (email, otp_code, purpose, expires_at, created_at) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([$email, $otp, $purpose, $expiresAt, $currentTime]);
        } catch (PDOException $e) {
            error_log('OTP storage error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify OTP code
     */
    public function verifyOTP($email, $otp, $purpose = 'signup') {
        try {
            $currentTime = gmdate('Y-m-d H:i:s');
            $stmt = $this->conn->prepare("
                SELECT id FROM otp_codes 
                WHERE email = ? AND otp_code = ? AND purpose = ? 
                AND expires_at > ? AND is_used = 0
            ");
            $stmt->execute([$email, $otp, $purpose, $currentTime]);
            
            if ($stmt->rowCount() > 0) {
                // Mark OTP as used
                $otpId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
                $updateStmt = $this->conn->prepare("UPDATE otp_codes SET is_used = 1 WHERE id = ?");
                $updateStmt->execute([$otpId]);
                
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log('OTP verification error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send OTP via email
     */
    public function sendOTP($email, $otp, $firstName = '') {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($email);
            
            $this->mail->Subject = 'Your OTP Code - RHMS Hotel';
            
            $emailBody = $this->getOTPEmailTemplate($otp, $firstName);
            $this->mail->Body = $emailBody;
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log('Email sending error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send Password Reset OTP via email
     */
    public function sendPasswordResetOTP($email, $otp, $firstName = '') {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($email);
            
            $this->mail->Subject = 'Password Reset Code - RHMS Hotel';
            
            $emailBody = $this->getPasswordResetEmailTemplate($otp, $firstName);
            $this->mail->Body = $emailBody;
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log('Password reset email sending error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get OTP email template
     */
    private function getOTPEmailTemplate($otp, $firstName) {
        $greeting = $firstName ? "Dear {$firstName}," : "Dear Customer,";
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>OTP Verification</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f8f9fa; }
                .otp-code { font-size: 32px; font-weight: bold; color: #dc3545; text-align: center; 
                           background: white; padding: 20px; margin: 20px 0; border-radius: 8px; 
                           border: 2px dashed #dc3545; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; 
                          border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='../images/logo/logo.png' alt='Richards Hotel Logo' style='height: 50px; margin-bottom: 10px;'>
                    <h1>RHMS Hotel</h1>
                    <p>Account Verification</p>
                </div>
                
                <div class='content'>
                    <p>{$greeting}</p>
                    
                    <p>Thank you for registering with RHMS Hotel! To complete your account creation, 
                    please use the following One-Time Password (OTP):</p>
                    
                    <div class='otp-code'>{$otp}</div>
                    
                    <div class='warning'>
                        <strong>Important:</strong>
                        <ul>
                            <li>This OTP is valid for 10 minutes only</li>
                            <li>Do not share this code with anyone</li>
                            <li>If you didn't request this, please ignore this email</li>
                        </ul>
                    </div>
                    
                    <p>If you have any questions, please contact our support team.</p>
                    
                    <p>Best regards,<br>RHMS Hotel Team</p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get Password Reset email template
     */
    private function getPasswordResetEmailTemplate($otp, $firstName) {
        $greeting = $firstName ? "Dear {$firstName}," : "Dear Customer,";
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Password Reset</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f8f9fa; }
                .otp-code { font-size: 32px; font-weight: bold; color: #dc3545; text-align: center; 
                           background: white; padding: 20px; margin: 20px 0; border-radius: 8px; 
                           border: 2px dashed #dc3545; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; 
                          border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='../images/logo/logo.png' alt='Richards Hotel Logo' style='height: 50px; margin-bottom: 10px;'>
                    <h1>RHMS Hotel</h1>
                    <p>Password Reset Request</p>
                </div>
                
                <div class='content'>
                    <p>{$greeting}</p>
                    
                    <p>We received a request to reset your password for your RHMS Hotel account. 
                    Please use the following reset code to create a new password:</p>
                    
                    <div class='otp-code'>{$otp}</div>
                    
                    <div class='warning'>
                        <strong>Important:</strong>
                        <ul>
                            <li>This reset code is valid for 10 minutes only</li>
                            <li>Do not share this code with anyone</li>
                            <li>If you didn't request this password reset, please ignore this email</li>
                            <li>Your password will remain unchanged unless you complete the reset process</li>
                        </ul>
                    </div>
                    
                    <p>If you have any questions or need assistance, please contact our support team.</p>
                    
                    <p>Best regards,<br>RHMS Hotel Team</p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Clean up expired OTP codes
     */
    public function cleanupExpiredOTPs() {
        try {
            $stmt = $this->conn->prepare("DELETE FROM otp_codes WHERE expires_at < NOW()");
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('OTP cleanup error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if email has pending OTP
     */
    public function hasPendingOTP($email, $purpose = 'signup') {
        try {
            $currentTime = gmdate('Y-m-d H:i:s');
            $stmt = $this->conn->prepare("
                SELECT id FROM otp_codes 
                WHERE email = ? AND purpose = ? AND expires_at > ? AND is_used = 0
            ");
            $stmt->execute([$email, $purpose, $currentTime]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('Pending OTP check error: ' . $e->getMessage());
            return false;
        }
    }
}
?>