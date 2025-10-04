<?php
require_once '../centralized-login/SessionManager.php';
require_once '../config/database.php';

// Require admin authentication
SessionManager::requireAdmin();

// Fetch all bookings with details
$query = "
    SELECT 
        b.*, 
        rt.type_name,
        r.room_number
    FROM bookings b
    LEFT JOIN room_types rt ON b.room_type_id = rt.id
    LEFT JOIN rooms r ON b.room_id = r.id
    ORDER BY b.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary stats
$stats_query = "
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN payment_status = 'paid' THEN total_price ELSE 0 END) as total_revenue
    FROM bookings
";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$generated_at = date('M j, Y g:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings Detailed Report</title>
    <style>
        :root {
            --primary: #0d6efd;
            --text: #212529;
            --muted: #6c757d;
            --border: #dee2e6;
        }
        body { font-family: Arial, Helvetica, sans-serif; color: var(--text); margin: 0; }
        .container { max-width: 1024px; margin: 0 auto; padding: 16px; }
        .report-header { display: flex; align-items: center; gap: 12px; border-bottom: 2px solid var(--border); padding-bottom: 10px; margin-bottom: 16px; }
        .report-header .brand { font-size: 20px; font-weight: 700; }
        .report-header img { height: 36px; }
        .report-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .report-meta .left { font-size: 14px; color: var(--muted); }
        .actions { margin: 12px 0 20px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border: 1px solid var(--border); border-radius: 4px; text-decoration: none; cursor: pointer; font-size: 14px; }
        .btn-primary { background: var(--primary); color: #fff; border-color: var(--primary); }
        .btn-outline { background: #fff; color: var(--text); }
        .summary { display: flex; gap: 16px; margin-bottom: 12px; }
        .summary .card { border: 1px solid var(--border); border-radius: 6px; padding: 10px 12px; flex: 1; }
        .summary .label { color: var(--muted); font-size: 12px; }
        .summary .value { font-size: 18px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid var(--border); padding: 6px 8px; text-align: left; }
        th { background: #f8f9fa; }
        tr:nth-child(even) { background: #fcfcfd; }
        .text-muted { color: var(--muted); }
        .text-right { text-align: right; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 11px; border: 1px solid var(--border); }
        .badge.status { border-color: #adb5bd; }
        .badge.paid { background: #d1e7dd; border-color: #badbcc; }
        .badge.unpaid { background: #f8d7da; border-color: #f5c2c7; }
        .footer { margin-top: 16px; font-size: 11px; color: var(--muted); }

        @media print {
            @page { size: A4; margin: 12mm; }
            .actions { display: none; }
            .container { padding: 0; }
            .report-header { margin-top: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="report-header">
            <img src="../images/logo/logo.png" alt="Logo" />
            <div class="brand">Richards Hotel — Bookings Detailed Report</div>
        </div>
        <div class="report-meta">
            <div class="left">Generated: <?php echo htmlspecialchars($generated_at); ?></div>
            <div class="left">Report covers: All bookings</div>
        </div>

        <div class="actions no-print">
            <button class="btn btn-primary" onclick="window.print()">Print</button>
            <a class="btn btn-outline" href="bookings.php">Back to Bookings</a>
        </div>

        <div class="summary">
            <div class="card">
                <div class="label">Total Bookings</div>
                <div class="value"><?php echo number_format((int)$stats['total_bookings']); ?></div>
            </div>
            <div class="card">
                <div class="label">Total Revenue (paid)</div>
                <div class="value">₱<?php echo number_format((float)$stats['total_revenue'], 2); ?></div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 160px;">Customer</th>
                    <th style="width: 180px;">Email</th>
                    <th style="width: 120px;">Room Type</th>
                    <th style="width: 80px;">Room</th>
                    <th style="width: 120px;">Check-in</th>
                    <th style="width: 120px;">Check-out</th>
                    <th style="width: 80px;">Duration (hrs)</th>
                    <th style="width: 100px;" class="text-right">Total (₱)</th>
                    <th style="width: 100px;">Status</th>
                    <th style="width: 90px;">Payment</th>
                    <th style="width: 120px;">Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><strong>#<?php echo (int)$b['id']; ?></strong></td>
                    <td><?php echo htmlspecialchars($b['customer_name'] ?? ''); ?></td>
                    <td class="text-muted"><?php echo htmlspecialchars($b['customer_email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($b['type_name'] ?? ''); ?></td>
                    <td><?php echo $b['room_number'] ? htmlspecialchars($b['room_number']) : '<span class="text-muted">N/A</span>'; ?></td>
                    <td>
                        <?php echo date('M j, Y g:i A', strtotime($b['check_in_datetime'])); ?>
                    </td>
                    <td>
                        <?php echo date('M j, Y g:i A', strtotime($b['check_out_datetime'])); ?>
                    </td>
                    <td><?php echo (int)$b['duration_hours']; ?></td>
                    <td class="text-right"><?php echo number_format((float)$b['total_price'], 2); ?></td>
                    <td>
                        <span class="badge status"><?php echo htmlspecialchars($b['booking_status']); ?></span>
                    </td>
                    <td>
                        <?php if (($b['payment_status'] ?? '') === 'paid'): ?>
                            <span class="badge paid">Paid</span>
                        <?php else: ?>
                            <span class="badge unpaid">Unpaid</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($b['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="footer">
            Printed via Richards Hotel Management System — <?php echo htmlspecialchars($generated_at); ?>
        </div>
    </div>
</body>
</html>