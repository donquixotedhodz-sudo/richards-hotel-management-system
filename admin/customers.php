<?php
require_once '../centralized-login/SessionManager.php';
require_once '../config/database.php';
require_once 'controller/CustomerController.php';

// Require admin authentication
SessionManager::requireAdmin();

$currentUser = SessionManager::getCurrentUser();
$page_title = 'Customer Management';
$additional_css = ['css/dashboard.css'];

$customerController = new CustomerController();
$customers = $customerController->getAllCustomers();

?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="content-section active">
        <div class="content-header">
            <h2 class="mb-0"><i class="fas fa-users me-2"></i>Customer Management</h2>
            <p class="text-muted mb-0">Manage all hotel customers and their information</p>
        </div>
        
        <div class="content-body">
            <!-- Alert Messages -->
            <div id="alertContainer"></div>
            
            <!-- Customer Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Customers</h5>
                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#customerModal" onclick="openCreateModal()">
                            <i class="fas fa-plus me-2"></i>Add New Customer
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="customersTable">
                            <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($customers && count($customers) > 0): ?>
                                            <?php foreach ($customers as $customer): ?>
                                                <tr id="customer-<?php echo $customer['id']; ?>">
                                                    <td><?php echo htmlspecialchars($customer['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($customer['first_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($customer['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editCustomer(<?php echo $customer['id']; ?>)" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCustomer(<?php echo $customer['id']; ?>)" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted">No customers found. Add your first customer!</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customerModalLabel">Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="customerForm">
                <div class="modal-body">
                    <input type="hidden" id="customerId" name="id">
                    <input type="hidden" id="formAction" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="firstName" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="firstName" name="first_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="lastName" class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="lastName" name="last_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone *</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                    
                    <div class="mb-3" id="passwordField">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Minimum 6 characters required</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="submitBtn">Add Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this customer? This action cannot be undone.</p>
                <p class="text-muted" id="deleteCustomerInfo"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Customer</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentCustomerId = null;

// Open create modal
function openCreateModal() {
    document.getElementById('customerModalLabel').textContent = 'Add New Customer';
    const submitBtn = document.querySelector('#customerModal .btn-danger');
    if (submitBtn) submitBtn.textContent = 'Add Customer';
    document.getElementById('formAction').value = 'create';
    document.getElementById('customerId').value = '';
    document.getElementById('customerForm').reset();
    document.getElementById('passwordField').style.display = 'block';
    document.getElementById('password').required = true;
}

// Edit customer
function editCustomer(id) {
    currentCustomerId = id;
    
    // Fetch customer data
    fetch('controller/CustomerController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get&id=' + id
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data && data.id) {
            document.getElementById('customerModalLabel').textContent = 'Edit Customer';
            const submitBtn = document.querySelector('#customerModal .btn-danger');
            if (submitBtn) submitBtn.textContent = 'Update Customer';
            document.getElementById('formAction').value = 'update';
            document.getElementById('customerId').value = data.id;
            document.getElementById('firstName').value = data.first_name || '';
            document.getElementById('lastName').value = data.last_name || '';
            document.getElementById('email').value = data.email || '';
            document.getElementById('phone').value = data.phone || '';
            document.getElementById('passwordField').style.display = 'none';
            document.getElementById('password').required = false;
            
            new bootstrap.Modal(document.getElementById('customerModal')).show();
        } else {
            showAlert('Customer data not found or invalid response', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error fetching customer data: ' + error.message, 'danger');
    });
}

// Delete customer
function deleteCustomer(id) {
    currentCustomerId = id;
    
    // Get customer info for confirmation
    const row = document.getElementById('customer-' + id);
    const firstName = row.cells[1].textContent;
    const lastName = row.cells[2].textContent;
    const email = row.cells[3].textContent;
    
    document.getElementById('deleteCustomerInfo').textContent = `${firstName} ${lastName} (${email})`;
    
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Confirm delete
document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (currentCustomerId) {
        fetch('controller/CustomerController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=delete&id=' + currentCustomerId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('customer-' + currentCustomerId).remove();
                showAlert(data.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            showAlert('Error deleting customer', 'danger');
        });
    }
});

// Handle form submission
document.getElementById('customerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('controller/CustomerController.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('customerModal')).hide();
            // Reload page to show updated data
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        showAlert('Error processing request', 'danger');
    });
});

// Show alert messages
function showAlert(message, type) {
    const alertContainer = document.getElementById('alertContainer');
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    alertContainer.appendChild(alertDiv);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Toggle sidebar for mobile
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('show');
}
</script>

<?php include 'includes/footer.php'; ?>