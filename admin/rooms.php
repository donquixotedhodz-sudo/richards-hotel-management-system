<?php
require_once '../centralized-login/SessionManager.php';
require_once '../config/database.php';

// Require admin authentication
SessionManager::requireAdmin();

$currentUser = SessionManager::getCurrentUser();
$page_title = 'Room Management';
$additional_css = ['css/dashboard.css'];
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-bed me-2"></i><?php echo $page_title; ?></h1>
                    <p class="text-muted mb-0">Manage hotel rooms and room types</p>
                </div>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#roomModal" onclick="openAddRoomModal()">
                    <i class="fas fa-plus me-2"></i>Add New Room
                </button>
            </div>
        </div>
        
        <div class="content-body">

            <!-- Room Statistics -->
            <div class="row mb-4" id="roomStats">
                <!-- Stats will be loaded here -->
            </div>

            <!-- Rooms Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Rooms</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="roomsTable">
                            <thead>
                                <tr>
                                    <th>Room Number</th>
                                    <th>Room Type</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="roomsTableBody">
                                <!-- Rooms will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Room Modal -->
    <div class="modal fade" id="roomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roomModalTitle">Add New Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="roomForm">
                    <div class="modal-body">
                        <input type="hidden" id="roomId" name="room_id">
                        <div class="mb-3">
                            <label for="roomNumber" class="form-label">Room Number</label>
                            <input type="text" class="form-control" id="roomNumber" name="room_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="roomType" class="form-label">Room Type</label>
                            <select class="form-select" id="roomType" name="room_type_id" required>
                                <option value="">Select Room Type</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="roomStatus" class="form-label">Status</label>
                            <select class="form-select" id="roomStatus" name="status" required>
                                <option value="available">Available</option>
                                <option value="occupied">Occupied</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="out_of_order">Out of Order</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="saveRoomBtn">Save Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Room Details Modal -->
    <div class="modal fade" id="roomDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Room Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="roomDetailsContent">
                    <!-- Room details will be loaded here -->
                </div>
             </div>
         </div>
     </div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
        let currentRoomId = null;
        let roomTypes = [];

        // Load initial data
        document.addEventListener('DOMContentLoaded', function() {
            loadRoomStats();
            loadRoomTypes();
            loadRooms();
        });

        // Load room statistics
        function loadRoomStats() {
            fetch('controller/RoomController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_stats'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const stats = data.stats;
                    document.getElementById('roomStats').innerHTML = `
                        <div class="col-md-3">
                            <div class="card text-center bg-primary text-white">
                                <div class="card-body">
                                    <i class="fas fa-bed fa-2x text-white mb-2"></i>
                                    <h4>${stats.total_rooms}</h4>
                                    <p>Total Rooms</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center bg-success text-white">
                                <div class="card-body">
                                    <i class="fas fa-check-circle fa-2x text-white mb-2"></i>
                                    <h4>${stats.available_rooms}</h4>
                                    <p>Available</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center bg-danger text-white">
                                <div class="card-body">
                                    <i class="fas fa-user fa-2x text-white mb-2"></i>
                                    <h4>${stats.occupied_rooms}</h4>
                                    <p>Occupied</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center bg-warning text-dark">
                                <div class="card-body">
                                    <i class="fas fa-tools fa-2x text-dark mb-2"></i>
                                    <h4>${stats.maintenance_rooms + stats.out_of_order_rooms}</h4>
                                    <p>Maintenance</p>
                                </div>
                            </div>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading room stats:', error);
            });
        }

        // Load room types
        function loadRoomTypes() {
            fetch('controller/RoomController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_room_types'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    roomTypes = data.room_types;
                    const select = document.getElementById('roomType');
                    select.innerHTML = '<option value="">Select Room Type</option>';
                    roomTypes.forEach(type => {
                        select.innerHTML += `<option value="${type.id}">${type.type_name}</option>`;
                    });
                }
            })
            .catch(error => {
                console.error('Error loading room types:', error);
            });
        }

        // Load all rooms
        function loadRooms() {
            fetch('controller/RoomController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_all'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const tbody = document.getElementById('roomsTableBody');
                    tbody.innerHTML = '';
                    data.rooms.forEach(room => {
                        const statusClass = `status-${room.status.replace(' ', '_')}`;
                        const row = `
                            <tr>
                                <td><strong>${room.room_number}</strong></td>
                                <td>${room.type_name || 'N/A'}</td>
                                <td><span class="status-badge ${statusClass}">${room.status.charAt(0).toUpperCase() + room.status.slice(1).replace('_', ' ')}</span></td>
                                <td>${new Date(room.created_at).toLocaleDateString()}</td>
                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <button class="btn btn-primary btn-xs square-btn border-2" onclick="viewRoomDetails(${room.id})" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-warning btn-xs square-btn border-2" onclick="editRoom(${room.id})" title="Edit Room">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-xs square-btn border-2" onclick="deleteRoom(${room.id})" title="Delete Room">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });
                }
            })
            .catch(error => {
                console.error('Error loading rooms:', error);
                alert('Error loading rooms. Please try again.');
            });
        }

        // Open add room modal
        function openAddRoomModal() {
            currentRoomId = null;
            document.getElementById('roomModalTitle').textContent = 'Add New Room';
            document.getElementById('saveRoomBtn').textContent = 'Save Room';
            document.getElementById('roomForm').reset();
        }

        // Edit room
        function editRoom(roomId) {
            currentRoomId = roomId;
            document.getElementById('roomModalTitle').textContent = 'Edit Room';
            document.getElementById('saveRoomBtn').textContent = 'Update Room';
            
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', roomId);

            fetch('controller/RoomController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const room = data.room;
                    document.getElementById('roomId').value = room.id;
                    document.getElementById('roomNumber').value = room.room_number;
                    document.getElementById('roomType').value = room.room_type_id;
                    document.getElementById('roomStatus').value = room.status;
                    new bootstrap.Modal(document.getElementById('roomModal')).show();
                } else {
                    alert('Error loading room details: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error loading room details:', error);
                alert('Error loading room details. Please try again.');
            });
        }

        // View room details
        function viewRoomDetails(roomId) {
            const formData = new FormData();
            formData.append('action', 'details');
            formData.append('id', roomId);

            fetch('controller/RoomController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const room = data.room;
                    const statusClass = `status-${room.status.replace(' ', '_')}`;
                    document.getElementById('roomDetailsContent').innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-door-open me-2"></i>Room Information</h6>
                                <table class="table table-borderless">
                                    <tr><td><strong>Room Number:</strong></td><td>${room.room_number}</td></tr>
                                    <tr><td><strong>Room Type:</strong></td><td>${room.type_name || 'N/A'}</td></tr>
                                    <tr><td><strong>Status:</strong></td><td><span class="status-badge ${statusClass}">${room.status.charAt(0).toUpperCase() + room.status.slice(1).replace('_', ' ')}</span></td></tr>
                                    <tr><td><strong>Created:</strong></td><td>${new Date(room.created_at).toLocaleString()}</td></tr>
                                    <tr><td><strong>Updated:</strong></td><td>${new Date(room.updated_at).toLocaleString()}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle me-2"></i>Room Type Details</h6>
                                <p>${room.room_type_description || 'No description available'}</p>
                            </div>
                        </div>
                    `;
                    new bootstrap.Modal(document.getElementById('roomDetailsModal')).show();
                } else {
                    alert('Error loading room details: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error loading room details:', error);
                alert('Error loading room details. Please try again.');
            });
        }

        // Delete room
        function deleteRoom(roomId) {
            if (confirm('Are you sure you want to delete this room? This action cannot be undone.')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', roomId);

                fetch('controller/RoomController.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Room deleted successfully!');
                        loadRooms();
                        loadRoomStats();
                    } else {
                        alert('Error deleting room: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error deleting room:', error);
                    alert('Error deleting room. Please try again.');
                });
            }
        }

        // Handle room form submission
        document.getElementById('roomForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = currentRoomId ? 'update' : 'create';
            formData.append('action', action);
            if (currentRoomId) {
                formData.append('id', currentRoomId);
            }

            fetch('controller/RoomController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    bootstrap.Modal.getInstance(document.getElementById('roomModal')).hide();
                    loadRooms();
                    loadRoomStats();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error saving room:', error);
                alert('Error saving room. Please try again.');
            });
        });
    </script>
</body>
</html>