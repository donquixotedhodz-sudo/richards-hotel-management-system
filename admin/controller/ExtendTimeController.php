<?php
require_once '../../centralized-login/SessionManager.php';
require_once '../../config/database.php';

// Require admin authentication
SessionManager::requireAdmin();

header('Content-Type: application/json');

function respond($success, $message, $data = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

try {
    // Read JSON input
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        respond(false, 'Invalid JSON payload');
    }

    $bookingId = isset($payload['booking_id']) ? (int)$payload['booking_id'] : 0;
    $extendHours = isset($payload['extend_hours']) ? (int)$payload['extend_hours'] : 0;

    if (!$bookingId || !$extendHours) {
        respond(false, 'Booking ID and extend hours are required');
    }

    // Validate allowable range
    if ($extendHours < 1 || $extendHours > 12) {
        respond(false, 'Extend hours must be between 1 and 12');
    }

    // Get current admin
    $currentUser = SessionManager::getCurrentUser();
    $adminId = $currentUser['id'] ?? null;

    // Fetch booking (avoid selecting columns that may not exist)
    $stmt = $conn->prepare("SELECT b.* FROM bookings b WHERE b.id = ?");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        respond(false, 'Booking not found');
    }

    // Only allow extension for active bookings
    $status = strtolower($booking['booking_status']);
    if (!in_array($status, ['confirmed', 'checked_in'])) {
        respond(false, 'Booking cannot be extended in its current status');
    }

    // Determine hourly rate (use default 200 if not configured)
    $hourlyRate = 200.0;
    $additionalCost = $extendHours * $hourlyRate;

    // Calculate new checkout and totals
    $currentCheckOut = new DateTime($booking['check_out_datetime']);
    $newCheckOut = clone $currentCheckOut;
    $newCheckOut->add(new DateInterval('PT' . $extendHours . 'H'));

    $newDurationHours = (int)$booking['duration_hours'] + $extendHours;
    $newTotalPrice = (float)$booking['total_price'] + $additionalCost;

    // Update booking
    $update = $conn->prepare("UPDATE bookings SET duration_hours = ?, total_price = ?, check_out_datetime = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $ok = $update->execute([$newDurationHours, $newTotalPrice, $newCheckOut->format('Y-m-d H:i:s'), $bookingId]);
    if (!$ok) {
        respond(false, 'Failed to update booking');
    }

    // Log extension in booking_extensions (non-fatal if logging fails)
    try {
        $log = $conn->prepare("INSERT INTO booking_extensions (booking_id, user_id, extended_by, additional_hours, additional_cost, hourly_rate, previous_checkout, new_checkout, extension_reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $log->execute([
            $bookingId,
            $booking['user_id'] ?? null,
            $adminId,
            $extendHours,
            $additionalCost,
            $hourlyRate,
            $booking['check_out_datetime'],
            $newCheckOut->format('Y-m-d H:i:s'),
            'Admin extended booking time'
        ]);
    } catch (Exception $logEx) {
        error_log('Booking extension log failed: ' . $logEx->getMessage());
        // Proceed without failing the main operation
    }

    respond(true, 'Time extended successfully', [
        'additional_hours' => $extendHours,
        'additional_cost' => $additionalCost,
        'new_total' => $newTotalPrice,
        'new_checkout' => $newCheckOut->format('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    respond(false, $e->getMessage());
}