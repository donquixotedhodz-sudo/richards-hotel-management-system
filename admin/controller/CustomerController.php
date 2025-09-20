<?php
require_once __DIR__ . '/../../config/database.php';

class CustomerController {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    // Get all customers
    public function getAllCustomers() {
        try {
            $stmt = $this->conn->prepare("SELECT id, first_name, last_name, email, phone FROM users ORDER BY id DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Get customer by ID
    public function getCustomerById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT id, first_name, last_name, email, phone FROM users WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Create new customer
    public function createCustomer($first_name, $last_name, $email, $phone, $password) {
        try {
            // Check if email already exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$first_name, $last_name, $email, $phone, $password_hash]);
            
            if($result) {
                return ['success' => true, 'message' => 'Customer created successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to create customer'];
            }
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Update customer
    public function updateCustomer($id, $first_name, $last_name, $email, $phone) {
        try {
            // Check if email already exists for other users
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            $stmt = $this->conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
            $result = $stmt->execute([$first_name, $last_name, $email, $phone, $id]);
            
            if($result) {
                return ['success' => true, 'message' => 'Customer updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update customer'];
            }
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Delete customer
    public function deleteCustomer($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if($result) {
                return ['success' => true, 'message' => 'Customer deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete customer'];
            }
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $controller = new CustomerController();
    
    switch ($_POST['action']) {
        case 'create':
            $result = $controller->createCustomer(
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $_POST['phone'],
                $_POST['password']
            );
            echo json_encode($result);
            break;
            
        case 'update':
            $result = $controller->updateCustomer(
                $_POST['id'],
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['email'],
                $_POST['phone']
            );
            echo json_encode($result);
            break;
            
        case 'delete':
            $result = $controller->deleteCustomer($_POST['id']);
            echo json_encode($result);
            break;
            
        case 'get':
            $result = $controller->getCustomerById($_POST['id']);
            echo json_encode($result);
            break;
    }
    exit;
}
?>