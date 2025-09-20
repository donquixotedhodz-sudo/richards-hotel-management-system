<?php
// AJAX handler for room availability checking
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$room_type_id = $_POST['room_type_id'] ?? '';
$check_in_datetime = $_POST['check_in_datetime'] ?? '';
$duration_hours = $_POST['duration_hours'] ?? '';

if (!$room_type_id || !$check_in_datetime || !$duration_hours) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields'
    ]);
    exit;
}

try {
    // Calculate check-out datetime
    $check_in = new DateTime($check_in_datetime);
    $check_out = clone $check_in;
    $check_out->add(new DateInterval('PT' . $duration_hours . 'H'));
    
    // Get room type name
    $room_type_stmt = $conn->prepare("SELECT type_name FROM room_types WHERE id = ?");
    $room_type_stmt->execute([$room_type_id]);
    $room_type_name = $room_type_stmt->fetchColumn();
    
    if (!$room_type_name) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid room type'
        ]);
        exit;
    }
    
    // Check for available rooms
    $availability_stmt = $conn->prepare("
        SELECT r.id, r.room_number, r.status,
               CASE 
                   WHEN r.status != 'available' THEN CONCAT('Room not available (Status: ', r.status, ')')
                   WHEN EXISTS (
                       SELECT 1 FROM bookings b 
                       WHERE b.room_id = r.id 
                       AND b.booking_status IN ('confirmed', 'checked_in')
                       AND (
                           (b.check_in_datetime < ? AND b.check_out_datetime > ?) OR
                           (b.check_in_datetime < ? AND b.check_out_datetime > ?) OR
                           (b.check_in_datetime >= ? AND b.check_in_datetime < ?)
                       )
                   ) THEN 'Room booked during requested time'
                   ELSE 'Available'
               END as availability_status
        FROM rooms r
        WHERE r.room_type_id = ?
        ORDER BY r.room_number ASC
    ");
    
    $availability_stmt->execute([
        $check_out->format('Y-m-d H:i:s'), // check_out > booking_check_in
        $check_in->format('Y-m-d H:i:s'),  // check_in < booking_check_out
        $check_out->format('Y-m-d H:i:s'), // check_out > booking_check_in
        $check_in->format('Y-m-d H:i:s'),  // check_in < booking_check_out
        $check_in->format('Y-m-d H:i:s'),  // booking_check_in >= check_in
        $check_out->format('Y-m-d H:i:s'), // booking_check_in < check_out
        $room_type_id
    ]);
    
    $rooms_availability = $availability_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Separate available and occupied rooms
    $available_rooms = array_filter($rooms_availability, function($room) {
        return $room['availability_status'] === 'Available';
    });
    
    $occupied_rooms = array_filter($rooms_availability, function($room) {
        return $room['availability_status'] !== 'Available';
    });
    
    echo json_encode([
        'success' => true,
        'data' => [
            'room_type_name' => $room_type_name,
            'check_in' => $check_in->format('M j, Y g:i A'),
            'check_out' => $check_out->format('M j, Y g:i A'),
            'available_rooms' => array_values($available_rooms),
            'occupied_rooms' => array_values($occupied_rooms),
            'total_rooms' => count($rooms_availability)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Availability check error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Unable to check availability. Please try again later.'
    ]);
}
?>