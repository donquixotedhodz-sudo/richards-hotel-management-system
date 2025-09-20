<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all booking rates with room type information
    $stmt = $pdo->prepare("
        SELECT 
            br.room_type_id,
            br.duration_hours,
            br.price,
            rt.type_name
        FROM booking_rates br
        JOIN room_types rt ON br.room_type_id = rt.id
        ORDER BY br.room_type_id, br.duration_hours
    ");
    
    $stmt->execute();
    $rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize rates by room type and duration
    $organized_rates = [];
    foreach ($rates as $rate) {
        $organized_rates[$rate['room_type_id']][$rate['duration_hours']] = $rate['price'];
    }
    
    echo json_encode([
        'success' => true,
        'rates' => $organized_rates,
        'raw_data' => $rates
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>