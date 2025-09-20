<?php
require_once '../config/database.php';
require_once 'BookingNotificationService.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Email Debug Test</h2>";

try {
    // Test database connection
    echo "<h3>1. Testing Database Connection</h3>";
    if ($conn) {
        echo "✅ Database connection successful<br>";
        
        // Test booking query
        $testQuery = "SELECT COUNT(*) as count FROM bookings WHERE status = 'confirmed'";
        $stmt = $conn->prepare($testQuery);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Found " . $result['count'] . " confirmed bookings<br>";
        
        // Get a sample booking for testing
        $sampleQuery = "
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
            WHERE b.status = 'confirmed'
            LIMIT 1
        ";
        $stmt = $conn->prepare($sampleQuery);
        $stmt->execute();
        $sampleBooking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sampleBooking) {
            echo "✅ Sample booking found: ID " . $sampleBooking['id'] . " for " . $sampleBooking['first_name'] . " " . $sampleBooking['last_name'] . "<br>";
            echo "📧 Email: " . $sampleBooking['user_email'] . "<br>";
        } else {
            echo "⚠️ No confirmed bookings found for testing<br>";
        }
    } else {
        echo "❌ Database connection failed<br>";
    }
    
    echo "<h3>2. Testing Email Service Initialization</h3>";
    $emailService = new BookingNotificationService($conn);
    echo "✅ Email service initialized successfully<br>";
    
    echo "<h3>3. Testing Email Configuration</h3>";
    // Test basic email functionality
    $testEmail = 'test@example.com'; // Change this to your test email
    
    // Create a simple test email
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'richardshotelmanagement@gmail.com';
    $mail->Password = 'dttz szma pzjp fllz';
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->setFrom('richardshotelmanagement@gmail.com', 'RHMS Hotel');
    $mail->isHTML(true);
    
    echo "✅ SMTP configuration set<br>";
    
    if ($sampleBooking && $sampleBooking['user_email']) {
        echo "<h3>4. Testing Booking Email Send</h3>";
        echo "Attempting to send confirmation email to: " . $sampleBooking['user_email'] . "<br>";
        
        $result = $emailService->sendBookingConfirmation($sampleBooking['id']);
        
        if ($result) {
            echo "✅ Email sent successfully!<br>";
        } else {
            echo "❌ Email sending failed. Check error logs.<br>";
        }
    } else {
        echo "<h3>4. No Test Data Available</h3>";
        echo "⚠️ Cannot test email sending - no booking data with valid email found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>5. Error Log Check</h3>";
echo "Check your PHP error log for detailed error messages.<br>";
echo "Common log locations:<br>";
echo "- XAMPP: C:\\xampp\\php\\logs\\php_error_log<br>";
echo "- Or check your server's error log<br>";

?>