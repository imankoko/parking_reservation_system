<?php
session_start();
include 'db_connect.php';

// Security Check: Force login wall validation to verify user state records
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Driver') {
    header("Location: login_view.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Force database connection and system execution to run on Malaysian Time
pg_query($conn, "SET TIME ZONE 'Asia/Kuala_Lumpur'");
date_default_timezone_set('Asia/Kuala_Lumpur');

$query = "SELECT b.*, p.slot_number, p.location 
          FROM tbl_booking b 
          JOIN tbl_parking_listing p ON b.parking_id = p.parking_id 
          WHERE b.user_id = $1 
          ORDER BY b.booking_date DESC, b.start_time DESC";
$result = pg_query_params($conn, $query, array($user_id));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Booking History - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #ffffff; margin: 0; padding-bottom: 80px; }
        
        /* Faded Yellow Header matching Dashboard & admin_reviews.php */
        .header {
            background: linear-gradient(to bottom, #d4bc44, #ffffff);
            padding: 30px 20px; display: flex; align-items: center; gap: 15px;
        }
        .logo-img { width: 60px; height: auto; }
        .header-text h2 { margin: 0; font-size: 1.4rem; color: #000; }

        .main-content { padding: 20px; }

        /* Table Container matching Dashboard & Admin view layouts */
        .table-container { 
            background: white; 
            padding: 15px; 
            border-radius: 12px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
            overflow-x: auto; 
        }
        
        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
        th { background: #f8f9fa; color: #888; text-transform: uppercase; font-size: 11px; }
        
        /* Monospace Badge design parameter wrapper for specific Parking slots */
        .slot-badge {
            background: #f0f0f0; color: #333; padding: 4px 8px;
            border-radius: 5px; font-weight: bold; font-family: monospace; font-size: 0.95em;
        }

        /* Highly scannable color-coded Status Badges mapping the driver workflow states */
        .status-badge { 
            padding: 4px 10px; 
            border-radius: 5px; 
            font-weight: bold; 
            font-size: 0.85em; 
            text-transform: uppercase;
            display: inline-block;
        }
        .status-confirmed { background: #e3f2fd; color: #0d47a1; }
        .status-completed { background: #e8f5e9; color: #1b5e20; }
        .status-cancelled { background: #ffebee; color: #b71c1c; }

        /* Bottom Navigation Bar matching Driver Dashboard context context */
        .bottom-nav {
            position: fixed; bottom: 0; width: 100%; background: #fff;
            display: flex; justify-content: space-around; padding: 10px 0;
            border-top: 1px solid #eee; box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            z-index: 1000;
        }
        .nav-item { text-align: center; text-decoration: none; color: #888; font-size: 0.8rem; flex: 1; }
        .nav-item i { font-size: 1.5rem; display: block; margin-bottom: 2px; }
        .nav-item.active { color: #000; }

        /* Smartphone display optimization view resets */
        @media (max-width: 768px) {
            .table-container { padding: 10px; border-radius: 8px; }
            th, td { padding: 10px 6px; font-size: 12px; }
            .status-badge { font-size: 0.8em; padding: 3px 6px; }
        }
    </style>
</head>
<body>

    <div class="header">
        <img src="logo.png" class="logo-img" alt="Logo">
        <div class="header-text">
            <h2>My Parking<br>Booking History</h2>
        </div>
    </div>

    <div class="main-content">
        <div class="table-container">
            <h3 style="margin: 0 0 15px 0; font-size: 1rem; color: #333;">
                <i class="fa-solid fa-clock-history" style="color: #d4bc44; margin-right: 5px;"></i> Reservation Activity Logs
            </h3>
            
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Reservation Date</th>
                        <th>Assigned Bay</th>
                        <th>Time Interval Space</th>
                        <th>Current Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result && pg_num_rows($result) > 0): 
                        while ($row = pg_fetch_assoc($result)): 
                            $raw_status = trim($row['booking_status']);
                            
                            // Map conditional styling metrics dynamically against raw string flags
                            $badge_modifier = "status-confirmed";
                            if (strcasecmp($raw_status, 'Completed') === 0) {
                                $badge_modifier = "status-completed";
                            } elseif (strcasecmp($raw_status, 'Cancelled') === 0) {
                                $badge_modifier = "status-cancelled";
                            }
                    ?>
                        <tr>
                            <td>#<?php echo $row['booking_id']; ?></td>
                            <td><strong><?php echo date('d M Y', strtotime($row['booking_date'])); ?></strong></td>
                            <td>
                                <span class="slot-badge"><?php echo htmlspecialchars($row['slot_number']); ?></span>
                                <small style="color: #666; margin-left: 4px;">(<?php echo htmlspecialchars($row['location']); ?>)</small>
                            </td>
                            <td style="color: #555;">
                                <?php echo date('h:i A', strtotime($row['start_time'])); ?> - 
                                <?php echo !empty($row['end_time']) ? date('h:i A', strtotime($row['end_time'])) : '--:--'; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $badge_modifier; ?>">
                                    <?php echo htmlspecialchars($raw_status); ?>
                                </span>
                            </td>
                        </tr>
                    <?php 
                        endwhile; 
                    elseif (!$result):
                        echo "<tr><td colspan='5' style='color:red; text-align:center; font-weight:bold; padding:20px;'>Database Error Reading Records Array.</td></tr>";
                    else:
                        echo "<tr><td colspan='5' style='text-align:center; padding: 40px; color: #888;'>
                                <i class='fa-solid fa-folder-open' style='font-size:2rem; display:block; margin-bottom:10px; color:#ccc;'></i>
                                No past reservation actions found.
                              </td></tr>";
                    endif; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="driver_dashboard.php" class="nav-item">
            <i class="fa-solid fa-house"></i> Home
        </a>
        <a href="booking_history.php" class="nav-item active">
            <i class="fa-solid fa-rectangle-list"></i> History
        </a>
        <a href="logout.php" class="nav-item" style="color:#dc3545;" onclick="return confirm('Logout from MySpot?')">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </nav>

</body>
</html>