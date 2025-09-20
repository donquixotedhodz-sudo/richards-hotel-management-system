<?php
require_once '../centralized-login/SessionManager.php';
require_once '../config/database.php';

// Require admin authentication
SessionManager::requireAdmin();

$currentUser = SessionManager::getCurrentUser();
$page_title = 'Admin Dashboard';
$additional_css = ['css/dashboard.css'];

// Fetch admin statistics
// Get total rooms count
$stmt = $conn->prepare("SELECT COUNT(*) as total_rooms FROM rooms");
$stmt->execute();
$totalRooms = $stmt->fetch(PDO::FETCH_ASSOC)['total_rooms'] ?? 0;

// Get total bookings count
$stmt = $conn->prepare("SELECT COUNT(*) as total_bookings FROM bookings");
$stmt->execute();
$totalBookings = $stmt->fetch(PDO::FETCH_ASSOC)['total_bookings'] ?? 0;

// Get total customers count
$stmt = $conn->prepare("SELECT COUNT(*) as total_customers FROM users");
$stmt->execute();
$totalCustomers = $stmt->fetch(PDO::FETCH_ASSOC)['total_customers'] ?? 0;

// Get pending bookings count
$stmt = $conn->prepare("SELECT COUNT(*) as pending_bookings FROM bookings WHERE booking_status = 'pending'");
$stmt->execute();
$pendingBookings = $stmt->fetch(PDO::FETCH_ASSOC)['pending_bookings'] ?? 0;

// Get total revenue
$stmt = $conn->prepare("SELECT SUM(total_price) as total_revenue FROM bookings WHERE payment_status = 'paid'");
$stmt->execute();
$totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

// Get customer data for charts
// Weekly data (last 4 weeks)
$stmt = $conn->prepare("
    SELECT 
        WEEK(created_at) as week_num,
        COUNT(*) as customer_count
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 4 WEEK)
    GROUP BY WEEK(created_at)
    ORDER BY week_num
");
$stmt->execute();
$weeklyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly data (all months)
$stmt = $conn->prepare("
    SELECT 
        MONTH(created_at) as month_num,
        MONTHNAME(created_at) as month_name,
        COUNT(*) as customer_count
    FROM users 
    GROUP BY MONTH(created_at), YEAR(created_at)
    ORDER BY created_at
");
$stmt->execute();
$monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Yearly data (last 3 years)
$stmt = $conn->prepare("
    SELECT 
        YEAR(created_at) as year_num,
        COUNT(*) as customer_count
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 YEAR)
    GROUP BY YEAR(created_at)
    ORDER BY year_num
");
$stmt->execute();
$yearlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for JavaScript
$weeklyLabels = [];
$weeklyCounts = [];
for ($i = 0; $i < 4; $i++) {
    $weeklyLabels[] = 'Week ' . ($i + 1);
    $weeklyCounts[] = 0;
}
foreach ($weeklyData as $data) {
    $weekIndex = count($weeklyLabels) - (4 - array_search($data, $weeklyData));
    if ($weekIndex >= 0 && $weekIndex < 4) {
        $weeklyCounts[$weekIndex] = (int)$data['customer_count'];
    }
}

// Prepare monthly data for all 12 months
$monthlyLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$monthlyCounts = array_fill(0, 12, 0);

// Fill in actual data
foreach ($monthlyData as $data) {
    $monthIndex = (int)$data['month_num'] - 1; // Convert to 0-based index
    if ($monthIndex >= 0 && $monthIndex < 12) {
        $monthlyCounts[$monthIndex] += (int)$data['customer_count'];
    }
}

$yearlyLabels = [];
$yearlyCounts = [];
foreach ($yearlyData as $data) {
    $yearlyLabels[] = $data['year_num'];
    $yearlyCounts[] = (int)$data['customer_count'];
}

?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>
    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Dashboard Section -->
        <div id="dashboard-section" class="content-section active">
            <div class="content-header">
                <h2 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h2>
                <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($currentUser['name']); ?>!</p>
            </div>
            
            <div class="content-body">
                <!-- Admin Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="card border-0 shadow-sm h-100" style="background-color: #007bff;">
                            <div class="card-body text-white text-center">
                                <div class="d-flex align-items-center justify-content-center mb-3">
                                    <div class="rounded-circle bg-white bg-opacity-25 p-3">
                                        <i class="fas fa-bed fa-2x"></i>
                                    </div>
                                </div>
                                <h2 class="fw-bold mb-1"><?php echo number_format($totalRooms); ?></h2>
                                <p class="mb-0 opacity-75">Total Rooms</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="card border-0 shadow-sm h-100" style="background-color: #ffc107;">
                            <div class="card-body text-white text-center">
                                <div class="d-flex align-items-center justify-content-center mb-3">
                                    <div class="rounded-circle bg-white bg-opacity-25 p-3">
                                        <i class="fas fa-calendar-check fa-2x"></i>
                                    </div>
                                </div>
                                <h2 class="fw-bold mb-1"><?php echo number_format($totalBookings); ?></h2>
                                <p class="mb-0 opacity-75">Total Bookings</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="card border-0 shadow-sm h-100" style="background-color: #28a745;">
                            <div class="card-body text-white text-center">
                                <div class="d-flex align-items-center justify-content-center mb-3">
                                    <div class="rounded-circle bg-white bg-opacity-25 p-3">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                                <h2 class="fw-bold mb-1"><?php echo number_format($totalCustomers); ?></h2>
                                <p class="mb-0 opacity-75">Total Customers</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="card border-0 shadow-sm h-100" style="background-color: #dc3545;">
                            <div class="card-body text-white text-center">
                                <div class="d-flex align-items-center justify-content-center mb-3">
                                    <div class="rounded-circle bg-white bg-opacity-25 p-3">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                                <h2 class="fw-bold mb-1"><?php echo number_format($pendingBookings); ?></h2>
                                <p class="mb-0 opacity-75">Pending Bookings</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Revenue Card -->
                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm" style="background-color: #007bff;">
                            <div class="card-body text-white">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3">
                                                <i class="fas fa-dollar-sign fa-lg"></i>
                                            </div>
                                            <h5 class="mb-0 fw-bold">Total Revenue</h5>
                                        </div>
                                        <h1 class="fw-bold mb-1">₱<?php echo number_format($totalRevenue, 2); ?></h1>
                                        <small class="opacity-75">From confirmed bookings</small>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="rounded-circle bg-white bg-opacity-25 p-4 d-inline-flex">
                                            <i class="fas fa-chart-line fa-3x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Customer Analytics Charts -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Customers by Week</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="weeklyChart" width="400" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Customers by Month</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyChart" width="400" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Customers by Year</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="yearlyChart" width="400" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }

        // Customer Analytics Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Get data from PHP
            const weeklyLabels = <?php echo json_encode($weeklyLabels); ?>;
            const weeklyCounts = <?php echo json_encode($weeklyCounts); ?>;
            const monthlyLabels = <?php echo json_encode($monthlyLabels); ?>;
            const monthlyCounts = <?php echo json_encode($monthlyCounts); ?>;
            const yearlyLabels = <?php echo json_encode($yearlyLabels); ?>;
            const yearlyCounts = <?php echo json_encode($yearlyCounts); ?>;

            // Weekly Chart
            const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
            const weeklyChart = new Chart(weeklyCtx, {
                type: 'line',
                data: {
                    labels: weeklyLabels,
                    datasets: [{
                        label: 'New Customers',
                        data: weeklyCounts,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Monthly Chart
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            const monthlyChart = new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: monthlyLabels,
                    datasets: [{
                        label: 'New Customers',
                        data: monthlyCounts,
                        backgroundColor: [
                            '#007bff',
                            '#ffc107',
                            '#28a745',
                            '#dc3545',
                            '#6f42c1',
                            '#fd7e14'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Yearly Chart
            const yearlyCtx = document.getElementById('yearlyChart').getContext('2d');
            const yearlyChart = new Chart(yearlyCtx, {
                type: 'doughnut',
                data: {
                    labels: yearlyLabels,
                    datasets: [{
                        data: yearlyCounts,
                        backgroundColor: [
                            '#007bff',
                            '#28a745',
                            '#ffc107'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        });
    </script>
</body>
</html>