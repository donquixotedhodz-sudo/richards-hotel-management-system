<?php
require_once '../centralized-login/SessionManager.php';
require_once '../config/database.php';

// Require customer authentication
SessionManager::requireCustomer();

$currentUser = SessionManager::getCurrentUser();
$page_title = 'My Bookings';
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
        
        <!-- My Bookings Section -->
        <div id="bookings-section" class="content-section">
            <div class="content-header">
                <h2 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>My Bookings</h2>
                <p class="text-muted mb-0">View and manage all your bookings</p>
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
                            <!-- <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-secondary btn-sm active" onclick="filterBookings('all')">All</button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="filterBookings('pending')">Pending</button>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="filterBookings('confirmed')">Confirmed</button>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="filterBookings('cancelled')">Cancelled</button>
                            </div> -->
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
                                            <th class="text-center">Actions</th>
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
                                                <td class="text-end">
                                                    <div class="d-flex justify-content-end gap-1">
                                                         <button type="button" class="btn btn-warning-1 btn-xs square-btn border-2" onclick="showViewBookingModal(<?php echo $booking['id']; ?>)" title="View Booking">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        <?php if (in_array(strtolower($booking['booking_status']), ['pending', 'confirmed'])): ?>
                                                            <button type="button" class="btn btn-warning btn-xs square-btn border-2" onclick="showEditBookingModal(<?php echo $booking['id']; ?>)" title="Edit Booking">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if (in_array(strtolower($booking['booking_status']), ['confirmed', 'checked_in'])): ?>
                                                            <button type="button" class="btn btn-success btn-xs square-btn border-2" onclick="showExtendTimeModal(<?php echo $booking['id']; ?>)" title="Extend Time">
                                                                <i class="fas fa-clock"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if (strtolower($booking['booking_status']) === 'pending'): ?>
                                                            <button type="button" class="btn btn-danger btn-xs square-btn border-2" onclick="cancelBooking(<?php echo $booking['id']; ?>)" title="Cancel Booking">
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

    <!-- Edit Booking Modal -->
    <div class="modal fade" id="editBookingModal" tabindex="-1" aria-labelledby="editBookingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBookingModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit Booking
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="editBookingContent">
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
                    <button type="button" class="btn btn-primary" id="saveBookingBtn" onclick="saveBookingChanges()" disabled>
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notificationContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        <!-- Notifications will be dynamically added here -->
    </div>

     
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
     <script>
         let currentBookingId = null;
         let currentBookingData = null;
         
         // Notification System
         function showNotification(message, type = 'success', duration = 5000) {
             const notificationContainer = document.getElementById('notificationContainer');
             const notificationId = 'notification-' + Date.now();
             
             // Define notification types and their styles
             const notificationTypes = {
                 success: {
                     bgClass: 'bg-success',
                     icon: 'fas fa-check-circle',
                     title: 'Success!'
                 },
                 error: {
                     bgClass: 'bg-danger',
                     icon: 'fas fa-exclamation-circle',
                     title: 'Error!'
                 },
                 warning: {
                     bgClass: 'bg-warning',
                     icon: 'fas fa-exclamation-triangle',
                     title: 'Warning!'
                 },
                 info: {
                     bgClass: 'bg-info',
                     icon: 'fas fa-info-circle',
                     title: 'Information'
                 }
             };
             
             const notificationType = notificationTypes[type] || notificationTypes.info;
             
             // Create notification element
             const notificationHTML = `
                 <div id="${notificationId}" class="toast align-items-center text-white ${notificationType.bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                     <div class="d-flex">
                         <div class="toast-body">
                             <div class="d-flex align-items-center">
                                 <i class="${notificationType.icon} me-2"></i>
                                 <div>
                                     <strong>${notificationType.title}</strong>
                                     <div>${message}</div>
                                 </div>
                             </div>
                         </div>
                         <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                     </div>
                 </div>
             `;
             
             // Add notification to container
             notificationContainer.insertAdjacentHTML('beforeend', notificationHTML);
             
             // Initialize and show the toast
             const toastElement = document.getElementById(notificationId);
             const toast = new bootstrap.Toast(toastElement, {
                 autohide: true,
                 delay: duration
             });
             
             toast.show();
             
             // Remove the toast element after it's hidden
             toastElement.addEventListener('hidden.bs.toast', function() {
                 toastElement.remove();
             });
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
                 showNotification('Please select additional hours', 'warning');
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
                     const additionalCost = data.data.additional_cost.toLocaleString('en-US', {minimumFractionDigits: 2});
                     showNotification(`Time extended successfully! Additional cost: ₱${additionalCost}`, 'success');
                     
                     // Close modal and reload page after a short delay to show notification
                     bootstrap.Modal.getInstance(document.getElementById('extendTimeModal')).hide();
                     setTimeout(() => {
                         window.location.reload();
                     }, 1500);
                 } else {
                     showNotification(`Error: ${data.message}`, 'error');
                     confirmBtn.disabled = false;
                     confirmBtn.innerHTML = originalText;
                 }
             })
             .catch(error => {
                 console.error('Error:', error);
                 showNotification('An error occurred while extending time. Please try again.', 'error');
                 confirmBtn.disabled = false;
                 confirmBtn.innerHTML = originalText;
             });
         }
         
         // Show view booking modal
         function showViewBookingModal(bookingId) {
             // Reset modal content
             document.getElementById('bookingDetailsContent').innerHTML = `
                 <div class="text-center">
                     <div class="spinner-border text-primary" role="status">
                         <span class="visually-hidden">Loading...</span>
                     </div>
                     <p class="mt-2">Loading booking details...</p>
                 </div>
             `;
             
             // Show modal
             const modal = new bootstrap.Modal(document.getElementById('bookingDetailsModal'));
             modal.show();
             
             // Load booking details
             fetch(`controller/TimeExtensionController.php?action=get_booking_for_view&booking_id=${bookingId}`)
                 .then(response => response.json())
                 .then(data => {
                     if (data.success) {
                         displayBookingDetails(data.data);
                     } else {
                         document.getElementById('bookingDetailsContent').innerHTML = `
                             <div class="alert alert-danger">
                                 <i class="fas fa-exclamation-triangle me-2"></i>
                                 ${data.message}
                             </div>
                         `;
                     }
                 })
                 .catch(error => {
                     console.error('Error:', error);
                     document.getElementById('bookingDetailsContent').innerHTML = `
                         <div class="alert alert-danger">
                             <i class="fas fa-exclamation-triangle me-2"></i>
                             Error loading booking details. Please try again.
                         </div>
                     `;
                 });
         }

         // Display booking details in modal
         function displayBookingDetails(booking) {
             const checkInDate = new Date(booking.check_in_datetime);
             const checkOutDate = new Date(booking.check_out_datetime);
             const createdDate = new Date(booking.created_at);
             
             // Format dates for display
             const formatDate = (date) => {
                 return date.toLocaleDateString('en-US', {
                     year: 'numeric',
                     month: 'long',
                     day: 'numeric',
                     hour: '2-digit',
                     minute: '2-digit'
                 });
             };

             // Status badge classes
             const getStatusBadge = (status) => {
                 const statusClasses = {
                     'pending': 'bg-warning',
                     'confirmed': 'bg-success',
                     'checked_in': 'bg-primary',
                     'checked_out': 'bg-secondary',
                     'cancelled': 'bg-danger'
                 };
                 return statusClasses[status.toLowerCase()] || 'bg-secondary';
             };

             const getPaymentBadge = (status) => {
                 const paymentClasses = {
                     'pending': 'bg-warning',
                     'paid': 'bg-success',
                     'refunded': 'bg-info'
                 };
                 return paymentClasses[status.toLowerCase()] || 'bg-secondary';
             };

             document.getElementById('bookingDetailsContent').innerHTML = `
                 <div class="row">
                     <div class="col-12">
                         <div class="card border-primary mb-4">
                             <div class="card-header bg-primary text-white">
                                 <h5 class="mb-0">
                                     <i class="fas fa-calendar-alt me-2"></i>
                                     Booking #${String(booking.id).padStart(4, '0')}
                                 </h5>
                             </div>
                             <div class="card-body">
                                 <div class="row">
                                     <div class="col-md-6">
                                         <h6 class="text-primary mb-3"><i class="fas fa-user me-2"></i>Customer Information</h6>
                                         <p class="mb-2"><strong>Name:</strong> ${booking.customer_name || 'N/A'}</p>
                                         <p class="mb-2"><strong>Email:</strong> ${booking.customer_email || 'N/A'}</p>
                                         <p class="mb-2"><strong>Phone:</strong> ${booking.customer_phone || 'N/A'}</p>
                                     </div>
                                     <div class="col-md-6">
                                         <h6 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i>Booking Status</h6>
                                         <p class="mb-2">
                                             <strong>Status:</strong> 
                                             <span class="badge ${getStatusBadge(booking.booking_status)} ms-2">
                                                 ${booking.booking_status.charAt(0).toUpperCase() + booking.booking_status.slice(1)}
                                             </span>
                                         </p>
                                         <p class="mb-2">
                                             <strong>Payment:</strong> 
                                             <span class="badge ${getPaymentBadge(booking.payment_status)} ms-2">
                                                 ${booking.payment_status.charAt(0).toUpperCase() + booking.payment_status.slice(1)}
                                             </span>
                                         </p>
                                         <p class="mb-2"><strong>Created:</strong> ${formatDate(createdDate)}</p>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>

                 <div class="row">
                     <div class="col-md-6">
                         <div class="card border-info mb-3">
                             <div class="card-header bg-info text-white">
                                 <h6 class="mb-0"><i class="fas fa-bed me-2"></i>Room Details</h6>
                             </div>
                             <div class="card-body">
                                 <p class="mb-2"><strong>Room Type:</strong> ${booking.type_name || 'N/A'}</p>
                                 <p class="mb-2"><strong>Duration:</strong> ${booking.duration_hours} hours</p>
                                 ${booking.room_description ? `<p class="mb-0"><strong>Description:</strong> ${booking.room_description}</p>` : ''}
                             </div>
                         </div>
                     </div>
                     <div class="col-md-6">
                         <div class="card border-success mb-3">
                             <div class="card-header bg-success text-white">
                                 <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Schedule</h6>
                             </div>
                             <div class="card-body">
                                 <p class="mb-2"><strong>Check-in:</strong> ${formatDate(checkInDate)}</p>
                                 <p class="mb-0"><strong>Check-out:</strong> ${formatDate(checkOutDate)}</p>
                             </div>
                         </div>
                     </div>
                 </div>

                 <div class="row">
                     <div class="col-12">
                         <div class="card border-warning">
                             <div class="card-header bg-warning text-dark">
                                 <h6 class="mb-0"><i class="fas fa-peso-sign me-2"></i>Payment Information</h6>
                             </div>
                             <div class="card-body">
                                 <div class="row">
                                     <div class="col-md-6">
                                         <p class="mb-2"><strong>Total Amount:</strong> <span class="text-success fs-5">₱${parseFloat(booking.total_price).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></p>
                                     </div>
                                     <div class="col-md-6">
                                         <p class="mb-2"><strong>Payment Method:</strong> ${booking.payment_method || 'Cash'}</p>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>

                 ${booking.special_requests ? `
                 <div class="row mt-3">
                     <div class="col-12">
                         <div class="card border-secondary">
                             <div class="card-header bg-secondary text-white">
                                 <h6 class="mb-0"><i class="fas fa-comment me-2"></i>Special Requests</h6>
                             </div>
                             <div class="card-body">
                                 <p class="mb-0">${booking.special_requests}</p>
                             </div>
                         </div>
                     </div>
                 </div>
                 ` : ''}
             `;
         }

         // View booking details (placeholder function)
         function viewBookingDetails(bookingId) {
             showViewBookingModal(bookingId);
         }
         
         // Cancel booking (placeholder function)
         function cancelBooking(bookingId) {
             if (confirm('Are you sure you want to cancel booking #' + bookingId + '?\n\nThis action cannot be undone.')) {
                 showNotification('Booking cancellation feature will be implemented in a future update.', 'info');
             }
         }
         
         // Show edit booking modal
         function showEditBookingModal(bookingId) {
             // Reset modal content
             document.getElementById('editBookingContent').innerHTML = `
                 <div class="text-center">
                     <div class="spinner-border text-primary" role="status">
                         <span class="visually-hidden">Loading...</span>
                     </div>
                     <p class="mt-2">Loading booking details...</p>
                 </div>
             `;
             document.getElementById('saveBookingBtn').disabled = true;
             
             // Show modal
             const modal = new bootstrap.Modal(document.getElementById('editBookingModal'));
             modal.show();
             
             // Load booking details for editing using the correct endpoint
             fetch(`controller/TimeExtensionController.php?action=get_booking_for_edit&booking_id=${bookingId}`)
                 .then(response => response.json())
                 .then(data => {
                     if (data.success) {
                         displayEditBookingForm(data.booking);
                     } else {
                         document.getElementById('editBookingContent').innerHTML = `
                             <div class="alert alert-danger">
                                 <i class="fas fa-exclamation-triangle me-2"></i>
                                 ${data.message}
                             </div>
                         `;
                     }
                 })
                 .catch(error => {
                     console.error('Error:', error);
                     document.getElementById('editBookingContent').innerHTML = `
                         <div class="alert alert-danger">
                             <i class="fas fa-exclamation-triangle me-2"></i>
                             Error loading booking details. Please try again.
                         </div>
                     `;
                 });
         }

         // Display edit booking form
         function displayEditBookingForm(booking) {
             const checkInDate = new Date(booking.check_in_datetime);
             const checkOutDate = new Date(booking.check_out_datetime);
             
             // Format dates for input fields
             const formatDateForInput = (date) => {
                 const year = date.getFullYear();
                 const month = String(date.getMonth() + 1).padStart(2, '0');
                 const day = String(date.getDate()).padStart(2, '0');
                 const hours = String(date.getHours()).padStart(2, '0');
                 const minutes = String(date.getMinutes()).padStart(2, '0');
                 return `${year}-${month}-${day}T${hours}:${minutes}`;
             };

             document.getElementById('editBookingContent').innerHTML = `
                 <form id="editBookingForm">
                     <input type="hidden" id="editBookingId" value="${booking.id}">
                     
                     <div class="row mb-3">
                         <div class="col-12">
                             <div class="alert alert-info">
                                 <i class="fas fa-info-circle me-2"></i>
                                 <strong>Booking #${String(booking.id).padStart(4, '0')}</strong> - ${booking.type_name}
                             </div>
                         </div>
                     </div>
                     
                     <div class="row mb-3">
                         <div class="col-md-6">
                             <label for="editCustomerName" class="form-label">Customer Name</label>
                             <input type="text" class="form-control" id="editCustomerName" value="${booking.customer_name || ''}" required>
                         </div>
                         <div class="col-md-6">
                             <label for="editCustomerPhone" class="form-label">Phone Number</label>
                             <input type="tel" class="form-control" id="editCustomerPhone" value="${booking.customer_phone || ''}" required>
                         </div>
                     </div>
                     
                     <div class="row mb-3">
                         <div class="col-12">
                             <label for="editCustomerEmail" class="form-label">Email Address</label>
                             <input type="email" class="form-control" id="editCustomerEmail" value="${booking.customer_email || ''}" required>
                         </div>
                     </div>
                     
                     <div class="row mb-3">
                         <div class="col-md-6">
                             <label for="editCheckIn" class="form-label">Check-in Date & Time</label>
                             <input type="datetime-local" class="form-control" id="editCheckIn" value="${formatDateForInput(checkInDate)}" required>
                         </div>
                         <div class="col-md-6">
                             <label for="editCheckOut" class="form-label">Check-out Date & Time</label>
                             <input type="datetime-local" class="form-control" id="editCheckOut" value="${formatDateForInput(checkOutDate)}" required>
                         </div>
                     </div>
                     
                     <div class="row mb-3">
                         <div class="col-12">
                             <label for="editSpecialRequests" class="form-label">Special Requests</label>
                             <textarea class="form-control" id="editSpecialRequests" rows="3" placeholder="Any special requests or notes...">${booking.special_requests || ''}</textarea>
                         </div>
                     </div>
                     
                     <div class="row">
                         <div class="col-12">
                             <div class="alert alert-warning">
                                 <i class="fas fa-exclamation-triangle me-2"></i>
                                 <strong>Note:</strong> Changes to dates may affect the total price. The system will recalculate the cost based on the new duration.
                             </div>
                         </div>
                     </div>
                 </form>
             `;
             
             // Enable save button
             document.getElementById('saveBookingBtn').disabled = false;
             
             // Add event listeners for date validation
             document.getElementById('editCheckIn').addEventListener('change', validateEditDates);
             document.getElementById('editCheckOut').addEventListener('change', validateEditDates);
         }

         // Validate edit booking dates
         function validateEditDates() {
             const checkIn = new Date(document.getElementById('editCheckIn').value);
             const checkOut = new Date(document.getElementById('editCheckOut').value);
             const now = new Date();
             
             let isValid = true;
             let message = '';
             
             if (checkIn < now) {
                 isValid = false;
                 message = 'Check-in date cannot be in the past';
             } else if (checkOut <= checkIn) {
                 isValid = false;
                 message = 'Check-out date must be after check-in date';
             }
             
             const saveBtn = document.getElementById('saveBookingBtn');
             if (isValid) {
                 saveBtn.disabled = false;
                 // Remove any existing error messages
                 const existingAlert = document.querySelector('#editBookingForm .alert-danger');
                 if (existingAlert) {
                     existingAlert.remove();
                 }
             } else {
                 saveBtn.disabled = true;
                 // Show error message
                 const existingAlert = document.querySelector('#editBookingForm .alert-danger');
                 if (existingAlert) {
                     existingAlert.remove();
                 }
                 const errorAlert = `
                     <div class="alert alert-danger">
                         <i class="fas fa-exclamation-triangle me-2"></i>
                         ${message}
                     </div>
                 `;
                 document.getElementById('editBookingForm').insertAdjacentHTML('afterbegin', errorAlert);
             }
         }

         // Save booking changes
         function saveBookingChanges() {
             const bookingId = document.getElementById('editBookingId').value;
             const customerName = document.getElementById('editCustomerName').value.trim();
             const customerEmail = document.getElementById('editCustomerEmail').value.trim();
             const customerPhone = document.getElementById('editCustomerPhone').value.trim();
             const checkIn = document.getElementById('editCheckIn').value;
             const checkOut = document.getElementById('editCheckOut').value;
             const specialRequests = document.getElementById('editSpecialRequests').value.trim();
             
             // Validate required fields
             if (!customerName || !customerEmail || !customerPhone || !checkIn || !checkOut) {
                 showNotification('Please fill in all required fields', 'warning');
                 return;
             }
             
             // Validate email format
             const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
             if (!emailRegex.test(customerEmail)) {
                 showNotification('Please enter a valid email address', 'warning');
                 return;
             }
             
             // Disable button and show loading
             const saveBtn = document.getElementById('saveBookingBtn');
             const originalText = saveBtn.innerHTML;
             saveBtn.disabled = true;
             saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
             
             // Prepare form data
             const formData = new FormData();
             formData.append('booking_id', bookingId);
             formData.append('customer_name', customerName);
             formData.append('customer_email', customerEmail);
             formData.append('customer_phone', customerPhone);
             formData.append('check_in_datetime', checkIn);
             formData.append('check_out_datetime', checkOut);
             formData.append('special_requests', specialRequests);
             
             // Submit the changes (Note: This would require a new endpoint in the controller)
             fetch('controller/EditBookingController.php', {
                 method: 'POST',
                 body: formData
             })
             .then(response => response.json())
             .then(data => {
                 if (data.success) {
                     showNotification('Booking updated successfully!', 'success');
                     
                     // Close modal and reload page after a short delay
                     bootstrap.Modal.getInstance(document.getElementById('editBookingModal')).hide();
                     setTimeout(() => {
                         window.location.reload();
                     }, 1500);
                 } else {
                     showNotification(`Error: ${data.message}`, 'error');
                     saveBtn.disabled = false;
                     saveBtn.innerHTML = originalText;
                 }
             })
             .catch(error => {
                 console.error('Error:', error);
                 showNotification('An error occurred while saving changes. Please try again.', 'error');
                 saveBtn.disabled = false;
                 saveBtn.innerHTML = originalText;
             });
         }

         // View booking details (placeholder function)
         function viewBookingDetails(bookingId) {
             showNotification(`Viewing details for booking #${bookingId}. This feature will be implemented in a future update.`, 'info');
         }
         
         // Cancel booking (placeholder function)
         function cancelBooking(bookingId) {
             if (confirm('Are you sure you want to cancel booking #' + bookingId + '?\n\nThis action cannot be undone.')) {
                 showNotification('Booking cancellation feature will be implemented in a future update.', 'info');
             }
         }
     </script>
</body>
</html>