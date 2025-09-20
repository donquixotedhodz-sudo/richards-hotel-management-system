<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : (isset($_REQUEST['room_id']) ? (int)$_REQUEST['room_id'] : 0);

function getAllRooms($conn) {
    $stmt = $conn->prepare("
        SELECT r.id, r.room_number, r.status, r.created_at, r.updated_at,
               rt.type_name, rt.description as room_type_description
        FROM rooms r
        LEFT JOIN room_types rt ON r.room_type_id = rt.id
        ORDER BY r.room_number ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRoom($conn, $id) {
    $stmt = $conn->prepare("
        SELECT r.id, r.room_number, r.room_type_id, r.status, r.created_at, r.updated_at,
               rt.type_name, rt.description as room_type_description
        FROM rooms r
        LEFT JOIN room_types rt ON r.room_type_id = rt.id
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    return $room ?: null;
}

function getRoomTypes($conn) {
    $stmt = $conn->prepare("SELECT id, type_name, description FROM room_types ORDER BY type_name ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRoomStats($conn) {
    $stats = [
        'total_rooms' => 0,
        'available_rooms' => 0,
        'occupied_rooms' => 0,
        'maintenance_rooms' => 0,
        'out_of_order_rooms' => 0
    ];

    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_rooms,
            COUNT(CASE WHEN status = 'available' THEN 1 END) as available_rooms,
            COUNT(CASE WHEN status = 'occupied' THEN 1 END) as occupied_rooms,
            COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance_rooms,
            COUNT(CASE WHEN status = 'out_of_order' THEN 1 END) as out_of_order_rooms
        FROM rooms
    ");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $stats = array_merge($stats, $row);
    }

    return $stats;
}

switch ($action) {
    case 'get_all':
        $rooms = getAllRooms($conn);
        echo json_encode(['success' => true, 'rooms' => $rooms]);
        exit;

    case 'get':
    case 'details':
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Room ID missing']);
            exit;
        }
        $room = getRoom($conn, $id);
        if (!$room) {
            echo json_encode(['success' => false, 'message' => 'Room not found']);
            exit;
        }
        echo json_encode(['success' => true, 'room' => $room]);
        exit;

    case 'get_room_types':
        $roomTypes = getRoomTypes($conn);
        echo json_encode(['success' => true, 'room_types' => $roomTypes]);
        exit;

    case 'get_stats':
        $stats = getRoomStats($conn);
        echo json_encode(['success' => true, 'stats' => $stats]);
        exit;

    case 'create':
        $room_number = trim($_POST['room_number'] ?? '');
        $room_type_id = isset($_POST['room_type_id']) ? (int)$_POST['room_type_id'] : 0;
        $status = trim($_POST['status'] ?? 'available');

        if (!$room_number || !$room_type_id) {
            echo json_encode(['success' => false, 'message' => 'Room number and room type are required.']);
            exit;
        }

        // Check if room number already exists
        $checkStmt = $conn->prepare("SELECT id FROM rooms WHERE room_number = ?");
        $checkStmt->execute([$room_number]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Room number already exists.']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_type_id, status) VALUES (?, ?, ?)");
        $result = $stmt->execute([$room_number, $room_type_id, $status]);
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Room created successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create room.']);
        }
        exit;

    case 'update':
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Room ID missing']);
            exit;
        }
        $room_number = trim($_POST['room_number'] ?? '');
        $room_type_id = isset($_POST['room_type_id']) ? (int)$_POST['room_type_id'] : 0;
        $status = trim($_POST['status'] ?? 'available');

        if (!$room_number || !$room_type_id) {
            echo json_encode(['success' => false, 'message' => 'Room number and room type are required.']);
            exit;
        }

        // Check if room number already exists for other rooms
        $checkStmt = $conn->prepare("SELECT id FROM rooms WHERE room_number = ? AND id != ?");
        $checkStmt->execute([$room_number, $id]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Room number already exists.']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE rooms SET room_number=?, room_type_id=?, status=? WHERE id=?");
        $result = $stmt->execute([$room_number, $room_type_id, $status, $id]);
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Room updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update room.']);
        }
        exit;

    case 'delete':
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Room ID missing']);
            exit;
        }

        // Check if room has any bookings
        $checkStmt = $conn->prepare("SELECT id FROM bookings WHERE room_id = ? LIMIT 1");
        $checkStmt->execute([$id]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete room with existing bookings.']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
        $result = $stmt->execute([$id]);
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Room deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete room.']);
        }
        exit;

    case 'update_status':
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Room ID missing']);
            exit;
        }
        $status = trim($_POST['status'] ?? '');
        $valid_statuses = ['available', 'occupied', 'maintenance', 'out_of_order'];
        
        if (!in_array($status, $valid_statuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status.']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE rooms SET status=? WHERE id=?");
        $result = $stmt->execute([$status, $id]);
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Room status updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update room status.']);
        }
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}
?>