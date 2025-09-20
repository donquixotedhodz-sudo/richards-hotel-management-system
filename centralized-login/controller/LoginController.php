<?php
session_start();
require_once __DIR__ . '/../AuthService.php';

class LoginController {
    private $authService;
    
    public function __construct() {
        $this->authService = new AuthService();
    }
    
    /**
     * Handle login request
     */
    public function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToLogin('Invalid request method');
            return;
        }
        
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validate input
        if (empty($identifier) || empty($password)) {
            $this->redirectToLogin('Please fill in all fields');
            return;
        }
        
        // Attempt authentication
        $result = $this->authService->authenticate($identifier, $password);
        
        if ($result['success']) {
            // Set session data
            $_SESSION['user_id'] = $result['user_data']['id'];
            $_SESSION['user_type'] = $result['user_type'];
            $_SESSION['user_name'] = $result['user_data']['name'];
            $_SESSION['user_email'] = $result['user_data']['email'];
            $_SESSION['username'] = $result['user_data']['username'] ?? $result['user_data']['name'];
            if (isset($result['user_data']['phone'])) {
                $_SESSION['user_phone'] = $result['user_data']['phone'];
            }
            $_SESSION['login_time'] = time();
            
            // Redirect based on user type after successful login
            if ($result['user_type'] === 'admin') {
                header('Location: ../../admin/dashboard.php');
            } else {
                header('Location: ../../index.php');
            }
            exit();
        } else {
            $this->redirectToLogin($result['message']);
        }
    }
    
    /**
     * Handle logout request
     */
    public function handleLogout() {
        session_start();
        session_destroy();
        header('Location: ../../index.php');
        exit();
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'type' => $_SESSION['user_type'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'login_time' => $_SESSION['login_time']
        ];
    }
    
    /**
     * Require authentication (middleware)
     */
    public function requireAuth($userType = null) {
        if (!$this->isLoggedIn()) {
            header('Location: ../centralized-login/login.php?message=Please log in to continue');
            exit();
        }
        
        if ($userType && $_SESSION['user_type'] !== $userType) {
            header('Location: ../centralized-login/login.php?message=Access denied');
            exit();
        }
    }
    
    /**
     * Redirect to login page with message
     */
    private function redirectToLogin($message) {
        header('Location: ../login.php?error=' . urlencode($message));
        exit();
    }
}

// Handle requests
if (isset($_GET['action'])) {
    $controller = new LoginController();
    
    switch ($_GET['action']) {
        case 'login':
            $controller->handleLogin();
            break;
        case 'logout':
            $controller->handleLogout();
            break;
        default:
            header('Location: ../login.php');
            exit();
    }
} else {
    // If no action specified, redirect to login
    header('Location: ../login.php');
    exit();
}
?>