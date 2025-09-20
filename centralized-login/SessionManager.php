<?php

class SessionManager {
    
    /**
     * Start session if not already started
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        self::startSession();
        return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
    }
    
    /**
     * Get current user data
     */
    public static function getCurrentUser() {
        self::startSession();
        
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'type' => $_SESSION['user_type'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'login_time' => $_SESSION['login_time'] ?? null
        ];
    }
    
    /**
     * Check if current user is admin
     */
    public static function isAdmin() {
        $user = self::getCurrentUser();
        return $user && $user['type'] === 'admin';
    }
    
    /**
     * Check if current user is customer
     */
    public static function isCustomer() {
        $user = self::getCurrentUser();
        return $user && $user['type'] === 'customer';
    }
    
    /**
     * Require authentication (redirect if not logged in)
     */
    public static function requireAuth($userType = null) {
        if (!self::isLoggedIn()) {
            self::redirectToLogin('Please log in to continue');
        }
        
        if ($userType && $_SESSION['user_type'] !== $userType) {
            self::redirectToLogin('Access denied');
        }
    }
    
    /**
     * Require admin access
     */
    public static function requireAdmin() {
        self::requireAuth('admin');
    }
    
    /**
     * Require customer access
     */
    public static function requireCustomer() {
        self::requireAuth('customer');
    }
    
    /**
     * Set user session data
     */
    public static function setUserSession($userId, $userType, $userName, $userEmail, $userPhone = null) {
        self::startSession();
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_type'] = $userType;
        $_SESSION['user_name'] = $userName;
        $_SESSION['user_email'] = $userEmail;
        if ($userPhone) {
            $_SESSION['user_phone'] = $userPhone;
        }
        $_SESSION['login_time'] = time();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
    }
    
    /**
     * Destroy user session (logout)
     */
    public static function destroySession() {
        self::startSession();
        
        // Clear all session variables
        $_SESSION = [];
        
        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
    }
    
    /**
     * Check session timeout (optional security feature)
     */
    public static function checkSessionTimeout($timeoutMinutes = 120) {
        self::startSession();
        
        if (isset($_SESSION['login_time'])) {
            $sessionAge = time() - $_SESSION['login_time'];
            $timeoutSeconds = $timeoutMinutes * 60;
            
            if ($sessionAge > $timeoutSeconds) {
                self::destroySession();
                self::redirectToLogin('Session expired. Please log in again.');
            }
        }
    }
    
    /**
     * Update last activity timestamp
     */
    public static function updateActivity() {
        self::startSession();
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Get user's dashboard URL based on user type
     */
    public static function getDashboardUrl() {
        $user = self::getCurrentUser();
        
        if (!$user) {
            return null;
        }
        
        switch ($user['type']) {
            case 'admin':
                return '../admin/dashboard.php';
            case 'customer':
                return '../customer/dashboard.php';
            default:
                return '../index.php';
        }
    }
    
    /**
     * Redirect to login page
     */
    private static function redirectToLogin($message = '') {
        $loginUrl = '../centralized-login/login.php';
        
        if ($message) {
            $loginUrl .= '?message=' . urlencode($message);
        }
        
        header('Location: ' . $loginUrl);
        exit();
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        self::startSession();
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token) {
        self::startSession();
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Set flash message
     */
    public static function setFlashMessage($type, $message) {
        self::startSession();
        $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
    }
    
    /**
     * Get and clear flash messages
     */
    public static function getFlashMessages() {
        self::startSession();
        
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        
        return $messages;
    }
}
?>