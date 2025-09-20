<?php
require_once __DIR__ . '/../config/database.php';

class AuthService {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    /**
     * Authenticate user (admin or customer)
     * @param string $identifier - username or email
     * @param string $password - plain text password
     * @return array - authentication result
     */
    public function authenticate($identifier, $password) {
        // First try to authenticate as admin
        $adminResult = $this->authenticateAdmin($identifier, $password);
        if ($adminResult['success']) {
            return $adminResult;
        }
        
        // If admin auth fails, try customer authentication
        $customerResult = $this->authenticateCustomer($identifier, $password);
        return $customerResult;
    }
    
    /**
     * Authenticate admin user
     */
    private function authenticateAdmin($identifier, $password) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, full_name, email, username, password_hash, is_active, 
                       failed_login_attempts, locked_until
                FROM admins 
                WHERE (username = :identifier OR email = :identifier) AND is_active = 1
            ");
            $stmt->bindParam(':identifier', $identifier);
            $stmt->execute();
            
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$admin) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Check if account is locked
            if ($admin['locked_until'] && new DateTime() < new DateTime($admin['locked_until'])) {
                return ['success' => false, 'message' => 'Account is temporarily locked'];
            }
            
            // Verify password
            if (password_verify($password, $admin['password_hash'])) {
                // Reset failed attempts on successful login
                $this->resetFailedAttempts('admins', $admin['id']);
                $this->updateLastLogin('admins', $admin['id']);
                
                return [
                    'success' => true,
                    'user_type' => 'admin',
                    'user_data' => [
                        'id' => $admin['id'],
                        'name' => $admin['full_name'],
                        'email' => $admin['email'],
                        'username' => $admin['username']
                    ]
                ];
            } else {
                // Increment failed attempts
                $this->incrementFailedAttempts('admins', $admin['id']);
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
        } catch (PDOException $e) {
            error_log("Admin authentication error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Authentication error'];
        }
    }
    
    /**
     * Authenticate customer user
     */
    private function authenticateCustomer($identifier, $password) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, first_name, last_name, email, phone, password_hash, is_active,
                       failed_login_attempts, locked_until
                FROM users 
                WHERE email = :identifier AND is_active = 1
            ");
            $stmt->bindParam(':identifier', $identifier);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Check if account is locked
            if ($user['locked_until'] && new DateTime() < new DateTime($user['locked_until'])) {
                return ['success' => false, 'message' => 'Account is temporarily locked'];
            }
            
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Reset failed attempts on successful login
                $this->resetFailedAttempts('users', $user['id']);
                $this->updateLastLogin('users', $user['id']);
                
                return [
                    'success' => true,
                    'user_type' => 'customer',
                    'user_data' => [
                        'id' => $user['id'],
                        'name' => $user['first_name'] . ' ' . $user['last_name'],
                        'email' => $user['email'],
                        'phone' => $user['phone']
                    ]
                ];
            } else {
                // Increment failed attempts
                $this->incrementFailedAttempts('users', $user['id']);
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
        } catch (PDOException $e) {
            error_log("Customer authentication error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Authentication error'];
        }
    }
    
    /**
     * Increment failed login attempts
     */
    private function incrementFailedAttempts($table, $userId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE {$table} 
                SET failed_login_attempts = failed_login_attempts + 1,
                    locked_until = CASE 
                        WHEN failed_login_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                        ELSE locked_until 
                    END
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Failed to increment login attempts: " . $e->getMessage());
        }
    }
    
    /**
     * Reset failed login attempts
     */
    private function resetFailedAttempts($table, $userId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE {$table} 
                SET failed_login_attempts = 0, locked_until = NULL 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Failed to reset login attempts: " . $e->getMessage());
        }
    }
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin($table, $userId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE {$table} 
                SET last_login = NOW() 
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Failed to update last login: " . $e->getMessage());
        }
    }
}
?>