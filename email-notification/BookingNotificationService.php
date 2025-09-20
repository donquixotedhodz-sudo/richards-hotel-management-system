<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class BookingNotificationService {
    private $conn;
    private $mail;
    
    public function __construct($connection = null) {
        if ($connection) {
            $this->conn = $connection;
        } else {
            // Create new connection if none provided
            $host = 'localhost';
            $username = 'root';
            $password = '';
            $database = 'rhms_db';
            
            try {
                $this->conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Database connection failed");
            }
        }
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
            $this->mail->Host       = 'smtp.gmail.com';
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = 'richardshotelmanagement@gmail.com';
            $this->mail->Password   = 'dttz szma pzjp fllz';
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = 587;
            
            // Recipients
            $this->mail->setFrom('richardshotelmanagement@gmail.com', 'RHMS Hotel');
            $this->mail->isHTML(true);
            
            // Enable debug output for troubleshooting
            $this->mail->SMTPDebug = 0; // Set to 2 for detailed debug info
            
        } catch (Exception $e) {    
            error_log('Mailer initialization error: ' . $e->getMessage());
            throw new Exception('Email configuration failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get booking details by ID
     */
    private function getBookingDetails($bookingId) {
        try {
            $query = "
                SELECT 
                    b.*,
                    rt.type_name,
                    rt.description as room_description,
                    r.room_number,
                    u.first_name,
                    u.last_name,
                    u.email as user_email
                FROM bookings b
                LEFT JOIN room_types rt ON b.room_type_id = rt.id
                LEFT JOIN rooms r ON b.room_id = r.id
                LEFT JOIN users u ON b.user_id = u.id
                WHERE b.id = ?
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$bookingId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Get booking details error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send booking confirmation email
     */
    public function sendBookingConfirmation($bookingId) {
        try {
            $booking = $this->getBookingDetails($bookingId);
            if (!$booking) {
                error_log('Booking not found for confirmation email: ' . $bookingId);
                return false;
            }
            
            if (empty($booking['user_email'])) {
                error_log('No email address found for booking: ' . $bookingId);
                return false;
            }
            
            $this->mail->clearAddresses();
            $this->mail->addAddress($booking['user_email']);
            
            $this->mail->Subject = 'Booking Confirmed - RHMS Hotel';
            
            $emailBody = $this->getBookingConfirmationTemplate($booking);
            $this->mail->Body = $emailBody;
            
            $result = $this->mail->send();
            if ($result) {
                error_log('Confirmation email sent successfully to: ' . $booking['user_email'] . ' for booking: ' . $bookingId);
            } else {
                error_log('Failed to send confirmation email to: ' . $booking['user_email'] . ' for booking: ' . $bookingId);
            }
            return $result;
        } catch (Exception $e) {
            error_log('Booking confirmation email error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send booking cancellation email
     */
    public function sendBookingCancellation($bookingId) {
        try {
            $booking = $this->getBookingDetails($bookingId);
            if (!$booking) {
                error_log('Booking not found for cancellation email: ' . $bookingId);
                return false;
            }
            
            if (empty($booking['user_email'])) {
                error_log('No email address found for booking: ' . $bookingId);
                return false;
            }
            
            $this->mail->clearAddresses();
            $this->mail->addAddress($booking['user_email']);
            
            $this->mail->Subject = 'Booking Cancelled - RHMS Hotel';
            
            $emailBody = $this->getBookingCancellationTemplate($booking);
            $this->mail->Body = $emailBody;
            
            $result = $this->mail->send();
            if ($result) {
                error_log('Cancellation email sent successfully to: ' . $booking['user_email'] . ' for booking: ' . $bookingId);
            } else {
                error_log('Failed to send cancellation email to: ' . $booking['user_email'] . ' for booking: ' . $bookingId);
            }
            return $result;
        } catch (Exception $e) {
            error_log('Booking cancellation email error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get booking confirmation email template
     */
    private function getBookingConfirmationTemplate($booking) {
        $customerName = $booking['first_name'] . ' ' . $booking['last_name'];
        $checkInDate = date('F j, Y', strtotime($booking['check_in_datetime']));
        $checkInTime = date('g:i A', strtotime($booking['check_in_datetime']));
        $checkOutDate = date('F j, Y', strtotime($booking['check_out_datetime']));
        $checkOutTime = date('g:i A', strtotime($booking['check_out_datetime']));
        $roomAssignment = $booking['room_number'] ? "Room " . $booking['room_number'] : "Room will be assigned upon check-in";
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Booking Confirmed</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px; background: #f8f9fa; border-radius: 0 0 8px 8px; }
                .booking-details { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #28a745; }
                .detail-row { display: flex; justify-content: space-between; margin: 10px 0; padding: 8px 0; border-bottom: 1px solid #eee; }
                .detail-label { font-weight: bold; color: #555; }
                .detail-value { color: #333; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
                .success-badge { background: #28a745; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: bold; }
                .contact-info { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Booking Confirmed!</h1>
                    <p>Your reservation has been successfully confirmed</p>
                    <div class='success-badge'>CONFIRMED</div>
                </div>
                
                <div class='content'>
                    <p>Dear {$customerName},</p>
                    
                    <p>Great news! Your booking at Richard's Hotel has been <strong>confirmed</strong>. We're excited to welcome you!</p>
                    
                    <div class='booking-details'>
                        <h3 style='color: #28a745; margin-top: 0;'>Booking Details</h3>
                        
                        <div class='detail-row'>
                            <span class='detail-label'>Booking ID:</span>
                            <span class='detail-value'>#{$booking['id']}</span>
                        </div>
                        
                        <div class='detail-row'>
                            <span class='detail-label'>Room Type:</span>
                            <span class='detail-value'>{$booking['type_name']}</span>
                        </div>
                        
                        <div class='detail-row'>
                            <span class='detail-label'>Room Assignment:</span>
                            <span class='detail-value'>{$roomAssignment}</span>
                        </div>
                        
                        <div class='detail-row'>
                            <span class='detail-label'>Check-in:</span>
                            <span class='detail-value'>{$checkInDate} at {$checkInTime}</span>
                        </div>
                        
                        <div class='detail-row'>
                            <span class='detail-label'>Check-out:</span>
                            <span class='detail-value'>{$checkOutDate} at {$checkOutTime}</span>
                        </div>
                        
                        <div class='detail-row'>
                            <span class='detail-label'>Duration:</span>
                            <span class='detail-value'>{$booking['duration_hours']} hours</span>
                        </div>
                        
                        <div class='detail-row'>
                            <span class='detail-label'>Total Amount:</span>
                            <span class='detail-value' style='font-weight: bold; color: #28a745;'>₱" . number_format($booking['total_price'], 2) . "</span>
                        </div>
                        
                        <div class='detail-row'>
                            <span class='detail-label'>Payment Status:</span>
                            <span class='detail-value' style='color: #28a745; font-weight: bold;'>PAID</span>
                        </div>
                    </div>
                    
                    <div class='contact-info'>
                        <h4 style='margin-top: 0; color: #555;'>Need Assistance?</h4>
                        <p style='margin: 5px 0;'>If you have any questions about this cancellation or need assistance with a new booking, please contact us:</p>
                        <p style='margin: 5px 0;'><strong>Phone:</strong> +63 123 456 7890</p>
                        <p style='margin: 5px 0;'><strong>Email:</strong> richardshotelmanagement@gmail.com</p>
                        <p style='margin: 5px 0;'><strong>Address:</strong> Richard's Hotel, Libertad, Roxas, Oriental Mindoro</p>
                    </div>
                    
                    <p><strong>What's Next?</strong></p>
                    <ul>
                        <li>Arrive at the hotel on your check-in date and time</li>
                        <li>Present a valid ID at the front desk</li>
                        <li>Enjoy your stay at RHMS Hotel!</li>
                    </ul>
                    
                    <p>Thank you for choosing RHMS Hotel. We look forward to providing you with an exceptional experience!</p>
                    
                    <p>Best regards,<br><strong>RHMS Hotel Team</strong></p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated confirmation email. Please do not reply to this email.</p>
                    <p>If you have any questions, please contact us using the information provided above.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get booking cancellation email template
     */
    private function getBookingCancellationTemplate($booking) {
        $customerName = $booking['first_name'] . ' ' . $booking['last_name'];
        $checkInDate = date('F j, Y', strtotime($booking['check_in_datetime']));
        $checkInTime = date('g:i A', strtotime($booking['check_in_datetime']));
        $checkOutDate = date('F j, Y', strtotime($booking['check_out_datetime']));
        $checkOutTime = date('g:i A', strtotime($booking['check_out_datetime']));
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Booking Cancelled</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px; background: #f8f9fa; border-radius: 0 0 8px 8px; }
                .booking-details { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #dc3545; }
                .detail-row { display: flex; justify-content: space-between; margin: 10px 0; padding: 8px 0; border-bottom: 1px solid #eee; }
                .detail-label { font-weight: bold; color: #555; }
                .detail-value { color: #333; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
                .cancelled-badge { background: #dc3545; color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: bold; }
                .contact-info { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .refund-info { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>❌ Booking Cancelled</h1>
                    <p>Your reservation has been cancelled</p>
                    <div class='cancelled-badge'>CANCELLED</div>
                </div>
                
                <div class='content'>
                    <p>Dear {$customerName},</p>
                    
                    <p>We regret to inform you that your booking at Richard's Hotel has been <strong>cancelled</strong> by our administration team.</p>
                    
                    <div class='booking-details'>
                        <h3 style='color: #dc3545; margin-top: 0;'>Cancelled Booking Details</h3>
                        
                        <div class='detail-row'>
                            <span class='detail-label'>Booking ID:</span>
                            <span class='detail-value'>#{$booking['id']}</span>
                        </div>
                        
                        <div class='detail-row'>
                            <span class='detail-label'>Room Type:</span>
                            <span class='detail-value'>{$booking['type_name']}</span>
                        </div>
                        
                        <div class='detail-row'>
                            <span class='detail-label'>Original Check-in:</span>
                            <span class='detail-value'>{$checkInDate} at {$checkInTime}</span>
                        </div>
                        
                        <div class='detail-row'>
                            <span class='detail-label'>Original Check-out:</span>
                            <span class='detail-value'>{$checkOutDate} at {$checkOutTime}</span>
                        </div>
                        
                        <div class='detail-row'>
                            <span class='detail-label'>Duration:</span>
                            <span class='detail-value'>{$booking['duration_hours']} hours</span>
                        </div>
                        
                        <div class='detail-row'>
                            <span class='detail-label'>Amount:</span>
                            <span class='detail-value'>₱" . number_format($booking['total_price'], 2) . "</span>
                        </div>
                    </div>
                    
                    <div class='refund-info'>
                        <h4 style='margin-top: 0; color: #856404;'>Refund Information</h4>
                        <p style='margin: 5px 0;'>If you have already made a payment, our team will process your refund within 3-5 business days.</p>
                        <p style='margin: 5px 0;'>You will receive a separate email confirmation once the refund has been processed.</p>
                    </div>
                    
                    <div class='contact-info'>
                        <h4 style='margin-top: 0; color: #555;'>Need Assistance?</h4>
                        <p style='margin: 5px 0;'>If you have any questions about this cancellation or need assistance with a new booking, please contact us:</p>
                        <p style='margin: 5px 0;'><strong>Phone:</strong> +63 123 456 7890</p>
                        <p style='margin: 5px 0;'><strong>Email:</strong> richardshotelmanagement@gmail.com</p>
                        <p style='margin: 5px 0;'><strong>Address:</strong> Richard's Hotel, Libertad, Roxas, Oriental Mindoro</p>
                    </div>
                    
                    <p>We sincerely apologize for any inconvenience this cancellation may have caused. We hope to serve you better in the future.</p>
                    
                    <p>Thank you for your understanding.</p>
                    
                    <p>Best regards,<br><strong>RHMS Hotel Team</strong></p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated notification email. Please do not reply to this email.</p>
                    <p>If you have any questions, please contact us using the information provided above.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>