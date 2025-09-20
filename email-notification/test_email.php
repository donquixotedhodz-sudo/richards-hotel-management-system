<?php
/**
 * Test file for BookingNotificationService
 * This file can be used to test email functionality without affecting actual bookings
 */

require_once __DIR__ . '/BookingNotificationService.php';
require_once __DIR__ . '/../config/database.php';

// Test the email notification service
function testEmailNotifications() {
    try {
        $emailService = new BookingNotificationService();
        
        echo "<h2>Testing Email Notification Service</h2>";
        echo "<p>This test will attempt to send emails using the BookingNotificationService.</p>";
        
        // Get a sample booking from the database for testing
        global $conn;
        $stmt = $conn->prepare("
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
            LIMIT 1
        ");
        $stmt->execute();
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            echo "<p style='color: red;'>No bookings found in database for testing.</p>";
            return;
        }
        
        echo "<h3>Sample Booking Data:</h3>";
        echo "<ul>";
        echo "<li><strong>Booking ID:</strong> " . $booking['id'] . "</li>";
        echo "<li><strong>Customer:</strong> " . $booking['first_name'] . " " . $booking['last_name'] . "</li>";
        echo "<li><strong>Email:</strong> " . $booking['user_email'] . "</li>";
        echo "<li><strong>Room Type:</strong> " . $booking['type_name'] . "</li>";
        echo "<li><strong>Status:</strong> " . $booking['booking_status'] . "</li>";
        echo "</ul>";
        
        echo "<h3>Testing Email Functions:</h3>";
        
        // Test confirmation email
        echo "<p><strong>Testing Confirmation Email...</strong></p>";
        if ($emailService->sendBookingConfirmation($booking['id'])) {
            echo "<p style='color: green;'>✅ Confirmation email sent successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to send confirmation email.</p>";
        }
        
        // Test cancellation email
        echo "<p><strong>Testing Cancellation Email...</strong></p>";
        if ($emailService->sendBookingCancellation($booking['id'])) {
            echo "<p style='color: green;'>✅ Cancellation email sent successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to send cancellation email.</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error during testing: " . $e->getMessage() . "</p>";
    }
}

// Run the test if accessed directly
if (basename($_SERVER['PHP_SELF']) == 'test_email.php') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Email Notification Test</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            h2 { color: #333; }
            h3 { color: #666; }
            ul { background: #f5f5f5; padding: 15px; border-radius: 5px; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="warning">
            <strong>⚠️ Warning:</strong> This test will send actual emails to the customer email addresses in your database. 
            Make sure you're using test data or have permission to send test emails.
        </div>
        
        <?php testEmailNotifications(); ?>
        
        <hr>
        <p><small>Test completed at <?php echo date('Y-m-d H:i:s'); ?></small></p>
    </body>
    </html>
    <?php
}
?>