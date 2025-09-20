<?php
require_once '../../centralized-login/SessionManager.php';
require_once '../../config/database.php';

class TimeExtensionController {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    /**
     * Handle time extension request
     */
    public function extendTime() {
        // Ensure user is authenticated
        SessionManager::requireCustomer();
        $currentUser = SessionManager::getCurrentUser();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->returnResponse(false, 'Invalid request method');
            return;
        }
        
        // Validate required fields
        if (empty($_POST['booking_id']) || empty($_POST['additional_hours'])) {
            $this->returnResponse(false, 'Booking ID and additional hours are required');
            return;
        }
        
        $bookingId = (int)$_POST['booking_id'];
        $additionalHours = (int)$_POST['additional_hours'];
        
        // Validate additional hours (minimum 1, maximum 12 for safety)
        if ($additionalHours < 1 || $additionalHours > 12) {
            $this->returnResponse(false, 'Additional hours must be between 1 and 12');
            return;
        }
        
        try {
            // Verify booking belongs to current user
            $stmt = $this->conn->prepare("
                SELECT * FROM bookings 
                WHERE id = ? AND (user_id = ? OR customer_email = ?)
                AND booking_status IN ('confirmed', 'checked_in')
            ");
            $stmt->execute([$bookingId, $currentUser['id'], $currentUser['email']]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                $this->returnResponse(false, 'Booking not found or cannot be extended');
                return;
            }
            
            // Check if booking is still active (not checked out or cancelled)
            if (in_array($booking['booking_status'], ['checked_out', 'cancelled'])) {
                $this->returnResponse(false, 'Cannot extend time for completed or cancelled bookings');
                return;
            }
            
            // Calculate additional cost (₱200 per hour)
            $hourlyRate = 200;
            $additionalCost = $additionalHours * $hourlyRate;
            
            // Calculate new check-out time
            $currentCheckOut = new DateTime($booking['check_out_datetime']);
            $newCheckOut = clone $currentCheckOut;
            $newCheckOut->add(new DateInterval('PT' . $additionalHours . 'H'));
            
            // Update booking with extended time and additional cost
            $newDurationHours = $booking['duration_hours'] + $additionalHours;
            $newTotalPrice = $booking['total_price'] + $additionalCost;
            
            $updateStmt = $this->conn->prepare("
                UPDATE bookings SET 
                    duration_hours = ?,
                    total_price = ?,
                    check_out_datetime = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $result = $updateStmt->execute([
                $newDurationHours,
                $newTotalPrice,
                $newCheckOut->format('Y-m-d H:i:s'),
                $bookingId
            ]);
            
            if (!$result) {
                throw new Exception('Failed to update booking');
            }
            
            // Log the extension for audit purposes
            $logStmt = $this->conn->prepare("
                INSERT INTO booking_extensions (
                    booking_id, 
                    user_id, 
                    additional_hours, 
                    additional_cost, 
                    extended_at
                ) VALUES (?, ?, ?, ?, NOW())
            ");
            
            // Note: This assumes we'll create a booking_extensions table
            // For now, we'll skip this and just update the main booking
            
            $this->returnResponse(true, 'Time extended successfully!', [
                'additional_hours' => $additionalHours,
                'additional_cost' => $additionalCost,
                'new_total' => $newTotalPrice,
                'new_checkout' => $newCheckOut->format('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            error_log('Time extension error: ' . $e->getMessage());
            $this->returnResponse(false, 'Error extending time. Please try again.');
        }
    }
    
    /**
     * Get booking details for extension
     */
    public function getBookingForExtension() {
        SessionManager::requireCustomer();
        $currentUser = SessionManager::getCurrentUser();
        
        if (empty($_GET['booking_id'])) {
            $this->returnResponse(false, 'Booking ID is required');
            return;
        }
        
        $bookingId = (int)$_GET['booking_id'];
        
        try {
            $stmt = $this->conn->prepare("
                SELECT b.*, rt.type_name 
                FROM bookings b
                LEFT JOIN room_types rt ON b.room_type_id = rt.id
                WHERE b.id = ? AND (b.user_id = ? OR b.customer_email = ?)
                AND b.booking_status IN ('confirmed', 'checked_in')
            ");
            $stmt->execute([$bookingId, $currentUser['id'], $currentUser['email']]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                $this->returnResponse(false, 'Booking not found or cannot be extended');
                return;
            }
            
            $this->returnResponse(true, 'Booking details retrieved', $booking);
            
        } catch (Exception $e) {
            error_log('Get booking error: ' . $e->getMessage());
            $this->returnResponse(false, 'Error retrieving booking details');
        }
    }
    
    /**
     * Get booking details for viewing (allows all booking statuses)
     */
    public function getBookingForViewing() {
        SessionManager::requireCustomer();
        $currentUser = SessionManager::getCurrentUser();
        
        if (empty($_GET['booking_id'])) {
            $this->returnResponse(false, 'Booking ID is required');
            return;
        }
        
        $bookingId = (int)$_GET['booking_id'];
        
        try {
            $stmt = $this->conn->prepare("
                SELECT b.*, rt.type_name, rt.description as room_description
                FROM bookings b
                LEFT JOIN room_types rt ON b.room_type_id = rt.id
                WHERE b.id = ? AND (b.user_id = ? OR b.customer_email = ?)
            ");
            $stmt->execute([$bookingId, $currentUser['id'], $currentUser['email']]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                $this->returnResponse(false, 'Booking not found');
                return;
            }
            
            $this->returnResponse(true, 'Booking details retrieved', $booking);
            
        } catch (Exception $e) {
            error_log('Get booking for viewing error: ' . $e->getMessage());
            $this->returnResponse(false, 'Error retrieving booking details');
        }
    }

    /**
     * Get booking details for editing (allows pending and confirmed bookings)
     */
    public function getBookingForEditing() {
        SessionManager::requireCustomer();
        $currentUser = SessionManager::getCurrentUser();
        
        if (empty($_GET['booking_id'])) {
            $this->returnResponse(false, 'Booking ID is required');
            return;
        }
        
        $bookingId = (int)$_GET['booking_id'];
        
        try {
            $stmt = $this->conn->prepare("
                SELECT b.*, rt.type_name 
                FROM bookings b
                LEFT JOIN room_types rt ON b.room_type_id = rt.id
                WHERE b.id = ? AND (b.user_id = ? OR b.customer_email = ?)
                AND b.booking_status IN ('pending', 'confirmed')
            ");
            $stmt->execute([$bookingId, $currentUser['id'], $currentUser['email']]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                $this->returnResponse(false, 'Booking not found or cannot be edited');
                return;
            }
            
            // Return booking data in the expected format for the frontend
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Booking details retrieved for editing',
                'booking' => $booking
            ]);
            
        } catch (Exception $e) {
            error_log('Get booking for editing error: ' . $e->getMessage());
            $this->returnResponse(false, 'Error retrieving booking details');
        }
    }
    
    /**
     * Return JSON response
     */
    private function returnResponse($success, $message, $data = null) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
    }
}

// Handle requests
if (isset($_GET['action'])) {
    $controller = new TimeExtensionController();
    
    switch ($_GET['action']) {
        case 'get_booking':
            $controller->getBookingForExtension();
            break;
        case 'get_booking_for_edit':
            $controller->getBookingForEditing();
            break;
        case 'get_booking_for_view':
            $controller->getBookingForViewing();
            break;
        case 'extend':
            $controller->extendTime();
            break;
        default:
            header('HTTP/1.0 404 Not Found');
            echo json_encode(['success' => false, 'message' => 'Action not found']);
    }
} else {
    // Default action is extend time
    $controller = new TimeExtensionController();
    $controller->extendTime();
}
?>