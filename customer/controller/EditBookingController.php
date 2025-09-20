<?php
require_once '../../centralized-login/SessionManager.php';
require_once '../../config/database.php';

class EditBookingController {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    /**
     * Update booking details
     */
    public function updateBooking() {
        // Ensure user is authenticated
        SessionManager::requireCustomer();
        $currentUser = SessionManager::getCurrentUser();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->returnResponse(false, 'Invalid request method');
            return;
        }
        
        // Validate required fields
        $requiredFields = ['booking_id', 'customer_name', 'customer_email', 'customer_phone', 'check_in_datetime', 'check_out_datetime'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $this->returnResponse(false, "Field '$field' is required");
                return;
            }
        }
        
        $bookingId = (int)$_POST['booking_id'];
        $customerName = trim($_POST['customer_name']);
        $customerEmail = trim($_POST['customer_email']);
        $customerPhone = trim($_POST['customer_phone']);
        $checkInDateTime = $_POST['check_in_datetime'];
        $checkOutDateTime = $_POST['check_out_datetime'];
        $specialRequests = isset($_POST['special_requests']) ? trim($_POST['special_requests']) : '';
        
        // Validate email format
        if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $this->returnResponse(false, 'Invalid email format');
            return;
        }
        
        // Validate dates
        $checkIn = new DateTime($checkInDateTime);
        $checkOut = new DateTime($checkOutDateTime);
        $now = new DateTime();
        
        if ($checkIn < $now) {
            $this->returnResponse(false, 'Check-in date cannot be in the past');
            return;
        }
        
        if ($checkOut <= $checkIn) {
            $this->returnResponse(false, 'Check-out date must be after check-in date');
            return;
        }
        
        try {
            // Verify booking belongs to current user and can be edited
            $stmt = $this->conn->prepare("
                SELECT * FROM bookings 
                WHERE id = ? AND (user_id = ? OR customer_email = ?)
                AND booking_status IN ('pending', 'confirmed')
            ");
            $stmt->execute([$bookingId, $currentUser['id'], $currentUser['email']]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                $this->returnResponse(false, 'Booking not found or cannot be edited');
                return;
            }
            
            // Calculate new duration and price
            $durationHours = $this->calculateDurationHours($checkIn, $checkOut);
            $newPrice = $this->calculatePrice($booking['room_type_id'], $durationHours);
            
            if ($newPrice === null) {
                $this->returnResponse(false, 'Unable to calculate price for the selected duration');
                return;
            }
            
            // Update booking
            $updateStmt = $this->conn->prepare("
                UPDATE bookings SET 
                    customer_name = ?,
                    customer_email = ?,
                    customer_phone = ?,
                    check_in_datetime = ?,
                    check_out_datetime = ?,
                    duration_hours = ?,
                    total_price = ?,
                    special_requests = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $updateStmt->execute([
                $customerName,
                $customerEmail,
                $customerPhone,
                $checkInDateTime,
                $checkOutDateTime,
                $durationHours,
                $newPrice,
                $specialRequests,
                $bookingId
            ]);
            
            $this->returnResponse(true, 'Booking updated successfully!', [
                'booking_id' => $bookingId,
                'new_price' => $newPrice,
                'duration_hours' => $durationHours
            ]);
            
        } catch (Exception $e) {
            error_log('Booking update error: ' . $e->getMessage());
            $this->returnResponse(false, 'Error updating booking. Please try again.');
        }
    }
    
    /**
     * Calculate duration in hours between two dates
     */
    private function calculateDurationHours($checkIn, $checkOut) {
        $interval = $checkIn->diff($checkOut);
        return ($interval->days * 24) + $interval->h + ($interval->i / 60);
    }
    
    /**
     * Calculate price based on room type and duration
     */
    private function calculatePrice($roomTypeId, $durationHours) {
        try {
            // Round duration to nearest valid booking duration
            $validDurations = [3, 12, 24];
            $closestDuration = $validDurations[0];
            
            foreach ($validDurations as $duration) {
                if ($durationHours <= $duration) {
                    $closestDuration = $duration;
                    break;
                }
            }
            
            // If duration is more than 24 hours, calculate daily rate
            if ($durationHours > 24) {
                $days = ceil($durationHours / 24);
                $stmt = $this->conn->prepare("
                    SELECT price FROM booking_rates 
                    WHERE room_type_id = ? AND duration_hours = 24
                ");
                $stmt->execute([$roomTypeId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    return $result['price'] * $days;
                }
            }
            
            // Get price for the closest duration
            $stmt = $this->conn->prepare("
                SELECT price FROM booking_rates 
                WHERE room_type_id = ? AND duration_hours = ?
            ");
            $stmt->execute([$roomTypeId, $closestDuration]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['price'] : null;
            
        } catch (PDOException $e) {
            error_log('Error calculating price: ' . $e->getMessage());
            return null;
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

// Handle the request
$controller = new EditBookingController();
$controller->updateBooking();
?>