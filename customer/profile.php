<?php
require_once '../centralized-login/SessionManager.php';
require_once '../config/database.php';

// Require customer authentication
SessionManager::requireCustomer();

$sessionUser = SessionManager::getCurrentUser();

// Fetch complete user data from database
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$sessionUser['id']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentUser) {
        // Fallback to session data if database fetch fails
        $currentUser = $sessionUser;
    } else {
        // Combine first_name and last_name into name for compatibility
        $currentUser['name'] = trim($currentUser['first_name'] . ' ' . $currentUser['last_name']);
    }
} catch (Exception $e) {
    // Fallback to session data if database error occurs
    $currentUser = $sessionUser;
}

$page_title = 'My Profile';
$additional_css = ['css/dashboard.css'];

// Handle profile update
$updateMessage = '';
$updateType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Split name into first_name and last_name
    $nameParts = explode(' ', $name, 2);
    $first_name = $nameParts[0];
    $last_name = isset($nameParts[1]) ? $nameParts[1] : '';
    
    // Validate inputs
    if (empty($name) || empty($email)) {
        $updateMessage = 'Name and email are required fields.';
        $updateType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $updateMessage = 'Please enter a valid email address.';
        $updateType = 'error';
    } else {
        try {
            // Check if email is already taken by another user
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $currentUser['id']]);
            
            if ($stmt->fetch()) {
                $updateMessage = 'This email address is already registered to another account.';
                $updateType = 'error';
            } else {
                // Update user profile (using first_name and last_name columns)
                $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $result = $stmt->execute([$first_name, $last_name, $email, $phone, $currentUser['id']]);
                
                if ($result) {
                    // Update session data
                    $_SESSION['user']['name'] = $name;
                    $_SESSION['user']['email'] = $email;
                    $_SESSION['user']['phone'] = $phone;
                    $_SESSION['user']['first_name'] = $first_name;
                    $_SESSION['user']['last_name'] = $last_name;
                    
                    // Refresh current user data from database
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$currentUser['id']]);
                    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
                    $currentUser['name'] = trim($currentUser['first_name'] . ' ' . $currentUser['last_name']);
                    
                    $updateMessage = 'Profile updated successfully!';
                    $updateType = 'success';
                } else {
                    $updateMessage = 'Failed to update profile. Please try again.';
                    $updateType = 'error';
                }
            }
        } catch (Exception $e) {
            error_log('Profile update error: ' . $e->getMessage());
            $updateMessage = 'An error occurred while updating your profile.';
            $updateType = 'error';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $updateMessage = 'All password fields are required.';
        $updateType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $updateMessage = 'New passwords do not match.';
        $updateType = 'error';
    } elseif (strlen($newPassword) < 6) {
        $updateMessage = 'New password must be at least 6 characters long.';
        $updateType = 'error';
    } else {
        try {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$currentUser['id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($currentPassword, $user['password'])) {
                $updateMessage = 'Current password is incorrect.';
                $updateType = 'error';
            } else {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $result = $stmt->execute([$hashedPassword, $currentUser['id']]);
                
                if ($result) {
                    $updateMessage = 'Password changed successfully!';
                    $updateType = 'success';
                } else {
                    $updateMessage = 'Failed to change password. Please try again.';
                    $updateType = 'error';
                }
            }
        } catch (Exception $e) {
            error_log('Password change error: ' . $e->getMessage());
            $updateMessage = 'An error occurred while changing your password.';
            $updateType = 'error';
        }
    }
}

?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>
    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Profile Section -->
        <div id="profile-section" class="content-section">
            <div class="content-header">
                <h2 class="mb-0"><i class="fas fa-user-cog me-2"></i>My Profile</h2>
                <p class="text-muted mb-0">Manage your account information and settings</p>
            </div>
            
            <div class="content-body">
                <?php if ($updateMessage): ?>
                    <div class="alert alert-<?php echo $updateType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $updateType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                        <?php echo htmlspecialchars($updateMessage); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row justify-content-center">
                    <!-- Account Summary Card - Centered -->
                    <div class="col-lg-6 col-md-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Account Summary</h5>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateProfileModal" style="background-color: #007bff; border: none; border-radius: 8px; padding: 8px 16px; font-weight: 500; color: white;">
                                        <i class="fas fa-user-edit me-1"></i>Update
                                    </button>
                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal" style="background-color: #ffc107; border: none; border-radius: 8px; padding: 8px 16px; font-weight: 500; color: #212529;">
                                        <i class="fas fa-key me-1"></i>Password
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="user-avatar mx-auto mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-user fa-2x text-white"></i>
                                    </div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($currentUser['name']); ?></h5>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                                </div>
                                
                                <hr>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Member Since:</span>
                                    <span class="fw-bold">
                                        <?php 
                                        if (isset($currentUser['created_at']) && $currentUser['created_at']) {
                                            $memberSince = new DateTime($currentUser['created_at']);
                                            echo $memberSince->format('M Y'); 
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Account Status:</span>
                                    <span class="badge bg-success">Active</span>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <span>Last Updated:</span>
                                    <span class="fw-bold">
                                        <?php 
                                        if (isset($currentUser['updated_at']) && $currentUser['updated_at']) {
                                            $lastUpdated = new DateTime($currentUser['updated_at']);
                                            echo $lastUpdated->format('M d, Y'); 
                                        } elseif (isset($currentUser['created_at']) && $currentUser['created_at']) {
                                            $lastUpdated = new DateTime($currentUser['created_at']);
                                            echo $lastUpdated->format('M d, Y'); 
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Profile Modal -->
    <div class="modal fade" id="updateProfileModal" tabindex="-1" aria-labelledby="updateProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateProfileModalLabel">
                        <i class="fas fa-user-edit me-2"></i>Update Profile Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="modal_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="modal_name" name="name" 
                                       value="<?php echo htmlspecialchars($currentUser['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="modal_email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="modal_email" name="email" 
                                       value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="modal_phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="modal_phone" name="phone" 
                                       value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <!-- Removed address field as it doesn't exist in database -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">
                        <i class="fas fa-key me-2"></i>Change Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="modal_current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="modal_current_password" name="current_password" required>
                            </div>
                            <div class="col-12">
                                <label for="modal_new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="modal_new_password" name="new_password" 
                                       minlength="6" required>
                                <div class="form-text">Password must be at least 6 characters long.</div>
                            </div>
                            <div class="col-12">
                                <label for="modal_confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="modal_confirm_password" name="confirm_password" 
                                       minlength="6" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password confirmation validation for modal
        document.getElementById('modal_confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('modal_new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Close modals after successful form submission
        <?php if ($updateMessage && $updateType === 'success'): ?>
            // Close any open modals
            const modals = document.querySelectorAll('.modal');
            modals.forEach(function(modal) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>