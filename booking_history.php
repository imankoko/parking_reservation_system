<?php
session_start();
include 'db_connect.php';
$user_id = $_SESSION['user_id'];

$query = "SELECT b.*, p.slot_number, p.location 
          FROM tbl_booking b 
          JOIN tbl_parking_listing p ON b.parking_id = p.parking_id 
          WHERE b.user_id = $1 
          ORDER BY b.booking_date DESC, b.start_time DESC";
$result = pg_query_params($conn, $query, array($user_id));
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Booking History</title>
    <link rel="stylesheet" href="style.css">
</head>
<div class="nav-container">
    <a href="javascript:history.back()" class="btn-back">← Back</a>
    <a href="logout.php" class="btn-logout" onclick="return confirm('Logout from MySpot?')">Logout</a>
</div>
<body>
    <div class="header">
        <h2>My Bookings</h2>
    </div>

    <div class="container">
        <?php while ($row = pg_fetch_assoc($result)): ?>
            <div class="card" style="border:1px solid #ddd; padding:15px; margin:10px 0; border-radius:8px;">
                <strong>Date: <?php echo $row['booking_date']; ?></strong><br>
                Slot: <?php echo $row['slot_number']; ?> (<?php echo $row['location']; ?>)<br>
                Time: <?php echo $row['start_time']; ?> - <?php echo $row['end_time']; ?><br>
                Status: <span style="color:blue"><?php echo $row['booking_status']; ?></span>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>