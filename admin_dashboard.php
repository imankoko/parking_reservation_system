<?php
session_start();
include 'db_connect.php';

// Security check: only Admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Get selected date from GET, default to today if not set
$selected_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');

// 1. Fetch Stats
$user_count = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM tbl_user"), 0, 0);
$available_slots = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM tbl_parking_listing WHERE status = 'Available'"), 0, 0);
$total_slots = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM tbl_parking_listing"), 0, 0);

// 2. Revenue Calculation (Filtered by Date)
$revenue_query = "SELECT SUM((EXTRACT(HOUR FROM (b.end_time - b.start_time))) * p.price) as total_rev 
                  FROM tbl_booking b 
                  JOIN tbl_parking_listing p ON b.parking_id = p.parking_id
                  WHERE b.booking_date = $1";
$revenue_res = pg_query_params($conn, $revenue_query, array($selected_date));
$total_revenue = pg_fetch_result($revenue_res, 0, 0) ?: 0;

// 3. Activity Query (Crucial for the table below)
$query_recent = "SELECT b.booking_id, b.booking_date, u.full_name, p.slot_number, p.price as hourly_rate, 
                  b.plate_number, b.phone_number, b.start_time, b.end_time,
                  (EXTRACT(HOUR FROM (b.end_time - b.start_time))) as duration
                  FROM tbl_booking b 
                  JOIN tbl_user u ON b.user_id = u.user_id 
                  JOIN tbl_parking_listing p ON b.parking_id = p.parking_id 
                  WHERE b.booking_date = $1
                  ORDER BY b.start_time DESC";

// Execute query and store result in $recent_bookings
$recent_bookings = pg_query_params($conn, $query_recent, array($selected_date));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #ffffff; margin: 0; padding-bottom: 80px; }
        
        /* Faded Yellow Header */
        .header {
            background: linear-gradient(to bottom, #d4bc44, #ffffff);
            padding: 30px 20px; display: flex; align-items: center; gap: 15px;
        }
        .logo-img { width: 60px; height: auto; }
        .header-text h2 { margin: 0; font-size: 1.4rem; color: #000; }

        .main-content { padding: 20px; }
        .filter-container { background: #f9f9f9; padding: 15px; margin-bottom: 20px; border-radius: 12px; }
        
        /* 2x2 Stats Grid for Mobile */
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 25px; }
        
        @media (min-width: 768px) {
            .stats-grid { grid-template-columns: repeat(4, 1fr); gap: 20px; }
        }

        .stat-card { 
            background: #fff; padding: 20px 10px; text-align: center; 
            border-bottom: 4px solid #d4bc44; box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
            border-radius: 12px; 
        }
        .stat-card p { margin: 0; font-size: 0.8em; font-weight: bold; color: #666; text-transform: uppercase; }
        .stat-card h3 { margin: 5px 0 0 0; font-size: 1.5em; color: #000; }

        /* Table Design */
        .table-container { background: white; padding: 15px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
        th { background: #f8f9fa; color: #888; text-transform: uppercase; font-size: 11px; }
        
        /* Bottom Navigation Bar */
        .bottom-nav {
            position: fixed; bottom: 0; width: 100%; background: #fff;
            display: flex; justify-content: space-around; padding: 10px 0;
            border-top: 1px solid #eee; box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }
        .nav-item { text-align: center; text-decoration: none; color: #888; font-size: 0.8rem; flex: 1; }
        .nav-item i { font-size: 1.5rem; display: block; margin-bottom: 2px; }
        .nav-item.active { color: #000; }
    </style>
</head>
<body>

    <div class="header">
        <img src="logo.png" class="logo-img" alt="Logo">
        <div class="header-text"><h2>Administrator<br>Dashboard</h2></div>
    </div>

    <div class="main-content">
        <div class="filter-container">
            <form method="GET" style="display: flex; gap: 5px; align-items: center;">
                <input type="date" name="filter_date" value="<?php echo $selected_date; ?>" style="padding: 8px; border-radius: 5px; border: 1px solid #ddd; flex: 1;">
                <button type="submit" style="background:#d4bc44; border:none; padding: 10px; border-radius: 5px; font-weight:bold; cursor:pointer;">Filter</button>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><p>Total Slots</p><h3><?php echo $total_slots; ?></h3></div>
            <div class="stat-card"><p>Available</p><h3><?php echo $available_slots; ?></h3></div>
            <div class="stat-card"><p>Total Users</p><h3><?php echo $user_count; ?></h3></div>
            <div class="stat-card"><p>Revenue</p><h3 style="color:#27ae60;">RM<?php echo number_format($total_revenue, 0); ?></h3></div>
        </div>

        <div class="table-container">
            <h3 style="margin: 0 0 10px 0; font-size: 1rem;">Activity Log: <?php echo date('d M Y', strtotime($selected_date)); ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Driver</th>
                        <th>Slot</th>
                        <th>Time</th>
                        <th>Duration</th>
                        <th>Fee</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_bookings && pg_num_rows($recent_bookings) > 0): ?>
                        <?php while ($row = pg_fetch_assoc($recent_bookings)): ?>
                        <tr>
                            <td>#<?php echo $row['booking_id']; ?></td>
                            <td><strong><?php echo $row['full_name']; ?></strong><br><small><?php echo $row['plate_number']; ?></small></td>
                            <td><?php echo $row['slot_number']; ?></td>
                            <td><?php echo date("h:i A", strtotime($row['start_time'])); ?></td>
                            <td><?php echo $row['duration']; ?> Hr</td>
                            <td style="color:#27ae60; font-weight:bold;">RM<?php echo number_format($row['duration'] * $row['hourly_rate'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding: 20px; color: #888;">No records found for this date.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="generate_report.php?date=<?php echo $selected_date; ?>" target="_blank" style="display:inline-block; background:#333; color:white; padding:15px 30px; text-decoration:none; border-radius:10px; font-weight:bold;">📊 Generate Report</a>
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="admin_dashboard.php" class="nav-item active"><i class="fa-solid fa-chart-pie"></i> Report</a>
        <a href="admin_reviews.php" class="nav-item"><i class="fa-solid fa-star"></i> Reviews</a>
        <a href="logout.php" class="nav-item" style="color:#dc3545;" onclick="return confirm('Logout?')">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </nav>

</body>
</html>