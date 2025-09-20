<?php
require_once '../centralized-login/SessionManager.php';
require_once '../config/database.php';

// Require customer authentication
SessionManager::requireCustomer();

$currentUser = SessionManager::getCurrentUser();
$page_title = 'Customer Dashboard';
$additional_css = ['css/dashboard.css'];

// Fetch booking statistics for current user
$userId = $currentUser['id'];

// Get total bookings count (match by user_id OR email for legacy bookings)
$stmt = $conn->prepare("SELECT COUNT(*) as total_bookings FROM bookings WHERE user_id = ? OR customer_email = ?");
$stmt->execute([$userId, $currentUser['email']]);
$totalBookings = $stmt->fetch(PDO::FETCH_ASSOC)['total_bookings'];

// Get pending bookings count
$stmt = $conn->prepare("SELECT COUNT(*) as pending_bookings FROM bookings WHERE (user_id = ? OR customer_email = ?) AND booking_status = 'pending'");
$stmt->execute([$userId, $currentUser['email']]);
$pendingBookings = $stmt->fetch(PDO::FETCH_ASSOC)['pending_bookings'];

// Get confirmed bookings count
$stmt = $conn->prepare("SELECT COUNT(*) as confirmed_bookings FROM bookings WHERE (user_id = ? OR customer_email = ?) AND booking_status = 'confirmed'");
$stmt->execute([$userId, $currentUser['email']]);
$confirmedBookings = $stmt->fetch(PDO::FETCH_ASSOC)['confirmed_bookings'];

// Get total amount spent
$stmt = $conn->prepare("SELECT SUM(total_price) as total_spent FROM bookings WHERE (user_id = ? OR customer_email = ?) AND payment_status = 'paid'");
$stmt->execute([$userId, $currentUser['email']]);
$totalSpent = $stmt->fetch(PDO::FETCH_ASSOC)['total_spent'] ?? 0;

?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>
    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Dashboard Section -->
        <div id="dashboard-section" class="content-section">
            <div class="content-header">
                <h2 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
                <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($currentUser['name']); ?>!</p>
            </div>
            
            <div class="content-body">
                <!-- Booking Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="card text-center bg-primary text-white">
                            <div class="card-body">
                                <i class="fas fa-calendar-alt fa-3x mb-3"></i>
                                <h3 class="card-title"><?php echo $totalBookings; ?></h3>
                                <p class="card-text">Total Bookings</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="card text-center bg-warning text-white">
                            <div class="card-body">
                                <i class="fas fa-clock fa-3x mb-3"></i>
                                <h3 class="card-title"><?php echo $pendingBookings; ?></h3>
                                <p class="card-text">Pending Bookings</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="card text-center bg-success text-white">
                            <div class="card-body">
                                <i class="fas fa-check-circle fa-3x mb-3"></i>
                                <h3 class="card-title"><?php echo $confirmedBookings; ?></h3>
                                <p class="card-text">Confirmed Bookings</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="card text-center bg-info text-white">
                            <div class="card-body">
                                <i class="fas fa-peso-sign fa-3x mb-3"></i>
                                <h3 class="card-title">₱<?php echo number_format($totalSpent, 2); ?></h3>
                                <p class="card-text">Total Spent</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Bookings Section -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Recent Bookings</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                // Fetch recent bookings (last 5) - match by user_id OR email for legacy bookings
                $recentBookingsQuery = "SELECT b.*, rt.type_name 
                                      FROM bookings b 
                                      LEFT JOIN room_types rt ON b.room_type_id = rt.id 
                                      WHERE b.user_id = ? OR b.customer_email = ? 
                                      ORDER BY b.id DESC 
                                      LIMIT 5";
                $recentStmt = $conn->prepare($recentBookingsQuery);
                $recentStmt->execute([$userId, $currentUser['email']]);
                                $recentBookings = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (count($recentBookings) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Booking ID</th>
                                                    <th>Room Type</th>
                                                    <th>Check-in</th>
                                                    <th>Status</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentBookings as $booking): ?>
                                                    <tr>
                                                        <td>#<?php echo str_pad($booking['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                                        <td><?php echo htmlspecialchars($booking['type_name'] ?? 'N/A'); ?></td>
                                                        <td><?php echo date('M j, Y g:i A', strtotime($booking['check_in_datetime'])); ?></td>
                                                        <td>
                                                            <?php
                                                            $statusClass = '';
                                                            switch(strtolower($booking['booking_status'])) {
                                                                case 'pending':
                                                                    $statusClass = 'bg-warning text-dark';
                                                                    break;
                                                                case 'confirmed':
                                                                    $statusClass = 'bg-success';
                                                                    break;
                                                                case 'checked-in':
                                                                    $statusClass = 'bg-primary';
                                                                    break;
                                                                case 'checked-out':
                                                                    $statusClass = 'bg-info';
                                                                    break;
                                                                case 'cancelled':
                                                                    $statusClass = 'bg-danger';
                                                                    break;
                                                                default:
                                                                    $statusClass = 'bg-secondary';
                                                            }
                                                            ?>
                                                            <span class="badge <?php echo $statusClass; ?>">
                                                                <?php echo ucfirst($booking['booking_status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>₱<?php echo number_format($booking['total_price'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="#" onclick="showSection('bookings')" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-list me-1"></i>View All Bookings
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <h6 class="text-muted">No recent bookings found</h6>
                                        <p class="text-muted mb-3">Start by making your first reservation!</p>
                                        <a href="#" onclick="showSection('reservations')" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i>Make a Reservation
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
              
            </div>
         </div>
        
        <!-- My Bookings Section -->
        <div id="bookings-section" class="content-section" style="display: none;">
            <div class="content-header">
                <h2 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>My Bookings</h2>
                <p class="text-muted mb-0">View and manage all your bookings</p>
            </div>
            
            <div class="content-body">
                <?php
                // Fetch all bookings for current user - match by user_id OR email for legacy bookings
                $allBookingsQuery = "SELECT b.*, rt.type_name, rt.description as room_description 
                                   FROM bookings b 
                                   LEFT JOIN room_types rt ON b.room_type_id = rt.id 
                                   WHERE b.user_id = ? OR b.customer_email = ? 
                                   ORDER BY b.created_at DESC";
                $allBookingsStmt = $conn->prepare($allBookingsQuery);
                $allBookingsStmt->execute([$userId, $currentUser['email']]);
                $allBookings = $allBookingsStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($allBookings) > 0): ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Bookings (<?php echo count($allBookings); ?>)</h5>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="filterBookings('all')">All</button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="filterBookings('pending')">Pending</button>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="filterBookings('confirmed')">Confirmed</button>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="filterBookings('cancelled')">Cancelled</button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Booking ID</th>
                                            <th>Room Type</th>
                                            <th>Check-in</th>
                                            <th>Check-out</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Payment</th>
                                            <th>Total</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allBookings as $booking): ?>
                                            <tr class="booking-row" data-status="<?php echo strtolower($booking['booking_status']); ?>">
                                                <td>
                                                    <strong>#<?php echo str_pad($booking['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                                                    <br><small class="text-muted"><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($booking['type_name'] ?? 'N/A'); ?></strong>
                                                    <?php if ($booking['room_description']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($booking['room_description'], 0, 50)) . '...'; ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo date('M j, Y', strtotime($booking['check_in_datetime'])); ?></strong>
                                                    <br><small class="text-muted"><?php echo date('g:i A', strtotime($booking['check_in_datetime'])); ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo date('M j, Y', strtotime($booking['check_out_datetime'])); ?></strong>
                                                    <br><small class="text-muted"><?php echo date('g:i A', strtotime($booking['check_out_datetime'])); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $booking['duration_hours']; ?> hours</span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = '';
                                                    switch(strtolower($booking['booking_status'])) {
                                                        case 'pending':
                                                            $statusClass = 'bg-warning';
                                                            break;
                                                        case 'confirmed':
                                                            $statusClass = 'bg-success';
                                                            break;
                                                        case 'checked_in':
                                                            $statusClass = 'bg-primary';
                                                            break;
                                                        case 'checked_out':
                                                            $statusClass = 'bg-secondary';
                                                            break;
                                                        case 'cancelled':
                                                            $statusClass = 'bg-danger';
                                                            break;
                                                        default:
                                                            $statusClass = 'bg-secondary';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($booking['booking_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $paymentClass = '';
                                                    switch(strtolower($booking['payment_status'])) {
                                                        case 'pending':
                                                            $paymentClass = 'bg-warning';
                                                            break;
                                                        case 'paid':
                                                            $paymentClass = 'bg-success';
                                                            break;
                                                        case 'refunded':
                                                            $paymentClass = 'bg-info';
                                                            break;
                                                        default:
                                                            $paymentClass = 'bg-secondary';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $paymentClass; ?>">
                                                        <?php echo ucfirst($booking['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong>₱<?php echo number_format($booking['total_price'], 2); ?></strong>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if (in_array(strtolower($booking['booking_status']), ['confirmed', 'checked_in'])): ?>
                                                            <button type="button" class="btn btn-outline-success btn-sm" onclick="showExtendTimeModal(<?php echo $booking['id']; ?>)" title="Extend Time">
                                                                <i class="fas fa-clock"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if (strtolower($booking['booking_status']) === 'pending'): ?>
                                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="cancelBooking(<?php echo $booking['id']; ?>)" title="Cancel Booking">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
                            <h4 class="text-muted mb-3">No Bookings Found</h4>
                            <p class="text-muted mb-4">You haven't made any bookings yet. Start by making your first reservation!</p>
                            <a href="../index.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Make Your First Booking
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Profile Section -->
        <div id="profile-section" class="content-section" style="display: none;">
            <div class="content-header">
                <h2 class="mb-0"><i class="fas fa-user-cog me-2"></i>Profile Settings</h2>
                <p class="text-muted mb-0">Manage your account information and preferences</p>
            </div>
            
            <div class="content-body">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h5>
                            </div>
                            <div class="card-body">
                                <form id="profileForm" method="POST" action="#">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="profile_name" class="form-label">Full Name *</label>
                                            <input type="text" class="form-control" id="profile_name" name="name" value="<?php echo htmlspecialchars($currentUser['name']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="profile_email" class="form-label">Email Address *</label>
                                            <input type="email" class="form-control" id="profile_email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="profile_phone" class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" id="profile_phone" name="phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="profile_address" class="form-label">Address</label>
                                            <input type="text" class="form-control" id="profile_address" name="address" value="<?php echo htmlspecialchars($currentUser['address'] ?? ''); ?>">
                                        </div>
                                        <div class="col-12">
                                            <hr>
                                            <h6 class="text-muted mb-3"><i class="fas fa-lock me-2"></i>Change Password (Optional)</h6>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                        </div>
                                        <div class="col-12">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i>Update Profile
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="resetProfileForm()">
                                                    <i class="fas fa-undo me-2"></i>Reset
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Account Summary -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Account Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="user-avatar me-3" style="width: 60px; height: 60px; background: var(--primary-red); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($currentUser['name']); ?></h6>
                                        <small class="text-muted">Customer since <?php echo date('M Y', strtotime($currentUser['created_at'] ?? 'now')); ?></small>
                                    </div>
                                </div>
                                <hr>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="border-end">
                                            <h4 class="text-primary mb-1"><?php echo $totalBookings; ?></h4>
                                            <small class="text-muted">Total Bookings</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-success mb-1">₱<?php echo number_format($totalSpent, 2); ?></h4>
                                        <small class="text-muted">Total Spent</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Account Security -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Account Security</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="mb-1">Password</h6>
                                        <small class="text-muted">Last changed: Never</small>
                                    </div>
                                    <span class="badge bg-warning">Weak</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="mb-1">Email Verification</h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($currentUser['email']); ?></small>
                                    </div>
                                    <span class="badge bg-success">Verified</span>
                                </div>
                                <div class="d-grid">
                                    <button class="btn btn-outline-primary btn-sm" onclick="alert('Security settings will be implemented in a future update.')">
                                        <i class="fas fa-cog me-2"></i>Security Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>

    <!-- Extend Time Modal -->
    <div class="modal fade" id="extendTimeModal" tabindex="-1" aria-labelledby="extendTimeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="extendTimeModalLabel">
                        <i class="fas fa-clock me-2"></i>Extend Booking Time
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="extendTimeContent">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading booking details...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmExtendBtn" onclick="confirmTimeExtension()" disabled>
                        <i class="fas fa-check me-2"></i>Extend Time
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div class="modal fade" id="bookingDetailsModal" tabindex="-1" aria-labelledby="bookingDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingDetailsModalLabel">
                        <i class="fas fa-info-circle me-2"></i>Booking Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="bookingDetailsContent">
                    <!-- Booking details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

     
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
     <script>
         let currentBookingId = null;
         let currentBookingData = null;

         function showSection(sectionName) {
             // Hide all sections
             document.querySelectorAll('.content-section').forEach(section => {
                 section.style.display = 'none';
             });
             
             // Remove active class from all nav links
             document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                 link.classList.remove('active');
             });
             
             // Show selected section
             document.getElementById(sectionName + '-section').style.display = 'block';
             
             // Add active class to clicked nav link
             event.target.classList.add('active');
         }
         
         function toggleSidebar() {
             document.getElementById('sidebar').classList.toggle('show');
         }
         
         // Filter bookings by status
         function filterBookings(status) {
             const rows = document.querySelectorAll('.booking-row');
             const buttons = document.querySelectorAll('.btn-group button');
             
             // Remove active class from all buttons
             buttons.forEach(btn => btn.classList.remove('active'));
             
             // Add active class to clicked button
             event.target.classList.add('active');
             
             // Show/hide rows based on status
             rows.forEach(row => {
                 if (status === 'all' || row.dataset.status === status) {
                     row.style.display = '';
                 } else {
                     row.style.display = 'none';
                 }
             });
         }
         
         // Show extend time modal
         function showExtendTimeModal(bookingId) {
             currentBookingId = bookingId;
             
             // Reset modal content
             document.getElementById('extendTimeContent').innerHTML = `
                 <div class="text-center">
                     <div class="spinner-border text-primary" role="status">
                         <span class="visually-hidden">Loading...</span>
                     </div>
                     <p class="mt-2">Loading booking details...</p>
                 </div>
             `;
             document.getElementById('confirmExtendBtn').disabled = true;
             
             // Show modal
             const modal = new bootstrap.Modal(document.getElementById('extendTimeModal'));
             modal.show();
             
             // Load booking details
             fetch(`controller/TimeExtensionController.php?action=get_booking&booking_id=${bookingId}`)
                 .then(response => response.json())
                 .then(data => {
                     if (data.success) {
                         currentBookingData = data.data;
                         displayExtendTimeForm(data.data);
                     } else {
                         document.getElementById('extendTimeContent').innerHTML = `
                             <div class="alert alert-danger">
                                 <i class="fas fa-exclamation-triangle me-2"></i>
                                 ${data.message}
                             </div>
                         `;
                     }
                 })
                 .catch(error => {
                     console.error('Error:', error);
                     document.getElementById('extendTimeContent').innerHTML = `
                         <div class="alert alert-danger">
                             <i class="fas fa-exclamation-triangle me-2"></i>
                             Error loading booking details. Please try again.
                         </div>
                     `;
                 });
         }

         // Display extend time form
         function displayExtendTimeForm(booking) {
             const checkOutDate = new Date(booking.check_out_datetime);
             const formattedCheckOut = checkOutDate.toLocaleDateString('en-US', {
                 year: 'numeric',
                 month: 'long',
                 day: 'numeric',
                 hour: '2-digit',
                 minute: '2-digit'
             });

             document.getElementById('extendTimeContent').innerHTML = `
                 <div class="row">
                     <div class="col-12">
                         <div class="card bg-light">
                             <div class="card-body">
                                 <h6 class="card-title">Booking #${String(booking.id).padStart(4, '0')}</h6>
                                 <div class="row">
                                     <div class="col-md-6">
                                         <p class="mb-1"><strong>Room Type:</strong> ${booking.type_name}</p>
                                         <p class="mb-1"><strong>Current Duration:</strong> ${booking.duration_hours} hours</p>
                                     </div>
                                     <div class="col-md-6">
                                         <p class="mb-1"><strong>Current Total:</strong> ₱${parseFloat(booking.total_price).toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                                         <p class="mb-1"><strong>Current Check-out:</strong> ${formattedCheckOut}</p>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
                 
                 <div class="mt-4">
                     <label for="additionalHours" class="form-label">
                         <strong>Additional Hours</strong>
                         <small class="text-muted">(₱200 per hour)</small>
                     </label>
                     <select class="form-select" id="additionalHours" onchange="calculateExtensionCost()">
                         <option value="">Select additional hours...</option>
                         <option value="1">1 hour (+₱200)</option>
                         <option value="2">2 hours (+₱400)</option>
                         <option value="3">3 hours (+₱600)</option>
                         <option value="4">4 hours (+₱800)</option>
                         <option value="5">5 hours (+₱1,000)</option>
                         <option value="6">6 hours (+₱1,200)</option>
                         <option value="8">8 hours (+₱1,600)</option>
                         <option value="12">12 hours (+₱2,400)</option>
                     </select>
                 </div>
                 
                 <div id="extensionSummary" class="mt-4" style="display: none;">
                     <div class="card border-success">
                         <div class="card-header bg-success text-white">
                             <h6 class="mb-0"><i class="fas fa-calculator me-2"></i>Extension Summary</h6>
                         </div>
                         <div class="card-body">
                             <div class="row">
                                 <div class="col-md-6">
                                     <p class="mb-1"><strong>Additional Hours:</strong> <span id="summaryHours">-</span></p>
                                     <p class="mb-1"><strong>Additional Cost:</strong> <span id="summaryAdditionalCost">-</span></p>
                                 </div>
                                 <div class="col-md-6">
                                     <p class="mb-1"><strong>New Total Duration:</strong> <span id="summaryNewDuration">-</span></p>
                                     <p class="mb-1"><strong>New Total Cost:</strong> <span id="summaryNewTotal">-</span></p>
                                 </div>
                             </div>
                             <hr>
                             <p class="mb-0"><strong>New Check-out Time:</strong> <span id="summaryNewCheckout">-</span></p>
                         </div>
                     </div>
                 </div>
             `;
             
             document.getElementById('confirmExtendBtn').disabled = true;
         }

         // Calculate extension cost
         function calculateExtensionCost() {
             const additionalHours = parseInt(document.getElementById('additionalHours').value);
             
             if (!additionalHours || !currentBookingData) {
                 document.getElementById('extensionSummary').style.display = 'none';
                 document.getElementById('confirmExtendBtn').disabled = true;
                 return;
             }
             
             const hourlyRate = 200;
             const additionalCost = additionalHours * hourlyRate;
             const newDuration = currentBookingData.duration_hours + additionalHours;
             const newTotal = parseFloat(currentBookingData.total_price) + additionalCost;
             
             // Calculate new checkout time
             const currentCheckOut = new Date(currentBookingData.check_out_datetime);
             const newCheckOut = new Date(currentCheckOut.getTime() + (additionalHours * 60 * 60 * 1000));
             const formattedNewCheckOut = newCheckOut.toLocaleDateString('en-US', {
                 year: 'numeric',
                 month: 'long',
                 day: 'numeric',
                 hour: '2-digit',
                 minute: '2-digit'
             });
             
             // Update summary
             document.getElementById('summaryHours').textContent = additionalHours + ' hours';
             document.getElementById('summaryAdditionalCost').textContent = '₱' + additionalCost.toLocaleString('en-US', {minimumFractionDigits: 2});
             document.getElementById('summaryNewDuration').textContent = newDuration + ' hours';
             document.getElementById('summaryNewTotal').textContent = '₱' + newTotal.toLocaleString('en-US', {minimumFractionDigits: 2});
             document.getElementById('summaryNewCheckout').textContent = formattedNewCheckOut;
             
             // Show summary and enable button
             document.getElementById('extensionSummary').style.display = 'block';
             document.getElementById('confirmExtendBtn').disabled = false;
         }

         // Confirm time extension
         function confirmTimeExtension() {
             const additionalHours = parseInt(document.getElementById('additionalHours').value);
             
             if (!additionalHours || !currentBookingId) {
                 alert('Please select additional hours');
                 return;
             }
             
             // Disable button and show loading
             const confirmBtn = document.getElementById('confirmExtendBtn');
             const originalText = confirmBtn.innerHTML;
             confirmBtn.disabled = true;
             confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
             
             // Submit extension request
             const formData = new FormData();
             formData.append('booking_id', currentBookingId);
             formData.append('additional_hours', additionalHours);
             
             fetch('controller/TimeExtensionController.php?action=extend', {
                 method: 'POST',
                 body: formData
             })
             .then(response => response.json())
             .then(data => {
                 if (data.success) {
                     alert('Time extended successfully! Additional cost: ₱' + data.data.additional_cost.toLocaleString('en-US', {minimumFractionDigits: 2}));
                     
                     // Close modal and reload page
                     bootstrap.Modal.getInstance(document.getElementById('extendTimeModal')).hide();
                     window.location.reload();
                 } else {
                     alert('Error: ' + data.message);
                     confirmBtn.disabled = false;
                     confirmBtn.innerHTML = originalText;
                 }
             })
             .catch(error => {
                 console.error('Error:', error);
                 alert('An error occurred while extending time. Please try again.');
                 confirmBtn.disabled = false;
                 confirmBtn.innerHTML = originalText;
             });
         }
         
         // View booking details (placeholder function)
         function viewBookingDetails(bookingId) {
             alert('Viewing details for booking #' + bookingId + '\n\nThis feature will be implemented in a future update.');
         }
         
         // Cancel booking (placeholder function)
         function cancelBooking(bookingId) {
             if (confirm('Are you sure you want to cancel booking #' + bookingId + '?\n\nThis action cannot be undone.')) {
                 alert('Booking cancellation feature will be implemented in a future update.');
             }
         }
         
         // Profile form functions
         function resetProfileForm() {
             const form = document.getElementById('profileForm');
             form.reset();
             
             // Reset to original values
             document.getElementById('profile_name').value = '<?php echo htmlspecialchars($currentUser['name']); ?>';
             document.getElementById('profile_email').value = '<?php echo htmlspecialchars($currentUser['email']); ?>';
             document.getElementById('profile_phone').value = '<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>';
             document.getElementById('profile_address').value = '<?php echo htmlspecialchars($currentUser['address'] ?? ''); ?>';
             
             // Clear password fields
             document.getElementById('current_password').value = '';
             document.getElementById('new_password').value = '';
             document.getElementById('confirm_password').value = '';
         }
         
         // Profile form validation and submission
         document.addEventListener('DOMContentLoaded', function() {
             const profileForm = document.getElementById('profileForm');
             
             if (profileForm) {
                 profileForm.addEventListener('submit', function(e) {
                     e.preventDefault();
                     
                     const formData = new FormData(this);
                     const currentPassword = formData.get('current_password');
                     const newPassword = formData.get('new_password');
                     const confirmPassword = formData.get('confirm_password');
                     
                     // Validate password fields if any password field is filled
                     if (currentPassword || newPassword || confirmPassword) {
                         if (!currentPassword) {
                             alert('Please enter your current password to change it.');
                             return;
                         }
                         if (!newPassword) {
                             alert('Please enter a new password.');
                             return;
                         }
                         if (newPassword !== confirmPassword) {
                             alert('New password and confirmation do not match.');
                             return;
                         }
                         if (newPassword.length < 6) {
                             alert('New password must be at least 6 characters long.');
                             return;
                         }
                     }
                     
                     // Add action to form data
                     formData.append('action', 'update_profile');
                     
                     // Submit form via AJAX
                      fetch('includes/controller/update_profile.php', {
                          method: 'POST',
                          body: formData
                      })
                     .then(response => response.json())
                     .then(data => {
                         if (data.success) {
                             alert('Profile updated successfully!');
                             // Clear password fields after successful update
                             document.getElementById('current_password').value = '';
                             document.getElementById('new_password').value = '';
                             document.getElementById('confirm_password').value = '';
                             
                             // Reload page to reflect changes
                             window.location.reload();
                         } else {
                             alert('Error: ' + (data.message || 'Failed to update profile'));
                         }
                     })
                     .catch(error => {
                         console.error('Error:', error);
                         alert('An error occurred while updating your profile.');
                     });
                 });
             }
         });
     </script>
</body>
</html>