<?php
session_start();
require_once '../../../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Check if request is POST and has action
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'update_profile') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $customerId = $_SESSION['customer_id'];
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    
    // Validate required fields
    if (empty($name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Name and email are required']);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    // Check if email is already taken by another user
    $emailCheckStmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
    $emailCheckStmt->execute([$email, $customerId]);
    if ($emailCheckStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email is already taken by another user']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Handle password change if provided
    $updatePassword = false;
    if (!empty($currentPassword) && !empty($newPassword)) {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }
        
        // Validate new password
        if (strlen($newPassword) < 6) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long']);
            exit;
        }
        
        $updatePassword = true;
    }
    
    // Update profile information
    if ($updatePassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, address = ?, password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $address, $hashedPassword, $customerId]);
    } else {
        $stmt = $pdo->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $address, $customerId]);
    }
    
    // Update session data
    $_SESSION['customer_name'] = $name;
    $_SESSION['customer_email'] = $email;
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Profile updated successfully' . ($updatePassword ? ' (password changed)' : '')
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Profile update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating your profile']);
}
?>