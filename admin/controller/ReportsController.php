<?php
require_once '../../centralized-login/SessionManager.php';
require_once '../../config/database.php';

// Require admin authentication
SessionManager::requireAdmin();

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        case 'get_summary':
            getSummary($conn);
            break;
        case 'bookings_over_time':
            bookingsOverTime($conn);
            break;
        case 'revenue_over_time':
            revenueOverTime($conn);
            break;
        case 'occupancy_stats':
            occupancyStats($conn);
            break;
        case 'payment_breakdown':
            paymentBreakdown($conn);
            break;
        case 'top_customers':
            topCustomers($conn);
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

function getSummary($conn) {
    $days = (int)($_GET['days'] ?? $_POST['days'] ?? 30);
    $days = max(1, min($days, 365));

    // Total bookings in period
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM bookings WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)");
    $stmt->execute([$days]);
    $totalBookings = (int)$stmt->fetchColumn();

    // Total revenue in period (exclude cancelled)
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND booking_status NOT IN ('cancelled')");
    $stmt->execute([$days]);
    $totalRevenue = (float)$stmt->fetchColumn();

    // Average booking duration (hours) in period
    $stmt = $conn->prepare("SELECT COALESCE(AVG(duration_hours),0) FROM bookings WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)");
    $stmt->execute([$days]);
    $avgDuration = round((float)$stmt->fetchColumn(), 2);

    // Current occupancy: rooms occupied / total rooms
    $totalRoomsStmt = $conn->query("SELECT COUNT(*) FROM rooms");
    $totalRooms = (int)$totalRoomsStmt->fetchColumn();
    $occupiedRoomsStmt = $conn->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'");
    $occupiedRooms = (int)$occupiedRoomsStmt->fetchColumn();
    $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;

    echo json_encode([
        'success' => true,
        'summary' => [
            'total_bookings' => $totalBookings,
            'total_revenue' => $totalRevenue,
            'avg_duration_hours' => $avgDuration,
            'occupancy_rate' => $occupancyRate,
            'total_rooms' => $totalRooms,
            'occupied_rooms' => $occupiedRooms,
            'period_days' => $days
        ]
    ]);
}

function bookingsOverTime($conn) {
    $days = (int)($_GET['days'] ?? $_POST['days'] ?? 30);
    $days = max(1, min($days, 365));

    $stmt = $conn->prepare(
        "SELECT DATE(created_at) as date, COUNT(*) as count
         FROM bookings
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
         GROUP BY DATE(created_at)
         ORDER BY DATE(created_at)"
    );
    $stmt->execute([$days]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $values = [];
    foreach ($rows as $row) {
        $labels[] = $row['date'];
        $values[] = (int)$row['count'];
    }

    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'values' => $values,
        'period_days' => $days
    ]);
}

function revenueOverTime($conn) {
    $days = (int)($_GET['days'] ?? $_POST['days'] ?? 30);
    $days = max(1, min($days, 365));

    $stmt = $conn->prepare(
        "SELECT DATE(created_at) as date, COALESCE(SUM(total_price),0) as revenue
         FROM bookings
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
           AND booking_status NOT IN ('cancelled')
         GROUP BY DATE(created_at)
         ORDER BY DATE(created_at)"
    );
    $stmt->execute([$days]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $values = [];
    foreach ($rows as $row) {
        $labels[] = $row['date'];
        $values[] = round((float)$row['revenue'], 2);
    }

    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'values' => $values,
        'period_days' => $days
    ]);
}

function occupancyStats($conn) {
    // Simple breakdown of room statuses
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM rooms GROUP BY status");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'success' => true,
        'statuses' => $rows
    ]);
}

function paymentBreakdown($conn) {
    $days = (int)($_GET['days'] ?? $_POST['days'] ?? 30);
    $days = max(1, min($days, 365));

    $stmt = $conn->prepare(
        "SELECT payment_status, COUNT(*) as count
         FROM bookings
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
         GROUP BY payment_status"
    );
    $stmt->execute([$days]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'success' => true,
        'breakdown' => $rows
    ]);
}

function topCustomers($conn) {
    $days = (int)($_GET['days'] ?? $_POST['days'] ?? 90);
    $days = max(1, min($days, 365));

    $stmt = $conn->prepare(
        "SELECT customer_email, customer_name, COUNT(*) as bookings, COALESCE(SUM(total_price),0) as revenue
         FROM bookings
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
           AND booking_status NOT IN ('cancelled')
         GROUP BY customer_email, customer_name
         ORDER BY revenue DESC
         LIMIT 10"
    );
    $stmt->execute([$days]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'success' => true,
        'customers' => $rows
    ]);
}