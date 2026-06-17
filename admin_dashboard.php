<?php
session_start();
include 'db_connect.php';

// Security check: only Admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Enforce strict Malaysian Time localization to lock down midnight date slips
date_default_timezone_set('Asia/Kuala_Lumpur');
pg_query($conn, "SET TIME ZONE 'Asia/Kuala_Lumpur'");

// --- DATE RANGE LOGIC ---
$period = isset($_GET['period']) ? $_GET['period'] : 'today';

if ($period == 'today') {
    // SMART MIDNIGHT BRIDGE: If checking between 12 AM and 6 AM, include yesterday in the 'Today' view
    $current_hour = (int)date('G');
    if ($current_hour >= 0 && $current_hour < 6) {
        $start_date = date('Y-m-d', strtotime('-1 day')); 
    } else {
        $start_date = date('Y-m-d');
    }
    $end_date = date('Y-m-d'); 
} elseif ($period == 'mtd') {
    $start_date = date('Y-m-01'); 
    $end_date = date('Y-m-d');
} elseif ($period == 'last30') {
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
} else {
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
}

// 1. Fetch Global Stats
$user_count = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM tbl_user"), 0, 0);
$available_slots = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM tbl_parking_listing WHERE status = 'Available'"), 0, 0);
$total_slots = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM tbl_parking_listing"), 0, 0);

// 2. Revenue Calculation (Range) - FIXED: Now includes 'Cancelled' since they paid before canceling
$revenue_query = "SELECT SUM(GREATEST(1, EXTRACT(HOUR FROM ((b.booking_date + b.end_time) - (b.booking_date + b.start_time)))) * p.price) as total_rev 
                  FROM tbl_booking b 
                  JOIN tbl_parking_listing p ON b.parking_id = p.parking_id
                  WHERE b.booking_date::date BETWEEN $1 AND $2
                  AND b.booking_status IN ('Confirmed', 'Occupied', 'Completed', 'Cancelled')";
$revenue_res = pg_query_params($conn, $revenue_query, array($start_date, $end_date));
$total_revenue = pg_fetch_result($revenue_res, 0, 0) ?: 0;

// 3. Activity Query (Range) - FIXED: Included 'Cancelled' status in the lookup filter
$query_recent = "SELECT b.booking_id, b.booking_date, u.full_name, p.slot_number, p.price as hourly_rate, 
                  b.plate_number, b.phone_number, b.start_time, b.end_time, b.booking_status,
                  GREATEST(1, EXTRACT(HOUR FROM ((b.booking_date + b.end_time) - (b.booking_date + b.start_time)))) as duration
                  FROM tbl_booking b 
                  JOIN tbl_user u ON b.user_id = u.user_id 
                  JOIN tbl_parking_listing p ON b.parking_id = p.parking_id 
                  WHERE b.booking_date::date BETWEEN $1 AND $2
                  AND b.booking_status IN ('Confirmed', 'Occupied', 'Completed', 'Cancelled')
                  ORDER BY b.booking_id DESC, b.booking_date DESC, b.start_time DESC";

$recent_bookings = pg_query_params($conn, $query_recent, array($start_date, $end_date));

// 4. --- LIVE DYNAMIC NOTIFICATION DISCOVERY ENGINE ---
$blocked_query = "SELECT slot_number, branch, location, status FROM tbl_parking_listing 
                  WHERE TRIM(BOTH FROM UPPER(status)) IN ('BLOCKED', 'MAINTENANCE', 'INACTIVE') 
                  ORDER BY branch ASC, slot_number ASC";
$blocked_res = pg_query($conn, $blocked_query);
$notification_count = pg_num_rows($blocked_res);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #ffffff; margin: 0; padding-bottom: 150px; } 
        
        .header {
            background: linear-gradient(to bottom, #d4bc44, #ffffff);
            padding: 30px 20px; display: flex; align-items: center; gap: 15px; position: relative;
        }
        .logo-img { width: 60px; height: auto; }
        .header-text h2 { margin: 0; font-size: 1.4rem; color: #000; }

        /* NOTIFICATION BAR COMPONENT DESIGNS */
        .notification-container {
            position: absolute; right: 25px; top: 35px; cursor: pointer; z-index: 1010;
        }
        .bell-icon { font-size: 1.5rem; color: #000; position: relative; }
        .badge-counter {
            position: absolute; top: -7px; right: -8px; background: #dc3545; color: white;
            font-size: 11px; font-weight: bold; padding: 2px 6px; border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .notification-dropdown {
            display: none; position: absolute; right: 0; top: 35px; width: 300px;
            background: #ffffff; border: 1px solid #d4bc44; border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15); z-index: 1025; overflow: hidden;
        }
        .notification-dropdown.show { display: block; }
        .noti-header { background: #f8f9fa; padding: 12px; font-size: 13px; font-weight: bold; border-bottom: 1px solid #eee; color: #333; }
        .noti-body { max-height: 250px; overflow-y: auto; }
        .noti-item {
            padding: 12px; border-bottom: 1px solid #f5f5f5; font-size: 12px; display: flex; gap: 10px; align-items: start; transition: background 0.2s;
        }
        .noti-item:hover { background: #fffdf3; }
        .noti-item i { margin-top: 2px; }

        .main-content { padding: 20px; }
        
        .filter-container { background: #f9f9f9; padding: 15px; margin-bottom: 20px; border-radius: 12px; border: 1px solid #d4bc44; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 25px; }
        @media (min-width: 768px) { .stats-grid { grid-template-columns: repeat(4, 1fr); gap: 20px; } }

        .stat-card { 
            background: #fff; padding: 20px 10px; text-align: center; 
            border-bottom: 4px solid #d4bc44; box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
            border-radius: 12px; 
        }
        .stat-card p { margin: 0; font-size: 0.8em; font-weight: bold; color: #666; text-transform: uppercase; }
        .stat-card h3 { margin: 5px 0 0 0; font-size: 1.5em; color: #000; }

        .table-container { background: white; padding: 15px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow-x: auto; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
        th { background: #f8f9fa; color: #888; text-transform: uppercase; font-size: 11px; }
        
        /* Interactive Status Tag Styles */
        .status-badge { padding: 3px 8px; border-radius: 6px; font-size: 10px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .badge-confirmed { background: #fef3c7; color: #d97706; }
        .badge-occupied { background: #dbeafe; color: #2563eb; }
        .badge-completed { background: #d1fae5; color: #059669; }
        .badge-cancelled { background: #fee2e2; color: #dc3545; }

        /* BOTTOM ACTION BAR (STICKY) */
        .sticky-actions {
            position: fixed;
            bottom: 65px; 
            left: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            padding: 12px 0;
            display: flex;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 -5px 15px rgba(0,0,0,0.08);
            z-index: 999;
            backdrop-filter: blur(8px);
        }

        .bottom-nav {
            position: fixed; bottom: 0; left: 0; width: 100%; background: #fff;
            display: flex; justify-content: space-around; padding: 10px 0;
            border-top: 1px solid #eee; box-shadow: 0 -2px 10px rgba(0,0,0,0.05); z-index: 1000;
        }
        .nav-item { text-align: center; text-decoration: none; color: #888; font-size: 0.8rem; flex: 1; }
        .nav-item i { font-size: 1.5rem; display: block; margin-bottom: 2px; }
        .nav-item.active { color: #000; }
    </style>
</head>
<body>

    <div class="header">
        <img src="logo.png" class="logo-img" alt="Logo">
        <div class="header-text"><h2>Administrator Dashboard</h2></div>

        <div class="notification-container" id="notiBellWrapper">
            <div class="bell-icon">
                <i class="fa-solid fa-bell"></i>
                <?php if ($notification_count > 0): ?>
                    <span class="badge-counter"><?php echo $notification_count; ?></span>
                <?php endif; ?>
            </div>
            <div class="notification-dropdown" id="notiDropdownTray">
                <div class="noti-header">System Alerts (<?php echo $notification_count; ?>)</div>
                <div class="noti-body">
                    <?php if ($notification_count > 0): ?>
                        <?php while ($unit = pg_fetch_assoc($blocked_res)): ?>
                            <div class="noti-item">
                                <i class="fa-solid fa-triangle-exclamation" style="color: #dc3545; font-size: 1.1rem;"></i>
                                <div>
                                    <span style="font-weight: bold; color: #c0392b;">Slot <?php echo htmlspecialchars($unit['slot_number']); ?> Blocked!</span><br>
                                    <small style="color: #666; font-size: 11px;">
                                        Branch: <?php echo htmlspecialchars($unit['branch']); ?> (<?php echo htmlspecialchars($unit['location']); ?>)<br>
                                        Status: <span style="text-transform: lowercase; font-weight: bold; color: #333;"><?php echo htmlspecialchars($unit['status']); ?></span>
                                    </small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="padding: 25px; text-align: center; color: #888; font-size: 12px;">
                            <i class="fa-solid fa-circle-check" style="color: #2ecc71; font-size: 1.8rem; display: block; margin-bottom: 8px;"></i>
                            All physical parking slots are working healthy!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="filter-container">
            <form method="GET" action="admin_dashboard.php">
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <select name="period" id="periodSelect" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                        <option value="today" <?php if($period == 'today') echo 'selected'; ?>>Today Only</option>
                        <option value="mtd" <?php if($period == 'mtd') echo 'selected'; ?>>Month to Date</option>
                        <option value="last30" <?php if($period == 'last30') echo 'selected'; ?>>Last 30 Days</option>
                        <option value="custom" <?php if($period == 'custom') echo 'selected'; ?>>Custom Range</option>
                    </select>

                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="date" name="start_date" id="startDate" value="<?php echo $start_date; ?>" style="flex: 1; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                        <input type="date" name="end_date" id="endDate" value="<?php echo $end_date; ?>" style="flex: 1; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                        <button type="submit" style="background:#d4bc44; border:none; padding: 10px 15px; border-radius: 8px; font-weight:bold; cursor:pointer;">APPLY</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><p>Total Slots</p><h3><?php echo $total_slots; ?></h3></div>
            <div class="stat-card"><p>Available</p><h3><?php echo $available_slots; ?></h3></div>
            <div class="stat-card"><p>Total Users</p><h3><?php echo $user_count; ?></h3></div>
            <div class="stat-card"><p>Revenue</p><h3 style="color:#27ae60;">RM<?php echo number_format($total_revenue, 2); ?></h3></div>
        </div>

        <div class="table-container">
            <h3 style="margin: 0 0 10px 0; font-size: 1rem;">
                Activity Log: 
                <?php echo (date('Y-m-d', strtotime($start_date)) == date('Y-m-d', strtotime($end_date))) ? date('d M Y', strtotime($start_date)) : date('d M', strtotime($start_date)) . " - " . date('d M Y', strtotime($end_date)); ?>
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Driver</th><th>Slot</th><th>Time</th><th>Duration</th><th>Status</th><th>Fee</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_bookings && pg_num_rows($recent_bookings) > 0): ?>
                        <?php while ($row = pg_fetch_assoc($recent_bookings)): ?>
                        <tr>
                            <td>#<?php echo $row['booking_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br><small><?php echo htmlspecialchars($row['plate_number']); ?></small></td>
                            <td><?php echo htmlspecialchars($row['slot_number']); ?></td>
                            <td><?php echo date("h:i A", strtotime($row['start_time'])); ?></td>
                            <td><?php echo htmlspecialchars($row['duration']); ?> Hr</td>
                            <td>
                                <?php 
                                $status = $row['booking_status'];
                                if ($status === 'Confirmed') echo '<span class="status-badge badge-confirmed">Confirmed</span>';
                                elseif ($status === 'Occupied') echo '<span class="status-badge badge-occupied">Occupied</span>';
                                elseif ($status === 'Cancelled') echo '<span class="status-badge badge-cancelled">Cancelled</span>';
                                else echo '<span class="status-badge badge-completed">Completed</span>';
                                ?>
                            </td>
                            <td style="color:#27ae60; font-weight:bold;">RM<?php echo number_format($row['duration'] * $row['hourly_rate'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding: 20px; color: #888;">No records found. Click APPLY to refresh.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="sticky-actions">
        <a href="admin_manage_slots.php" style="background:#d4bc44; color:black; padding:12px 20px; text-decoration:none; border-radius:10px; font-weight:bold; font-size: 13px;">
            <i class="fa-solid fa-gear"></i> Manage Slots
        </a>
        <a href="generate_report.php?start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>" target="_blank" style="background:#333; color:white; padding:12px 20px; text-decoration:none; border-radius:10px; font-weight:bold; font-size: 13px;">
            <i class="fa-solid fa-file-pdf"></i> Report
        </a>
    </div>

    <nav class="bottom-nav">
        <a href="admin_dashboard.php" class="nav-item active"><i class="fa-solid fa-chart-pie"></i> Report</a>
        <a href="admin_reviews.php" class="nav-item"><i class="fa-solid fa-star"></i> Reviews</a>
        <a href="logout.php" class="nav-item" style="color:#dc3545;" onclick="return confirm('Logout?')">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </nav>

    <script>
    // 🎛️ PERIOD SELECTION CALENDAR OVERLAY LOCKS
    document.getElementById('periodSelect').addEventListener('change', function() {
        const startInput = document.getElementById('startDate');
        const endInput = document.getElementById('endDate');
        const today = new Date().toISOString().split('T')[0];
        
        if (this.value === 'today') {
            startInput.value = today;
            endInput.value = today;
        } else if (this.value === 'mtd') {
            const firstDay = new Date(); firstDay.setDate(1);
            startInput.value = firstDay.toISOString().split('T')[0];
            endInput.value = today;
        } else if (this.value === 'last30') {
            const last30 = new Date(); last30.setDate(last30.getDate() - 30);
            startInput.value = last30.toISOString().split('T')[0];
            endInput.value = today;
        }
    });

    // 🔔 INTERACTIVE DROPDOWN CLICK LISTENER
    document.getElementById('notiBellWrapper').addEventListener('click', function(e) {
        e.stopPropagation();
        document.getElementById('notiDropdownTray').classList.toggle('show');
    });

    // Auto-close dropdown tray if user clicks anywhere else on the screen background
    document.addEventListener('click', function() {
        document.getElementById('notiDropdownTray').classList.remove('show');
    });
    </script>
</body>
</html>