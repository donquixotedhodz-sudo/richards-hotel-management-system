<?php
require_once '../centralized-login/SessionManager.php';
require_once '../config/database.php';

// Require admin authentication
SessionManager::requireAdmin();

$currentUser = SessionManager::getCurrentUser();
$page_title = 'Reports & Analysis';
$additional_css = ['css/dashboard.css'];
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="content-section active">
        <div class="content-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0"><i class="fas fa-chart-line me-2"></i>Reports & Analysis</h2>
                    <p class="text-muted mb-0">Key metrics and trends across bookings and rooms</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary btn-sm btn-rect d-inline-flex align-items-center no-print" onclick="window.print()" title="Print this report">
                        <i class="fas fa-print me-2"></i>
                        <span>Print</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="content-body">
            <!-- Summary Cards -->
            <div class="row g-3 mb-4" id="summaryCards">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="mb-1">Total Bookings</h6>
                                    <h3 class="mb-0" id="summaryTotalBookings">0</h3>
                                </div>
                                <i class="fas fa-calendar-check fa-2x"></i>
                            </div>
                            <small id="summaryPeriod" class="opacity-75">Last 30 days</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="mb-1">Total Revenue</h6>
                                    <h3 class="mb-0" id="summaryTotalRevenue">₱0.00</h3>
                                </div>
                                <i class="fas fa-peso-sign fa-2x"></i>
                            </div>
                            <small class="opacity-75">Last 30 days</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm bg-warning text-dark">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="mb-1">Avg. Duration (hrs)</h6>
                                    <h3 class="mb-0" id="summaryAvgDuration">0</h3>
                                </div>
                                <i class="fas fa-hourglass-half fa-2x text-dark"></i>
                            </div>
                            <small class="text-dark opacity-75">Last 30 days</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="mb-1">Occupancy Rate</h6>
                                    <h3 class="mb-0" id="summaryOccupancy">0%</h3>
                                </div>
                                <i class="fas fa-bed fa-2x"></i>
                            </div>
                            <small class="opacity-75" id="summaryRoomsLabel">Rooms: 0 occupied of 0</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 1 -->
            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Bookings Over Time</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="bookingsChart" height="120"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0"><i class="fas fa-chart-area me-2"></i>Revenue Over Time</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueChart" height="120"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 2 -->
            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0"><i class="fas fa-money-check-alt me-2"></i>Payment Status Breakdown</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="paymentChart" height="120"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Top Customers (by revenue)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="customersChart" height="120"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

<?php include 'includes/footer.php'; ?>

<style>
@media print {
    .sidebar, .no-print, .user-profile { display: none !important; }
    .main-content { padding: 0 !important; margin: 0 !important; }
    .content-section { box-shadow: none !important; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    loadSummary();
    renderBookingsChart();
    renderRevenueChart();
    renderPaymentChart();
    renderCustomersChart();
});

function loadSummary(days = 30) {
    fetch(`controller/ReportsController.php?action=get_summary&days=${days}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const s = data.summary;
            document.getElementById('summaryTotalBookings').textContent = s.total_bookings;
            document.getElementById('summaryTotalRevenue').textContent = `₱${Number(s.total_revenue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            document.getElementById('summaryAvgDuration').textContent = s.avg_duration_hours;
            document.getElementById('summaryOccupancy').textContent = `${s.occupancy_rate}%`;
            document.getElementById('summaryRoomsLabel').textContent = `Rooms: ${s.occupied_rooms} occupied of ${s.total_rooms}`;
            document.getElementById('summaryPeriod').textContent = `Last ${s.period_days} days`;
        })
        .catch(console.error);
}

function renderBookingsChart(days = 30) {
    fetch(`controller/ReportsController.php?action=bookings_over_time&days=${days}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const ctx = document.getElementById('bookingsChart');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Bookings',
                        data: data.values,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13,110,253,0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        })
        .catch(console.error);
}

function renderRevenueChart(days = 30) {
    fetch(`controller/ReportsController.php?action=revenue_over_time&days=${days}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const ctx = document.getElementById('revenueChart');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: data.values,
                        backgroundColor: 'rgba(25,135,84,0.4)',
                        borderColor: '#198754'
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        })
        .catch(console.error);
}

function renderPaymentChart(days = 30) {
    fetch(`controller/ReportsController.php?action=payment_breakdown&days=${days}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const labels = data.breakdown.map(b => (b.payment_status || 'unknown').replace('_',' '));
            const values = data.breakdown.map(b => Number(b.count));
            const ctx = document.getElementById('paymentChart');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: ['#198754','#ffc107','#dc3545','#6c757d']
                    }]
                },
                options: {
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        })
        .catch(console.error);
}

function renderCustomersChart(days = 90) {
    fetch(`controller/ReportsController.php?action=top_customers&days=${days}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const labels = data.customers.map(c => (c.customer_name || c.customer_email || 'Unknown'));
            const values = data.customers.map(c => Number(c.revenue));
            const ctx = document.getElementById('customersChart');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: values,
                        backgroundColor: 'rgba(13,110,253,0.3)',
                        borderColor: '#0d6efd'
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        })
        .catch(console.error);
}
</script>
</body>
</html>