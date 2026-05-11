<?php
session_start();
include 'db_connect.php';

// Security: Only Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    die("Access Denied");
}

// FIX: Get BOTH start and end dates from the URL
$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d');
$end = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

// 1. Fetch Totals for the RANGE (Using BETWEEN)
$stats_query = "SELECT 
    COUNT(b.booking_id) as total_bookings,
    SUM((EXTRACT(HOUR FROM (b.end_time - b.start_time))) * p.price) as total_revenue
    FROM tbl_booking b
    JOIN tbl_parking_listing p ON b.parking_id = p.parking_id
    WHERE b.booking_date BETWEEN $1 AND $2";

$stats_res = pg_query_params($conn, $stats_query, array($start, $end));
$stats = pg_fetch_assoc($stats_res);

$total_bookings = $stats['total_bookings'] ?: 0;
$total_revenue = $stats['total_revenue'] ?: 0;

// 2. Fetch Detailed Booking History for the RANGE
$query_history = "SELECT b.booking_id, b.booking_date, b.start_time, b.end_time, b.plate_number, b.phone_number, 
                  p.slot_number, p.price as hourly_rate, u.full_name,
                  (EXTRACT(HOUR FROM (b.end_time - b.start_time))) as duration
                  FROM tbl_booking b 
                  JOIN tbl_user u ON b.user_id = u.user_id 
                  JOIN tbl_parking_listing p ON b.parking_id = p.parking_id 
                  WHERE b.booking_date BETWEEN $1 AND $2
                  ORDER BY b.booking_date ASC, b.start_time ASC";
$history_res = pg_query_params($conn, $query_history, array($start, $end));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Report: <?php echo $start; ?> to <?php echo $end; ?></title>
    <style>
        /* Your existing CSS stays the same... */
        body { font-family: 'Helvetica', Arial, sans-serif; padding: 30px; color: #333; line-height: 1.6; }
        .report-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .summary-grid { display: flex; justify-content: space-between; margin-bottom: 30px; background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .summary-item { text-align: center; flex: 1; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em; }
        th, td { border: 1px solid #aaa; padding: 10px; text-align: left; }
        th { background-color: #eee; }
        .no-print { margin-bottom: 20px; }
        .btn-print { background: #333; color: white; padding: 10px 20px; border: none; cursor: pointer; font-weight: bold; text-decoration: none; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" class="btn-print">Print to PDF</button>
        <a href="admin_dashboard.php" class="btn-print" style="background:#666;">Back to Dashboard</a>
    </div>

    <div class="report-header">
        <h1>Parking Transaction Report</h1>
        <p><strong>Location:</strong> The Summit Batu Pahat</p>
        <p><strong>Period:</strong> 
            <?php 
                if($start == $end) {
                    echo date('d F Y', strtotime($start));
                } else {
                    echo date('d M Y', strtotime($start)) . " - " . date('d M Y', strtotime($end));
                }
            ?>
        </p>
    </div>

    <div class="summary-grid">
        <div class="summary-item">
            <span>Total Bookings</span><br>
            <strong><?php echo $total_bookings; ?></strong>
        </div>
        <div class="summary-item">
            <span>Total Revenue</span><br>
            <strong>RM <?php echo number_format($total_revenue, 2); ?></strong>
        </div>
        <div class="summary-item">
            <span>Generated At</span><br>
            <strong><?php echo date('d/m/Y H:i A'); ?></strong>
        </div>
    </div>

    <h3>Detailed Activity Log</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>ID</th>
                <th>Driver Name</th>
                <th>Slot</th>
                <th>Time</th>
                <th>Duration</th>
                <th>Fee</th>
            </tr>
        </thead>
        <tbody>
            <?php if (pg_num_rows($history_res) > 0): ?>
                <?php while ($row = pg_fetch_assoc($history_res)): ?>
                <tr>
                    <td><?php echo date("d/m/y", strtotime($row['booking_date'])); ?></td>
                    <td>#<?php echo $row['booking_id']; ?></td>
                    <td><?php echo $row['full_name']; ?><br><small><?php echo $row['plate_number']; ?></small></td>
                    <td><?php echo $row['slot_number']; ?></td>
                    <td><?php echo date("h:i A", strtotime($row['start_time'])); ?> - <?php echo date("h:i A", strtotime($row['end_time'])); ?></td>
                    <td><?php echo $row['duration']; ?> Hr</td>
                    <td>RM <?php echo number_format($row['duration'] * $row['hourly_rate'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align:center;">No data recorded for this period.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>This is a computer-generated report for MySpot Parking System.</p>
    </div>
</body>
</html>