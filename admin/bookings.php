<?php
require_once '../centralized-login/SessionManager.php';
require_once '../config/database.php';
require_once '../email-notification/BookingNotificationService.php';

// Require admin authentication
SessionManager::requireAdmin();

$currentUser = SessionManager::getCurrentUser();
$page_title = 'Bookings Management';
$additional_css = ['css/dashboard.css'];

// Handle status updates
if ($_POST && isset($_POST['action']) && isset($_POST['booking_id'])) {
    $bookingId = (int)$_POST['booking_id'];
    $action = $_POST['action'];
    
    // Initialize email notification service
    $emailService = new BookingNotificationService($conn);
    
    try {
        switch ($action) {
            case 'confirm':
                // First, get the room_id from the booking
                $getRoomStmt = $conn->prepare("SELECT room_id FROM bookings WHERE id = ?");
                $getRoomStmt->execute([$bookingId]);
                $booking = $getRoomStmt->fetch(PDO::FETCH_ASSOC);
                
                // Update booking status
                $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'confirmed', payment_status = 'paid' WHERE id = ?");
                $stmt->execute([$bookingId]);
                
                // Update room status to occupied if room is assigned
                if ($booking && $booking['room_id']) {
                    $roomStmt = $conn->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?");
                    $roomStmt->execute([$booking['room_id']]);
                }
                
                // Send confirmation email
                if ($emailService->sendBookingConfirmation($bookingId)) {
                    $success_message = "Booking confirmed successfully and confirmation email sent!";
                } else {
                    $success_message = "Booking confirmed successfully, but failed to send confirmation email.";
                }
                break;
                
            case 'cancel':
                // First, get the room_id from the booking
                $getRoomStmt = $conn->prepare("SELECT room_id FROM bookings WHERE id = ?");
                $getRoomStmt->execute([$bookingId]);
                $booking = $getRoomStmt->fetch(PDO::FETCH_ASSOC);
                
                // Update booking status
                $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE id = ?");
                $stmt->execute([$bookingId]);
                
                // Update room status back to available if room was assigned
                if ($booking && $booking['room_id']) {
                    $roomStmt = $conn->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
                    $roomStmt->execute([$booking['room_id']]);
                }
                
                // Send cancellation email
                if ($emailService->sendBookingCancellation($bookingId)) {
                    $success_message = "Booking cancelled successfully and cancellation email sent!";
                } else {
                    $success_message = "Booking cancelled successfully, but failed to send cancellation email.";
                }
                break;
        }
    } catch (Exception $e) {
        $error_message = "Error updating booking: " . $e->getMessage();
    }
}

// Remove pagination - show all records

// Status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$valid_statuses = ['pending', 'confirmed', 'cancelled', 'checked_in', 'checked_out'];
$status_filter = in_array($status_filter, $valid_statuses) ? $status_filter : '';

// No pagination needed - showing all records

// Fetch bookings with pagination
$query = "
    SELECT 
        b.*,
        rt.type_name,
        rt.description as room_description,
        r.room_number,
        u.first_name,
        u.last_name,
        u.email as user_email
    FROM bookings b
    LEFT JOIN room_types rt ON b.room_type_id = rt.id
    LEFT JOIN rooms r ON b.room_id = r.id
    LEFT JOIN users u ON b.user_id = u.id
";

if ($status_filter) {
    $query .= " WHERE b.booking_status = :status";
}

$query .= " ORDER BY b.created_at DESC";

$stmt = $conn->prepare($query);

// Bind status filter parameter if exists
if ($status_filter) {
    $stmt->bindValue(':status', $status_filter, PDO::PARAM_STR);
}

$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get booking statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
        SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
        SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
        SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as total_revenue
    FROM bookings
";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

?>
<?php include 'includes/header.php'; ?>
<style>
/* Form Styles */
.form-select-sm {
    border-radius: 0.375rem;
    border: 1px solid #ced4da;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-select-sm:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Scrollable Table Styles */
.table-responsive {
    border-radius: 0.375rem;
}

.table-responsive::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="content-section active">
        <div class="content-header">
            <h2 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Bookings Management</h2>
            <p class="text-muted mb-0">Manage all hotel bookings and reservations</p>
        </div>
        
        <div class="content-body">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            
            
            <!-- Bookings Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Bookings</h5>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" onchange="changeStatus(this.value)" style="width: auto;">
                                <option value="" <?php echo ($status_filter == '') ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo ($status_filter == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo ($status_filter == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="cancelled" <?php echo ($status_filter == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="checked_in" <?php echo ($status_filter == 'checked_in') ? 'selected' : ''; ?>>Checked In</option>
                                <option value="checked_out" <?php echo ($status_filter == 'checked_out') ? 'selected' : ''; ?>>Checked Out</option>
                            </select>

                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-hover table-striped mb-0 table-sm" id="bookingsTable" style="font-size: 0.875rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-nowrap" style="width: 60px;">ID</th>
                                    <th class="text-nowrap" style="width: 150px;">Customer</th>
                                    <th class="text-nowrap d-none d-md-table-cell" style="width: 100px;">Room Type</th>
                                    <th class="text-nowrap d-none d-lg-table-cell" style="width: 80px;">Room</th>
                                    <th class="text-nowrap" style="width: 90px;">Check-in</th>
                                    <th class="text-nowrap d-none d-sm-table-cell" style="width: 90px;">Check-out</th>
                                    <th class="text-nowrap d-none d-lg-table-cell" style="width: 70px;">Duration</th>
                                    <th class="text-nowrap" style="width: 80px;">Price</th>
                                    <th class="text-nowrap" style="width: 80px;">Status</th>
                                    <th class="text-nowrap d-none d-md-table-cell" style="width: 80px;">Payment</th>
                                    <th class="text-nowrap" style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                <tr data-status="<?php echo $booking['booking_status']; ?>">
                                    <td class="text-nowrap" style="padding: 0.375rem 0.5rem;"><strong>#<?php echo $booking['id']; ?></strong></td>
                                    <td style="padding: 0.375rem 0.5rem; max-width: 150px; overflow: hidden; text-overflow: ellipsis;">
                                        <div>
                                            <strong style="font-size: 0.85rem;"><?php echo htmlspecialchars($booking['customer_name']); ?></strong>
                                            <br><small class="text-muted d-none d-sm-inline" style="font-size: 0.75rem;"><?php echo htmlspecialchars($booking['customer_email']); ?></small>
                                        </div>
                                    </td>
                                    <td class="d-none d-md-table-cell" style="padding: 0.375rem 0.5rem;">
                                        <span class="badge bg-info" style="font-size: 0.7rem;"><?php echo htmlspecialchars($booking['type_name']); ?></span>
                                    </td>
                                    <td class="d-none d-lg-table-cell" style="padding: 0.375rem 0.5rem;">
                                        <?php if ($booking['room_number']): ?>
                                            <span class="badge bg-secondary" style="font-size: 0.7rem;">Room <?php echo htmlspecialchars($booking['room_number']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.75rem;">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap" style="padding: 0.375rem 0.5rem;">
                                        <small style="font-size: 0.75rem;"><?php echo date('M j, Y', strtotime($booking['check_in_datetime'])); ?></small>
                                        <br><small class="text-muted" style="font-size: 0.7rem;"><?php echo date('g:i A', strtotime($booking['check_in_datetime'])); ?></small>
                                    </td>
                                    <td class="text-nowrap d-none d-sm-table-cell" style="padding: 0.375rem 0.5rem;">
                                        <small style="font-size: 0.75rem;"><?php echo date('M j, Y', strtotime($booking['check_out_datetime'])); ?></small>
                                        <br><small class="text-muted" style="font-size: 0.7rem;"><?php echo date('g:i A', strtotime($booking['check_out_datetime'])); ?></small>
                                    </td>
                                    <td class="d-none d-lg-table-cell" style="padding: 0.375rem 0.5rem;">
                                        <span class="badge bg-light text-dark" style="font-size: 0.7rem;"><?php echo $booking['duration_hours']; ?>h</span>
                                    </td>
                                    <td class="text-nowrap" style="padding: 0.375rem 0.5rem;">
                                        <strong style="font-size: 0.8rem;">₱<?php echo number_format($booking['total_price'], 2); ?></strong>
                                    </td>
                                    <td class="text-nowrap" style="padding: 0.375rem 0.5rem;">
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        switch ($booking['booking_status']) {
                                            case 'confirmed':
                                                $status_class = 'bg-success';
                                                $status_text = 'Confirmed';
                                                break;
                                            case 'pending':
                                                $status_class = 'bg-warning text-dark';
                                                $status_text = 'Pending';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'bg-danger';
                                                $status_text = 'Cancelled';
                                                break;
                                            case 'completed':
                                                $status_class = 'bg-primary';
                                                $status_text = 'Completed';
                                                break;
                                            default:
                                                $status_class = 'bg-secondary';
                                                $status_text = ucfirst($booking['booking_status']);
                                        }
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>" style="font-size: 0.7rem;"><?php echo $status_text; ?></span>
                                    </td>
                                    <td class="d-none d-md-table-cell" style="padding: 0.375rem 0.5rem;">
                                        <?php
                                        $payment_class = '';
                                        $payment_text = '';
                                        switch ($booking['payment_status']) {
                                            case 'paid':
                                                $payment_class = 'bg-success';
                                                $payment_text = 'Paid';
                                                break;
                                            case 'pending':
                                                $payment_class = 'bg-warning text-dark';
                                                $payment_text = 'Pending';
                                                break;
                                            case 'failed':
                                                $payment_class = 'bg-danger';
                                                $payment_text = 'Failed';
                                                break;
                                            default:
                                                $payment_class = 'bg-secondary';
                                                $payment_text = ucfirst($booking['payment_status']);
                                        }
                                        ?>
                                        <span class="badge <?php echo $payment_class; ?>" style="font-size: 0.7rem;"><?php echo $payment_text; ?></span>
                                    </td>
                                    <td class="text-nowrap" style="padding: 0.375rem 0.5rem;">
                                        <div class="btn-group-vertical d-md-none" role="group" style="width: 100%;">
                                            <?php if ($booking['booking_status'] == 'pending'): ?>
                                                <form method="POST" style="display: inline; margin-bottom: 2px;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="action" value="confirm">
                                                    <button type="submit" class="btn btn-sm btn-outline-success me-1" title="Confirm">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger me-1" title="Cancel">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="edit_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary me-1" style="margin-bottom: 2px;" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="view_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-info me-1" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                        <div class="btn-group d-none d-md-flex" role="group">
                                            <?php if ($booking['booking_status'] == 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="action" value="confirm">
                                                    <button type="submit" class="btn btn-sm btn-outline-success me-1" title="Confirm">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger me-1" title="Cancel">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <button onclick="editBooking(<?php echo $booking['id']; ?>)" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="viewBookingDetails(<?php echo $booking['id']; ?>)" class="btn btn-sm btn-outline-info me-1" title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Booking Modal -->
<div class="modal fade" id="editBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editBookingForm">
                    <input type="hidden" id="edit_booking_id" name="booking_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Customer Name</label>
                                <input type="text" class="form-control" id="edit_customer_name" name="customer_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Customer Email</label>
                                <input type="email" class="form-control" id="edit_customer_email" name="customer_email" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Customer Phone</label>
                                <input type="text" class="form-control" id="edit_customer_phone" name="customer_phone" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Booking Status</label>
                                <select class="form-select" id="edit_booking_status" name="booking_status">
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="checked_in">Checked In</option>
                                    <option value="checked_out">Checked Out</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Check-in Date & Time</label>
                                <input type="datetime-local" class="form-control" id="edit_check_in" name="check_in_datetime" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Check-out Date & Time</label>
                                <input type="datetime-local" class="form-control" id="edit_check_out" name="check_out_datetime" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Special Requests</label>
                        <textarea class="form-control" id="edit_special_requests" name="special_requests" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="saveBookingChanges()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Booking Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="bookingDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Function to change status filter
function changeStatus(status) {
    const url = new URL(window.location);
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    // Remove any pagination parameters
    url.searchParams.delete('page');
    url.searchParams.delete('per_page');
    window.location.href = url.toString();
}

// Edit booking function
function editBooking(bookingId) {
    // Fetch booking data and populate modal
    fetch(`controller/BookingController.php?action=get&id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const booking = data.booking;
                document.getElementById('edit_booking_id').value = booking.id;
                document.getElementById('edit_customer_name').value = booking.customer_name;
                document.getElementById('edit_customer_email').value = booking.customer_email;
                document.getElementById('edit_customer_phone').value = booking.customer_phone;
                document.getElementById('edit_booking_status').value = booking.booking_status;
                document.getElementById('edit_check_in').value = booking.check_in_datetime.replace(' ', 'T');
                document.getElementById('edit_check_out').value = booking.check_out_datetime.replace(' ', 'T');
                document.getElementById('edit_special_requests').value = booking.special_requests || '';
                
                new bootstrap.Modal(document.getElementById('editBookingModal')).show();
            } else {
                alert('Error loading booking data: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading booking data');
        });
}

// Save booking changes
function saveBookingChanges() {
    const formData = new FormData(document.getElementById('editBookingForm'));
    formData.append('action', 'update');
    
    fetch('controller/BookingController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Booking updated successfully!');
            location.reload();
        } else {
            alert('Error updating booking: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating booking');
    });
}

// View booking details
function viewBookingDetails(bookingId) {
    fetch(`controller/BookingController.php?action=details&id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('bookingDetailsContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('viewDetailsModal')).show();
            } else {
                alert('Error loading booking details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading booking details');
        });
}

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
}


</script>

</body>
</html>