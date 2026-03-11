<?php
session_start();
include 'db_connect.php';

// Security: Only Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    die("Access Denied");
}

// Get the date from the URL (passed from dashboard)
$report_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// 1. Fetch Totals for the specific date
$stats_query = "SELECT 
    COUNT(b.booking_id) as total_bookings,
    SUM((EXTRACT(HOUR FROM (b.end_time - b.start_time))) * p.price) as total_revenue
    FROM tbl_booking b
    JOIN tbl_parking_listing p ON b.parking_id = p.parking_id
    WHERE b.booking_date = $1";

$stats_res = pg_query_params($conn, $stats_query, array($report_date));
$stats = pg_fetch_assoc($stats_res);

$total_bookings = $stats['total_bookings'] ?: 0;
$total_revenue = $stats['total_revenue'] ?: 0;

// 2. Fetch Detailed Booking History for that date
$query_history = "SELECT b.booking_id, b.start_time, b.end_time, b.plate_number, b.phone_number, 
                  p.slot_number, p.price as hourly_rate, u.full_name,
                  (EXTRACT(HOUR FROM (b.end_time - b.start_time))) as duration
                  FROM tbl_booking b 
                  JOIN tbl_user u ON b.user_id = u.user_id 
                  JOIN tbl_parking_listing p ON b.parking_id = p.parking_id 
                  WHERE b.booking_date = $1
                  ORDER BY b.start_time ASC";
$history_res = pg_query_params($conn, $query_history, array($report_date));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Parking Reservation System for The Summit Batu Pahat Report - <?php echo $report_date; ?></title>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; padding: 30px; color: #333; line-height: 1.6; }
        .report-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .report-header h1 { margin: 0; text-transform: uppercase; }
        
        .summary-grid { display: flex; justify-content: space-between; margin-bottom: 30px; background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .summary-item { text-align: center; flex: 1; }
        .summary-item span { display: block; font-size: 0.8em; color: #666; text-transform: uppercase; }
        .summary-item strong { font-size: 1.4em; color: #000; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em; }
        th, td { border: 1px solid #aaa; padding: 10px; text-align: left; }
        th { background-color: #eee; }
        
        .footer { margin-top: 50px; font-size: 0.8em; text-align: center; border-top: 1px solid #ccc; padding-top: 10px; }
        .btn-print { background: #333; color: white; padding: 10px 20px; border: none; cursor: pointer; font-weight: bold; text-decoration: none; }
        
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 20px;">
    <button onclick="window.print()" class="btn-print">Print to PDF</button>
    
    <a href="admin_dashboard.php" class="btn-print" style="background:#666; text-decoration: none; display: inline-block;">
        Back to Dashboard
    </a>
</div>

    <div class="report-header">
        <h1>Parking Transaction Report</h1>
        <p><strong>System:</strong> Parking Reservation System | <strong>Location:</strong> The Summit Batu Pahat</p>
        <p><strong>Report Date:</strong> <?php echo date('d F Y', strtotime($report_date)); ?></p>
    </div>

    <div class="summary-grid">
        <div class="summary-item">
            <span>Total Bookings</span>
            <strong><?php echo $total_bookings; ?></strong>
        </div>
        <div class="summary-item">
            <span>Total Revenue</span>
            <strong>RM <?php echo number_format($total_revenue, 2); ?></strong>
        </div>
        <div class="summary-item">
            <span>Generated At</span>
            <strong><?php echo date('H:i A'); ?></strong>
        </div>
    </div>

    <h3>Detailed Activity Log</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Driver Name</th>
                <th>Plate/Phone</th>
                <th>Slot</th>
                <th>Time (Start - End)</th>
                <th>Duration</th>
                <th>Fee</th>
            </tr>
        </thead>
        <tbody>
            <?php if (pg_num_rows($history_res) > 0): ?>
                <?php while ($row = pg_fetch_assoc($history_res)): ?>
                <?php 
                    $fee = $row['duration'] * $row['hourly_rate'];
                ?>
                <tr>
                    <td>#<?php echo $row['booking_id']; ?></td>
                    <td><?php echo $row['full_name']; ?></td>
                    <td><?php echo $row['plate_number']; ?><br><small><?php echo $row['phone_number']; ?></small></td>
                    <td><?php echo $row['slot_number']; ?></td>
                    <td><?php echo date("h:i A", strtotime($row['start_time'])); ?> - <?php echo date("h:i A", strtotime($row['end_time'])); ?></td>
                    <td><?php echo $row['duration']; ?> Hr</td>
                    <td>RM <?php echo number_format($fee, 2); ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align:center;">No data recorded for this date.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>This is a computer-generated report for Parking Reservation System.</p>
        <p>Printed on: <?php echo date('d-m-Y H:i:s'); ?></p>
    </div>

</body>
</html>