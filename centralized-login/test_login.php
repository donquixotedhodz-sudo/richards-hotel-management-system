<?php
// Test file for centralized login system
require_once 'AuthService.php';
require_once 'SessionManager.php';
require_once '../config/database.php';

echo "<h2>Testing Centralized Login System</h2>";
echo "<hr>";

// Test database connection
try {
    // Use the $conn variable from database.php
    $pdo = $conn;
    echo "✅ Database connection: SUCCESS<br>";
} catch (Exception $e) {
    echo "❌ Database connection: FAILED - " . $e->getMessage() . "<br>";
    exit;
}

// Test AuthService instantiation
try {
    $authService = new AuthService($pdo);
    echo "✅ AuthService instantiation: SUCCESS<br>";
} catch (Exception $e) {
    echo "❌ AuthService instantiation: FAILED - " . $e->getMessage() . "<br>";
}

// Test SessionManager
try {
    SessionManager::startSession();
    echo "✅ SessionManager start: SUCCESS<br>";
} catch (Exception $e) {
    echo "❌ SessionManager start: FAILED - " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Database Tables Check</h3>";

// Check if admin table exists and has data
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
    $adminCount = $stmt->fetch()['count'];
    echo "✅ Admin table: {$adminCount} records found<br>";
} catch (Exception $e) {
    echo "❌ Admin table: ERROR - " . $e->getMessage() . "<br>";
}

// Check if users table exists and has data
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "✅ Users table: {$userCount} records found<br>";
} catch (Exception $e) {
    echo "❌ Users table: ERROR - " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Sample Authentication Test</h3>";

// Test admin authentication with sample data
try {
    $result = $authService->authenticate('admin@hotel.com', 'admin123');
    if ($result['success']) {
        echo "✅ Admin authentication test: SUCCESS<br>";
        echo "&nbsp;&nbsp;&nbsp;User ID: " . $result['user']['id'] . "<br>";
        echo "&nbsp;&nbsp;&nbsp;User Email: " . $result['user']['email'] . "<br>";
        echo "&nbsp;&nbsp;&nbsp;User Type: " . $result['user']['type'] . "<br>";
    } else {
        echo "❌ Admin authentication test: FAILED - " . $result['message'] . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Admin authentication test: ERROR - " . $e->getMessage() . "<br>";
}

// Test customer authentication with sample data
try {
    $result = $authService->authenticate('john.doe@email.com', 'password123');
    if ($result['success']) {
        echo "✅ Customer authentication test: SUCCESS<br>";
        echo "&nbsp;&nbsp;&nbsp;User ID: " . $result['user']['id'] . "<br>";
        echo "&nbsp;&nbsp;&nbsp;User Email: " . $result['user']['email'] . "<br>";
        echo "&nbsp;&nbsp;&nbsp;User Type: " . $result['user']['type'] . "<br>";
    } else {
        echo "❌ Customer authentication test: FAILED - " . $result['message'] . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Customer authentication test: ERROR - " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Login System Files Check</h3>";

$files = [
    'AuthService.php' => 'Authentication Service',
    'SessionManager.php' => 'Session Manager',
    'controller/LoginController.php' => 'Login Controller',
    'login.php' => 'Login View'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        echo "✅ {$description}: EXISTS<br>";
    } else {
        echo "❌ {$description}: MISSING<br>";
    }
}

echo "<hr>";
echo "<p><strong>Test completed!</strong></p>";
echo "<p><a href='login.php'>Go to Login Page</a></p>";
?>