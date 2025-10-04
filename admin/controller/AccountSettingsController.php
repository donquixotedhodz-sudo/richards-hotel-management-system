<?php
require_once '../../centralized-login/SessionManager.php';
require_once '../../config/database.php';

// Require admin authentication
SessionManager::requireAdmin();
header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        case 'get_profile':
            getProfile($conn);
            break;
        case 'update_profile':
            updateProfile($conn);
            break;
        case 'change_password':
            changePassword($conn);
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getProfile($conn) {
    SessionManager::startSession();
    $adminId = $_SESSION['user_id'] ?? null;
    if (!$adminId) throw new Exception('Not authenticated');

    $stmt = $conn->prepare("SELECT id, full_name, email, username FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) throw new Exception('Admin not found');

    echo json_encode(['success' => true, 'profile' => $admin]);
}

function updateProfile($conn) {
    SessionManager::startSession();
    $adminId = $_SESSION['user_id'] ?? null;
    if (!$adminId) throw new Exception('Not authenticated');

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');

    if ($fullName === '' || $email === '' || $username === '') {
        throw new Exception('All fields are required');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Uniqueness checks
    $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
    $stmt->execute([$email, $adminId]);
    if ($stmt->fetch()) throw new Exception('Email already in use');

    $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
    $stmt->execute([$username, $adminId]);
    if ($stmt->fetch()) throw new Exception('Username already in use');

    $stmt = $conn->prepare("UPDATE admins SET full_name = ?, email = ?, username = ?, updated_at = NOW() WHERE id = ?");
    $ok = $stmt->execute([$fullName, $email, $username, $adminId]);
    if (!$ok) throw new Exception('Failed to update profile');

    // Update session values
    $_SESSION['user_name'] = $fullName;
    $_SESSION['user_email'] = $email;
    $_SESSION['username'] = $username;

    echo json_encode(['success' => true, 'message' => 'Profile updated']);
}

function changePassword($conn) {
    SessionManager::startSession();
    $adminId = $_SESSION['user_id'] ?? null;
    if (!$adminId) throw new Exception('Not authenticated');

    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($current === '' || $new === '' || $confirm === '') {
        throw new Exception('Please fill in all password fields');
    }
    if ($new !== $confirm) throw new Exception('New passwords do not match');
    if (strlen($new) < 8 || !preg_match('/[A-Z]/', $new) || !preg_match('/[a-z]/', $new) || !preg_match('/\d/', $new)) {
        throw new Exception('Password must be 8+ chars, include upper, lower, and number');
    }

    $stmt = $conn->prepare("SELECT password_hash FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new Exception('Admin not found');
    if (!password_verify($current, $row['password_hash'])) {
        throw new Exception('Current password is incorrect');
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE admins SET password_hash = ?, updated_at = NOW() WHERE id = ?");
    $ok = $stmt->execute([$hash, $adminId]);
    if (!$ok) throw new Exception('Failed to change password');

    echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
}

?>