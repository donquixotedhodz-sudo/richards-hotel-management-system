<?php
require_once '../../centralized-login/SessionManager.php';
require_once '../../config/database.php';

// Require admin authentication
SessionManager::requireAdmin();

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get':
            getBooking($conn);
            break;
            
        case 'update':
            updateBooking($conn);
            break;
            
        case 'details':
            getBookingDetails($conn);
            break;
            
        case 'delete':
            deleteBooking($conn);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function getBooking($conn) {
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        throw new Exception('Booking ID is required');
    }
    
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
        WHERE b.id = ?
    ");
    
    $stmt->execute([$id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    echo json_encode([
        'success' => true,
        'booking' => $booking
    ]);
}

function updateBooking($conn) {
    $id = (int)($_POST['booking_id'] ?? 0);
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $bookingStatus = $_POST['booking_status'] ?? '';
    $checkInDateTime = $_POST['check_in_datetime'] ?? '';
    $checkOutDateTime = $_POST['check_out_datetime'] ?? '';
    $specialRequests = trim($_POST['special_requests'] ?? '');
    
    // Validation
    if (!$id) {
        throw new Exception('Booking ID is required');
    }
    
    if (empty($customerName)) {
        throw new Exception('Customer name is required');
    }
    
    if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Valid customer email is required');
    }
    
    if (empty($customerPhone)) {
        throw new Exception('Customer phone is required');
    }
    
    if (!in_array($bookingStatus, ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'])) {
        throw new Exception('Invalid booking status');
    }
    
    if (empty($checkInDateTime) || empty($checkOutDateTime)) {
        throw new Exception('Check-in and check-out dates are required');
    }
    
    // Validate dates
    $checkIn = new DateTime($checkInDateTime);
    $checkOut = new DateTime($checkOutDateTime);
    
    if ($checkOut <= $checkIn) {
        throw new Exception('Check-out date must be after check-in date');
    }
    
    // Calculate duration in hours
    $interval = $checkIn->diff($checkOut);
    $durationHours = ($interval->days * 24) + $interval->h + ($interval->i / 60);
    $durationHours = ceil($durationHours); // Round up to nearest hour
    
    // Update booking
    $stmt = $conn->prepare("
        UPDATE bookings SET 
            customer_name = ?,
            customer_email = ?,
            customer_phone = ?,
            booking_status = ?,
            check_in_datetime = ?,
            check_out_datetime = ?,
            duration_hours = ?,
            special_requests = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $customerName,
        $customerEmail,
        $customerPhone,
        $bookingStatus,
        $checkInDateTime,
        $checkOutDateTime,
        $durationHours,
        $specialRequests,
        $id
    ]);
    
    if (!$result) {
        throw new Exception('Failed to update booking');
    }
    
    // If booking is being confirmed, assign a room if not already assigned
    if ($bookingStatus === 'confirmed') {
        // Check if room is already assigned
        $checkStmt = $conn->prepare("SELECT room_id, room_type_id FROM bookings WHERE id = ?");
        $checkStmt->execute([$id]);
        $bookingData = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bookingData && !$bookingData['room_id']) {
            // Find an available room of the specified type
            $roomStmt = $conn->prepare("
                SELECT id FROM rooms 
                WHERE room_type_id = ? 
                AND status = 'available' 
                AND id NOT IN (
                    SELECT DISTINCT room_id FROM bookings 
                    WHERE room_id IS NOT NULL 
                    AND booking_status IN ('confirmed', 'checked_in')
                    AND (
                        (check_in_datetime <= ? AND check_out_datetime >= ?)
                        OR (check_in_datetime > ?)
                    )
                )
                ORDER BY room_number ASC
                LIMIT 1
            ");
            
            $roomStmt->execute([
                $bookingData['room_type_id'],
                $checkInDateTime,
                $checkInDateTime,
                $checkInDateTime
            ]);
            $room = $roomStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($room) {
                // Assign the room to the booking
                $assignStmt = $conn->prepare("UPDATE bookings SET room_id = ? WHERE id = ?");
                $assignStmt->execute([$room['id'], $id]);
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking updated successfully'
    ]);
}

function getBookingDetails($conn) {
    $id = (int)($_GET['id'] ?? 0);
    
    if (!$id) {
        throw new Exception('Booking ID is required');
    }
    
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
        WHERE b.id = ?
    ");
    
    $stmt->execute([$id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception('Booking not found');
    }
    
    // Generate HTML for booking details
    $html = '
    <div class="row">
        <div class="col-md-6">
            <h6 class="fw-bold mb-3">Customer Information</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>Name:</strong></td>
                    <td>' . htmlspecialchars($booking['customer_name']) . '</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>' . htmlspecialchars($booking['customer_email']) . '</td>
                </tr>
                <tr>
                    <td><strong>Phone:</strong></td>
                    <td>' . htmlspecialchars($booking['customer_phone']) . '</td>
                </tr>
                <tr>
                    <td><strong>Address:</strong></td>
                    <td>' . htmlspecialchars($booking['customer_address']) . '</td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <h6 class="fw-bold mb-3">Booking Information</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>Booking ID:</strong></td>
                    <td>#' . $booking['id'] . '</td>
                </tr>
                <tr>
                    <td><strong>Room Type:</strong></td>
                    <td>' . htmlspecialchars($booking['type_name']) . '</td>
                </tr>
                <tr>
                    <td><strong>Room Number:</strong></td>
                    <td>' . ($booking['room_number'] ? 'Room ' . htmlspecialchars($booking['room_number']) : 'Not assigned') . '</td>
                </tr>
                <tr>
                    <td><strong>Duration:</strong></td>
                    <td>' . $booking['duration_hours'] . ' hours</td>
                </tr>
                <tr>
                    <td><strong>Total Price:</strong></td>
                    <td><strong>₱' . number_format($booking['total_price'], 2) . '</strong></td>
                </tr>
            </table>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-6">
            <h6 class="fw-bold mb-3">Check-in & Check-out</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>Check-in:</strong></td>
                    <td>' . date('M j, Y g:i A', strtotime($booking['check_in_datetime'])) . '</td>
                </tr>
                <tr>
                    <td><strong>Check-out:</strong></td>
                    <td>' . date('M j, Y g:i A', strtotime($booking['check_out_datetime'])) . '</td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <h6 class="fw-bold mb-3">Status Information</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>Booking Status:</strong></td>
                    <td><span class="badge bg-' . getStatusBadgeClass($booking['booking_status']) . '">' . ucfirst($booking['booking_status']) . '</span></td>
                </tr>
                <tr>
                    <td><strong>Payment Status:</strong></td>
                    <td><span class="badge bg-' . getPaymentBadgeClass($booking['payment_status']) . '">' . ucfirst($booking['payment_status']) . '</span></td>
                </tr>
                <tr>
                    <td><strong>Created:</strong></td>
                    <td>' . date('M j, Y g:i A', strtotime($booking['created_at'])) . '</td>
                </tr>
            </table>
        </div>
    </div>';
    
    if (!empty($booking['special_requests'])) {
        $html .= '
        <div class="row mt-3">
            <div class="col-12">
                <h6 class="fw-bold mb-3">Special Requests</h6>
                <div class="alert alert-info">
                    ' . nl2br(htmlspecialchars($booking['special_requests'])) . '
                </div>
            </div>
        </div>';
    }
    
    if (!empty($booking['proof_of_payment'])) {
        $html .= '
        <div class="row mt-3">
            <div class="col-12">
                <h6 class="fw-bold mb-3">Proof of Payment</h6>
                <div class="text-center">
                    <img src="../uploads/payments/' . htmlspecialchars($booking['proof_of_payment']) . '" 
                         alt="Proof of Payment" 
                         class="img-fluid" 
                         style="max-height: 300px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
            </div>
        </div>';
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
}

function deleteBooking($conn) {
    $id = (int)($_POST['booking_id'] ?? 0);
    
    if (!$id) {
        throw new Exception('Booking ID is required');
    }
    
    // Check if booking exists
    $stmt = $conn->prepare("SELECT id FROM bookings WHERE id = ?");
    $stmt->execute([$id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Booking not found');
    }
    
    // Delete booking
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    if (!$result) {
        throw new Exception('Failed to delete booking');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking deleted successfully'
    ]);
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'confirmed':
            return 'success';
        case 'cancelled':
            return 'danger';
        case 'checked_in':
            return 'primary';
        case 'checked_out':
            return 'secondary';
        default:
            return 'light';
    }
}

function getPaymentBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'paid':
            return 'success';
        case 'refunded':
            return 'info';
        default:
            return 'light';
    }
}
?>