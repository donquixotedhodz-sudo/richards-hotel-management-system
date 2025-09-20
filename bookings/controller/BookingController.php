<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../centralized-login/SessionManager.php';

class BookingController {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    /**
     * Handle booking form submission
     */
    public function createBooking() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->returnResponse(false, 'Invalid request method');
            return;
        }
        
        // Validate required fields
        $requiredFields = ['customer_name', 'customer_email', 'customer_phone', 'customer_address', 
                          'room_type_id', 'check_in_datetime', 'duration_hours'];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $this->returnResponse(false, 'Please fill in all required fields');
                return;
            }
        }
        
        // Sanitize input data
        $customerName = trim($_POST['customer_name']);
        $customerEmail = trim($_POST['customer_email']);
        $customerPhone = trim($_POST['customer_phone']);
        $customerAddress = trim($_POST['customer_address']);
        $roomTypeId = (int)$_POST['room_type_id'];
        $checkInDatetime = $_POST['check_in_datetime'];
        $durationHours = (int)$_POST['duration_hours'];
        $specialRequests = trim($_POST['special_requests'] ?? '');
        
        // Validate email format
        if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $this->returnResponse(false, 'Please enter a valid email address');
            return;
        }
        
        // Validate datetime
        $checkInDate = new DateTime($checkInDatetime);
        $now = new DateTime();
        if ($checkInDate <= $now) {
            $this->returnResponse(false, 'Check-in date must be in the future');
            return;
        }
        
        // Calculate check-out datetime
        $checkOutDate = clone $checkInDate;
        $checkOutDate->add(new DateInterval('PT' . $durationHours . 'H'));
        
        // Get price for the selected room type and duration
        $basePrice = $this->getBookingPrice($roomTypeId, $durationHours);
        if (!$basePrice) {
            $this->returnResponse(false, 'Invalid room type or duration selected');
            return;
        }
        
        // Calculate total price with 12% booking fee
        $bookingFee = $basePrice * 0.12;
        $price = $basePrice + $bookingFee;
        
        // Handle file upload for proof of payment
        $proofOfPayment = null;
        if (isset($_FILES['proof_of_payment']) && $_FILES['proof_of_payment']['error'] === UPLOAD_ERR_OK) {
            $proofOfPayment = $this->handleFileUpload($_FILES['proof_of_payment']);
            if (!$proofOfPayment) {
                $this->returnResponse(false, 'Error uploading proof of payment');
                return;
            }
        }
        
        // Get user ID if logged in
        $userId = null;
        if (SessionManager::isLoggedIn()) {
            $user = SessionManager::getCurrentUser();
            $userId = $user['id'];
        }

        // Check for duplicate bookings (prevent double submission)
        $duplicateCheck = $this->checkForDuplicateBooking(
            $customerEmail, $customerPhone, $roomTypeId, 
            $checkInDate->format('Y-m-d H:i:s'), $durationHours
        );
        
        if ($duplicateCheck) {
            $this->returnResponse(false, 'A similar booking already exists. Please check your bookings or contact support.');
            return;
        }

        try {
            // Insert booking into database
            $stmt = $this->conn->prepare("
                INSERT INTO bookings (
                    user_id, customer_name, customer_address, customer_phone, customer_email,
                    room_type_id, duration_hours, total_price, check_in_datetime, check_out_datetime,
                    proof_of_payment, special_requests, booking_status, payment_status
                ) VALUES (
                    :user_id, :customer_name, :customer_address, :customer_phone, :customer_email,
                    :room_type_id, :duration_hours, :total_price, :check_in_datetime, :check_out_datetime,
                    :proof_of_payment, :special_requests, 'pending', 'pending'
                )
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':customer_name' => $customerName,
                ':customer_address' => $customerAddress,
                ':customer_phone' => $customerPhone,
                ':customer_email' => $customerEmail,
                ':room_type_id' => $roomTypeId,
                ':duration_hours' => $durationHours,
                ':total_price' => $price,
                ':check_in_datetime' => $checkInDate->format('Y-m-d H:i:s'),
                ':check_out_datetime' => $checkOutDate->format('Y-m-d H:i:s'),
                ':proof_of_payment' => $proofOfPayment,
                ':special_requests' => $specialRequests
            ]);
            
            $bookingId = $this->conn->lastInsertId();
            
            // Automatically assign an available room to the booking
            $assignedRoomId = $this->assignRoomToBooking($bookingId, $roomTypeId);
            
            // If auto_confirm is set, also confirm the booking
            if (isset($_POST['auto_confirm']) && $_POST['auto_confirm'] === '1') {
                $this->confirmBooking($bookingId);
            }
            
            // Return success response (JSON for AJAX, redirect for regular form)
            $this->returnResponse(true, 'Booking submitted successfully! Booking ID: ' . $bookingId, $bookingId);
            
        } catch (PDOException $e) {
            error_log('Booking creation error: ' . $e->getMessage());
            $this->returnResponse(false, 'Error creating booking. Please try again.');
        }
    }
    
    /**
     * Check for duplicate bookings to prevent double submission
     */
    private function checkForDuplicateBooking($customerEmail, $customerPhone, $roomTypeId, $checkInDatetime, $durationHours) {
        try {
            // Check for bookings with same details within the last 5 minutes
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count FROM bookings 
                WHERE (customer_email = :email OR customer_phone = :phone)
                AND room_type_id = :room_type_id 
                AND check_in_datetime = :check_in_datetime
                AND duration_hours = :duration_hours
                AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ");
            
            $stmt->execute([
                ':email' => $customerEmail,
                ':phone' => $customerPhone,
                ':room_type_id' => $roomTypeId,
                ':check_in_datetime' => $checkInDatetime,
                ':duration_hours' => $durationHours
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
            
        } catch (PDOException $e) {
            error_log('Duplicate check error: ' . $e->getMessage());
            return false; // If check fails, allow booking to proceed
        }
    }

    /**
     * Get booking price based on room type and duration
     */
    private function getBookingPrice($roomTypeId, $durationHours) {
        try {
            $stmt = $this->conn->prepare("
                SELECT price FROM booking_rates 
                WHERE room_type_id = :room_type_id AND duration_hours = :duration_hours
            ");
            $stmt->execute([
                ':room_type_id' => $roomTypeId,
                ':duration_hours' => $durationHours
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['price'] : null;
            
        } catch (PDOException $e) {
            error_log('Error fetching booking price: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Handle file upload for proof of payment
     */
    private function handleFileUpload($file) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        // Validate file type
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }
        
        // Validate file size
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        // Create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../uploads/payments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'payment_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $filename;
        }
        
        return false;
    }
    
    /**
     * Assign an available room to a booking
     */
    private function assignRoomToBooking($bookingId, $roomTypeId) {
        try {
            // Find an available room of the specified type
            $stmt = $this->conn->prepare("
                SELECT id FROM rooms 
                WHERE room_type_id = :room_type_id 
                AND status = 'available' 
                AND id NOT IN (
                    SELECT DISTINCT room_id FROM bookings 
                    WHERE room_id IS NOT NULL 
                    AND booking_status IN ('confirmed', 'checked_in')
                    AND (
                        (check_in_datetime <= NOW() AND check_out_datetime >= NOW())
                        OR (check_in_datetime > NOW())
                    )
                )
                ORDER BY room_number ASC
                LIMIT 1
            ");
            
            $stmt->execute([':room_type_id' => $roomTypeId]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($room) {
                // Assign the room to the booking
                $updateStmt = $this->conn->prepare("
                    UPDATE bookings 
                    SET room_id = :room_id 
                    WHERE id = :booking_id
                ");
                
                $updateStmt->execute([
                    ':room_id' => $room['id'],
                    ':booking_id' => $bookingId
                ]);
                
                return $room['id'];
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log('Error assigning room to booking: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Confirm booking and assign room
     */
    public function confirmBooking($bookingId) {
        try {
            // Get booking details
            $stmt = $this->conn->prepare("SELECT room_type_id, room_id FROM bookings WHERE id = ?");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                throw new Exception('Booking not found');
            }
            
            // Assign room if not already assigned
            if (!$booking['room_id']) {
                $roomId = $this->assignRoomToBooking($bookingId, $booking['room_type_id']);
                if (!$roomId) {
                    throw new Exception('No available rooms of this type');
                }
            }
            
            // Update booking status to confirmed
            $stmt = $this->conn->prepare("
                UPDATE bookings 
                SET booking_status = 'confirmed' 
                WHERE id = ?
            ");
            
            $stmt->execute([$bookingId]);
            
            return true;
            
        } catch (Exception $e) {
            error_log('Error confirming booking: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get booking rates for AJAX requests
     */
    public function getBookingRates() {
        try {
            $stmt = $this->conn->prepare("
                SELECT br.room_type_id, br.duration_hours, br.price, rt.type_name
                FROM booking_rates br
                JOIN room_types rt ON br.room_type_id = rt.id
                ORDER BY br.room_type_id, br.duration_hours
            ");
            $stmt->execute();
            
            $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode($rates);
            
        } catch (PDOException $e) {
            error_log('Error fetching booking rates: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error fetching rates']);
        }
    }
    
    /**
     * Return response based on request type (AJAX or regular form)
     */
    private function returnResponse($success, $message, $bookingId = null) {
        // Check if this is an AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        // Also check for fetch requests (modern AJAX)
        $isFetch = isset($_SERVER['HTTP_ACCEPT']) && 
                   strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        
        if ($isAjax || $isFetch) {
            // Return JSON response for AJAX requests
            header('Content-Type: application/json');
            
            $response = [
                'success' => $success,
                'message' => $message
            ];
            
            if ($bookingId) {
                $response['booking_id'] = $bookingId;
            }
            
            http_response_code($success ? 200 : 400);
            echo json_encode($response);
            exit();
        } else {
            // Regular form submission - redirect as before
            if ($success) {
                header('Location: ../../index.php?booking_success=' . urlencode($message) . '#booking');
            } else {
                header('Location: ../../index.php?booking_error=' . urlencode($message) . '#booking');
            }
            exit();
        }
    }
    
    /**
     * Redirect with error message (kept for backward compatibility)
     */
    private function redirectWithError($message) {
        $this->returnResponse(false, $message);
    }
    
    /**
     * Redirect with success message (kept for backward compatibility)
     */
    private function redirectWithSuccess($message) {
        $this->returnResponse(true, $message);
    }
}

// Handle requests
if (isset($_GET['action'])) {
    $controller = new BookingController();
    
    switch ($_GET['action']) {
        case 'get_rates':
            $controller->getBookingRates();
            break;
        default:
            header('Location: ../../index.php');
            exit();
    }
} else {
    // Default action is to create booking
    $controller = new BookingController();
    $controller->createBooking();
}
?>